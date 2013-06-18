<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 29.11.11 21:15.
 */
namespace XMPP;

use XMPP\Client;
use XMPP\Socket;
use XMPP\Logger;
use XMPP\Response;
use XMPP\EventHandlers\EventHandler;
use XMPP\XML\Parser;

// default handlers which are registered in registerDefaultHandlers()
use XMPP\EventHandlers\StreamHandler;
use XMPP\EventHandlers\PingHandler;
use XMPP\EventHandlers\RosterHandler;
use XMPP\EventHandlers\PresenceHandler;

class Connection
{
  /**
   * @var string Remote XMPP server
   */
  public $host;

  /**
   * @var int Remote XMPP server port
   */
  public $port;

  /**
   * @var \XMPP\Client
   */
  public $client;

  /**
   * @var \XMPP\Socket The basic socket stream
   */
  private $socket;

  /**
   * @var \XMPP\XML\Parser
   */
  private $xmlParser;

  /**
   * @var \XMPP\EventHandlers\EventHandler[]
   */
  private $eventHandlers = array();

  /**
   * @var array
   */
  private $store = array();

  /**
   * @var int
   */
  private $_uid = 0;


  public function __construct(Client $client, $host, $port)
  {
    $this->host = $host;
    $this->port = $port;
    $this->client = $client;

    $this->socket = new Socket();
    $this->xmlParser = new Parser();

    $this->registerDefaultHandlers();
  }

  /**
   * just try it
   */
  public function __destruct()
  {
    $this->disconnect();
  }

  /**
   * Connects to the host with the settings from the config (set in the constructor)
   * @return bool
   */
  public function connect()
  {
    $conn = $this->socket->open('tcp', $this->host, $this->port);
    Logger::log('Attempting to connect to '.$this->host.':'.$this->port.'.');

    if ($conn) {
      $this->trigger(TRIGGER_INIT_STREAM);
      return true;
    }
    Logger::err('Could not connect to host.', true);
    return false;
  }

  /**
   * Reconnects the socket stream
   *
   * @param bool $closeStream
   */
  public function reconnect($closeStream = false)
  {
    $this->disconnect($closeStream);
    $this->connect();
  }

  /**
   * @param bool $closeStream
   */
  public function disconnect($closeStream = false)
  {
    if ($closeStream) {
      $this->socket->write(StreamHandler::XMPP_TERMINATE_STREAM);
    }
    $this->socket->close();
    Logger::log('Disconnected');
  }

  /**
   * Generates an unique identifier for outgoing stanzas
   *
   * @return string
   */
  public function UID()
  {
    return 'agixmpp_'.substr(md5(microtime().$this->_uid++), 0, 8);
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    if ($this->receive()) {
      $response = new Response($this->xmlParser->getTree());
      $this->handleEvents($response);
    }

    return !$this->socket->hasTimedOut() && $this->socket->isConnected();
  }

  /**
   * @return bool
   */
  protected function receive()
  {
    $buffer = $this->isEnclosedBuffer($this->socket->read());

    if ($buffer !== false) {
      if ($buffer == StreamHandler::XMPP_TERMINATE_STREAM) {
        $this->socket->close();
      } else {
        return $this->xmlParser->isValid($buffer);
      }
    }
    return false;
  }

  /**
   * Determine if the XML buffer is completely enclosed with '<' and '>'.
   *
   * @param $buffer
   * @return bool|string
   */
  protected function isEnclosedBuffer($buffer)
  {
    static $unclosedTags = 0, $fullBuffer = '';

    $tagOpenCount = substr_count($buffer, '<');
    $tagCloseCount = substr_count($buffer, '>');

    if ($tagOpenCount + $unclosedTags != $tagCloseCount) {
      $fullBuffer .= $buffer;
      $unclosedTags += $tagOpenCount - $tagCloseCount;
      return false;
    }
    $enclosedBuffer = $fullBuffer.$buffer;
    $unclosedTags = 0;
    $fullBuffer = '';
    return $enclosedBuffer;
  }

  /**
   * @param string $data
   * @param array $args
   * @param bool $awaitsResponse
   * @return \XMPP\Message
   */
  public function send($data, $args = array(), $awaitsResponse = false)
  {
    $message = count($args) > 0 ? vsprintf($data, $args) : $data;
    return new Message($message, $this, $awaitsResponse);
  }

  /**
   * Let the main loop sleep a bit to reduce the load
   *
   * @param int $min
   * @param int $max
   */
  public function sleep($min = 100, $max = 300)
  {
    usleep(mt_rand($min, $max) * 1000);
  }

  /**
   *
   */
  private function registerDefaultHandlers()
  {
    $this->addEventHandler(new StreamHandler());
    $this->addEventHandler(new PingHandler());
    $this->addEventHandler(new PresenceHandler());
    $this->addEventHandler(new RosterHandler());
  }

  /**
   * @param EventHandlers\EventHandler $handler
   */
  public function addEventHandler(EventHandler $handler)
  {
    $this->eventHandlers[] = $handler;
  }

  /**
   * @param \XMPP\Response $response
   */
  private function handleEvents(Response $response)
  {
    foreach($this->eventHandlers as $handler) {
      foreach($handler->getEvents() as $eventTag => $events) {
        foreach($events as $callback) {
          if ($response->has($eventTag)) {
            $this->invokeEvent($callback, array($response, $this));
          }
        }
      }
    }
  }

  /**
   * @param $callback
   * @param $parameters
   */
  public function invokeEvent($callback, $parameters)
  {
    if (is_callable($callback) || is_array($callback)) {
      call_user_func_array($callback, $parameters);
    }
  }

  /**
   * @param string $trigger
   */
  public function trigger($trigger)
  {
    foreach($this->eventHandlers as $handler) {
      //$handler->onTrigger($trigger, $this);
      foreach($handler->getTriggers() as $name => $callback) {
        if ($trigger == $name) {
          $this->invokeEvent($callback, array($this));
        }
      }
    }
  }

  /**
   * @return \XMPP\Socket
   */
  public function getSocket()
  {
    return $this->socket;
  }

  /**
   * @param $key
   * @param $val
   */
  public function store($key, $val)
  {
    $this->store[$key] = $val;
  }

  /**
   * @param $key
   * @return mixed
   */
  public function fetch($key)
  {
    if (isset($this->store[$key])) {
      return $this->store[$key];
    }
    return null;
  }
}