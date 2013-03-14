<?php
namespace XMPP;

use XMPP\Client;
use XMPP\Socket;
use XMPP\Logger;
use XMPP\XML\ResponseObject;
use XMPP\XML\Parser;
use XMPP\EventHandlers\EventReceiver;

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
   * @var array The list of all registered handlers
   */
  private $_handlers = array();

  /**
   * @var array The list of events which should be triggered
   */
  private $_triggers = array();

  /**
   * @var array The handlers in context with their events to wait for
   */
  private $_event_handlers = array();

  /**
   * @var array
   */
  private $_custom_handlers = array();

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
      $response = new ResponseObject($this->xmlParser->getTree());
      $this->handleEvents($response);
    }

    return !$this->socket->hasTimedOut() && $this->socket->isConnected();
  }

  /**
   * @return bool
   */
  protected function receive()
  {
    $buffer = $this->enclosedBuffer($this->socket->read());
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
  protected function enclosedBuffer($buffer)
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
   */
  public function send($data, $args = array())
  {
    if (count($args) > 0) {
      $data = vsprintf($data, $args);
    }
    $this->socket->write($data);
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
   * @param \XMPP\XML\ResponseObject $response
   */
  protected function handleEvents(ResponseObject $response)
  {
    foreach($this->getCustomEvents() as $key => $data) {
      if ($response->getByAttr($data['attr'], $data['value'])) {
        /** @var $handler \XMPP\EventHandlers\EventReceiver */
        $handler = $data['handler'];
        $handler->setObjects($response, $this, $this->client);
        $handler->onEvent($data['customEventName']);

        $this->removeCustomEvent($key);
      }
    }

    foreach($this->getEventHandlers() as $event => $handlers) {
      foreach($handlers as $handler) {
        if (!empty($response->get($event)->tag)) {
          $handler->setObjects($response, $this, $this->client);
          $handler->onEvent($event);
        }
      }
    }
  }

  /**
   * @param string $trigger
   */
  public function trigger($trigger)
  {
    /** @var $handler \XMPP\EventHandlers\EventReceiver */
    foreach($this->getHandlers() as $handler) {
      $handler->setObjects(new ResponseObject(array()), $this, $this->client);
      $handler->onTrigger($trigger);
    }
  }

  /**
   *
   */
  protected function registerDefaultHandlers()
  {
    $iqHandler = new PingHandler();
    $streamHandler = new StreamHandler();
    $rosterHandler = new RosterHandler();
    $presenceHandler = new PresenceHandler();

    $this->addEventHandlers(array('stream:stream', 'stream:features', 'stream:error', 'starttls', 'proceed', 'success', 'failure', 'bind'), $streamHandler);
    $this->addEventHandlers(array('iq', 'ping'), $iqHandler);
    $this->addEventHandlers(array('presence'), $presenceHandler);
    $this->addHandler($rosterHandler);
  }

  /**
   * This is a one time custom handler; after the event was triggered it's thrown away.
   * For re-usability one can use triggers
   *
   * @param string $attr
   * @param string $value
   * @param string $customEventName
   * @param \XMPP\EventHandlers\EventReceiver $eventHandler
   */
  public function addCustomHandler($attr, $value, $customEventName, EventReceiver $eventHandler)
  {
    $this->_custom_handlers[] = array('attr' => $attr, 'value' => $value, 'customEventName' => $customEventName, 'handler' => $eventHandler);
  }

  public function addIdHandler($value, $customEventName, EventReceiver $eventHandler)
  {
    $this->addCustomHandler('id', $value, $customEventName, $eventHandler);
  }

  /**
   * @return array
   */
  protected function getCustomEvents()
  {
    return $this->_custom_handlers;
  }

  /**
   * @param $key
   */
  protected function removeCustomEvent($key)
  {
    if (isset($this->_custom_handlers[$key])) {
      unset($this->_custom_handlers[$key]);
      $this->_custom_handlers = array_values($this->_custom_handlers);
      return true;
    }
    return false;
  }

  /**
   * @param array $events
   * @param \XMPP\EventHandlers\EventReceiver $eventHandler
   */
  public function addEventHandlers(array $events, EventReceiver $eventHandler)
  {
    $this->addHandler($eventHandler);

    foreach($events as $event) {
      //$this->_event_handlers[$event][] = $eventHandler;
      $this->addEventToHandler($event, $eventHandler, false);
    }
  }

  /**
   * @param $event
   * @param \XMPP\EventHandlers\EventReceiver $eventHandler
   * @param bool $addHandler
   */
  public function addEventToHandler($event, EventReceiver $eventHandler, $addHandler = true)
  {
    if ($addHandler) {
      $this->addHandler($eventHandler);
    }

    $this->_event_handlers[$event][] = $eventHandler;
  }

  /**
   * @return array
   */
  protected function getEventHandlers()
  {
    return $this->_event_handlers;
  }

  /**
   * @param \XMPP\EventHandlers\EventReceiver $handler
   */
  public function addHandler(EventReceiver $handler)
  {
    if (!in_array($handler, $this->_handlers)) {
      $this->_handlers[] = $handler;
    }
  }

  /**
   * @return array
   */
  protected function getHandlers()
  {
    return $this->_handlers;
  }

  /**
   * @return array
   */
  protected function getTriggers()
  {
    return $this->_triggers;
  }

  /**
   *
   */
  protected function clearTriggers()
  {
    $this->_triggers = array();
  }

  /**
   * @return \XMPP\Socket
   */
  public function getSocket()
  {
    return $this->socket;
  }

  /**
   * @return string
   */
  public function getHost()
  {
    return $this->host;
  }

  /**
   * @return int
   */
  public function getPort()
  {
    return $this->port;
  }

  /**
   * @return string
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * @return string
   */
  public function getPass()
  {
    return $this->pass;
  }

  /**
   * @return string
   */
  public function getResource()
  {
    return $this->resource;
  }

  /**
   * @return string
   */
  public function getJID()
  {
    return $this->JID;
  }

  /**
   * @param bool $auth_status
   */
  public function setAuthStatus($auth_status)
  {
    $this->auth_status = $auth_status;
  }

  /**
   * @return bool
   */
  public function getAuthStatus()
  {
    return $this->auth_status;
  }

  /**
   * @param string $availability
   */
  public function setAvailability($availability)
  {
    $this->availability = $availability;
  }

  /**
   * @return string
   */
  public function getAvailability()
  {
    return $this->availability;
  }

  /**
   * @param string $priority
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }

  /**
   * @return string
   */
  public function getPriority()
  {
    return $this->priority;
  }

  /**
   * @param string $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }

  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @param \XMPP\SendQueue $sendQueue
   */
  public function setSendQueue($sendQueue)
  {
    $this->sendQueue = $sendQueue;
  }

  /**
   * @return \XMPP\SendQueue
   */
  public function getSendQueue()
  {
    return $this->sendQueue;
  }
}