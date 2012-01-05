<?php
namespace XMPP;

use XMPP\SocketMock;
use XMPP\Socket;
use XMPP\Client;
use XMPP\XMLParser;
use XMPP\Logger;

use XMPP\EventHandlers\EventReceiver;
use XMPP\EventHandlers\StreamHandlers;

class Connection
{
  /**
   * @var string Remote XMPP server
   */
  protected $host;

  /**
   * @var int Remote XMPP server port
   */
  protected $port;

  /**
   * @var string User for authentication
   */
  protected $user;

  /**
   * @var string Password for authentication
   */
  protected $pass;

  /**
   * @var string The resource, which will be shown in the full JID (e.g. laptop, mobile, ..)
   */
  protected $resource;

  /**
   * @var string @TODO ?
   */
  protected $domain;

  /**
   * @var Socket The basic socket stream
   */
  protected $socket;

  /**
   * @var Logger
   */
  protected $Logger;

  /**
   * @var XMLParser
   */
  protected $XMLParser;

  /**
   * @var bool
   */
  protected $connected = false;

  /**
   * @var array The event handlers
   */
  protected $_handlers = array();

  /**
   * @var string The queue buffer
   */
  protected $_received_buffer = '';

  const XMPP_PROTOCOL_VERSION = '1.0';

  /**
   * @param array $config
   */
  public function __construct(array $config)
  {
    $this->setHost($config['host']);
    $this->setPort($config['port']);
    $this->setUser($config['user']);
    $this->setPass($config['pass']);
    $this->setDomain($config['server']);
    $this->setResource($config['resource']);

    $this->XMLParser = new XMLParser();
    $this->socket    = new Socket();
  }

  /**
   * Connects to the host with the settings from the config (set in the constructor)
   *
   * @return void
   */
  public function connect()
  {
    $conn = $this->getSocket()->open('tcp', $this->getHost(), $this->getPort());

    if ($conn) {
      $this->send('<stream:stream to="%s" from="%s" version="%s" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">', array(
          $this->getHost(),
          $this->getUser(),
          self::XMPP_PROTOCOL_VERSION
        )
      );
      $this->connected = true;
    }
  }

  /**
   * Reconnects the socket stream
   *
   * @return void
   */
  public function reconnect()
  {
    $this->disconnect();
    $this->connect();
  }

  /**
   * The core loop
   *
   * @return bool
   */
  public function main()
  {
    $this->registerDefaultHandlers();

    do {
      // Start time, to determine if we have a timeout
      $start = microtime(true);

      if ($this->listen()) {
        $parsed = $this->XMLParser->parse($this->_received_buffer);

        if ($parsed !== false) {
          foreach($this->getEventHandlers() as $event => $handlers) {
            $found = $this->XMLParser->findTag($event, $parsed);

            if ($found !== false) {
              /** @var $handler EventReceiver */
              foreach($handlers as $handler) {
                $handler->onEvent($event, $found);
              }
            }
          }
        }
      }
      $this->clearBuffer();
      $this->sleep();
    } while($this->isConnected() && !$this->getSocket()->hasTimedOut($start));

    // end of main loop
    return true;
  }

  protected function listen()
  {
    $buf = $this->getSocket()->read();
    if ($buf !== false) {
      $this->_received_buffer = $buf;
      return true;
    }
    return false;
  }

  protected function clearBuffer()
  {
    $this->_received_buffer = '';
  }

  protected function registerDefaultHandlers()
  {
    $streamHandler = new StreamHandlers();

    $this->addEventHandler('stream:stream', $streamHandler);
    $this->addEventHandler('stream:features', $streamHandler);
    $this->addEventHandler('stream:error', $streamHandler);
    //$this->addEventHandler('presence', array($this, '_event_presence'));
    //$this->addEventHandler('iq', array($this, '_event_iq'));
    //$this->addEventHandler('message', array($this, '_event_message'));
  }

  /**
   * @return array
   */
  protected function getEventHandlers()
  {
    return $this->_handlers;
  }


  /**
   * @param $event
   * @param EventHandlers\EventReceiver $eventHandler
   */
  public function addEventHandler($event, EventReceiver $eventHandler)
  {
    $this->_handlers[$event][] = $eventHandler;
  }


  /**
   * @param $data
   * @param array $args
   */
  public function send($data, $args = array())
  {
    if (count($args) > 0) {
      $data = vsprintf($data, $args);
    }
    $this->getSocket()->write($data);
  }

  /**
   *
   */
  public function disconnect()
  {
    $this->getSocket()->write('</stream:stream>');
    $this->getSocket()->close();
    $this->connected = false;
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    return $this->connected;
  }

  /**
   * Let the main loop sleep a bit to reduce the load
   *
   * @param int $min
   * @param int $max
   */
  protected function sleep($min = 100, $max = 500)
  {
    usleep(mt_rand($min, $max) * 1000);
  }
  
  /**
   * @return \XMPP\Socket
   */
  public function getSocket()
  {
    return $this->socket;
  }

  /**
   * @param $host
   */
  public function setHost($host)
  {
    $this->host = $host;
  }

  /**
   * @return string
   */
  public function getHost()
  {
    return $this->host;
  }

  /**
   * @param $pass
   */
  public function setPass($pass)
  {
    $this->pass = $pass;
  }

  /**
   * @return string
   */
  public function getPass()
  {
    return $this->pass;
  }

  /**
   * @param $port
   */
  public function setPort($port)
  {
    if (is_numeric($port)) {
      $this->port = $port;
    }
  }

  /**
   * @return int
   */
  public function getPort()
  {
    return $this->port;
  }

  /**
   * @param $resource
   */
  public function setResource($resource)
  {
    $this->resource = $resource;
  }

  /**
   * @return string
   */
  public function getResource()
  {
    return $this->resource;
  }

  /**
   * @param $server
   */
  public function setDomain($server)
  {
    $this->domain = $server;
  }

  /**
   * @return string
   */
  public function getDomain()
  {
    return $this->domain;
  }

  /**
   * @param $user
   */
  public function setUser($user)
  {
    $this->user = $user;
  }

  /**
   * @return string
   */
  public function getUser()
  {
    return $this->user;
  }
}