<?php
namespace XMPP;

use XMPP\Socket;
use XMPP\Client;
use XMPP\XMLParser;
use XMPP\ResponseObject;
use XMPP\Logger;

use XMPP\EventHandlers\EventObject;
use XMPP\EventHandlers\EventReceiver;

// default handlers which are registered in registerDefaultHandlers()
use XMPP\EventHandlers\StreamHandler;
use XMPP\EventHandlers\InfoQueryHandler;
use XMPP\EventHandlers\PresenceHandler;

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
   * @var \XMPP\Socket The basic socket stream
   */
  protected $socket;

  /**
   * @var \XMPP\Logger
   */
  protected $Logger;

  /**
   * @var \XMPP\XMLParser
   */
  protected $XMLParser;

  /**
   * @var array The list of all registered handlers
   */
  protected $_handlers = array();

  /**
   * @var array The list of events which should be triggered
   */
  protected $_triggers = array();

  /**
   * @var array The handlers in context with their events to wait for
   */
  protected $_event_handlers = array();

  /**
   * @var array The handlers which will be triggered by binding to an ID
   */
  protected $_bound_handlers = array();

  /**
   * @var string The queue buffer
   */
  protected $_received_buffer = '';

  /**
   * @var int For unique ID increnment
   */
  protected $_uid = 0;

  ///// Variables changed by the event handlers

  /**
   * @var string
   */
  protected $JID;


  /**
   * @var bool
   */
  protected $auth_status = false;


  // constants for XMPP namespaces and stuff
  const XMPP_PROTOCOL_VERSION = '1.0';

  const XMPP_STREAM_NAMESPACE = 'jabber:client';

  const XMPP_STREAM_NAMESPACE_STREAM = 'http://etherx.jabber.org/streams';

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
   */
  public function connect()
  {
    $conn = $this->getSocket()->open('tcp', $this->getHost(), $this->getPort());
    Logger::log('Attempting to connect to '.$this->getHost().':'.$this->getPort().'.');

    if ($conn) {
      $this->sendStart();
    } else {
      Logger::err('Could not connect to host.', true);
    }
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
   *
   */
  public function sendStart()
  {
    $conf = array($this->getHost(), $this->getUser(), self::XMPP_PROTOCOL_VERSION, self::XMPP_STREAM_NAMESPACE, self::XMPP_STREAM_NAMESPACE_STREAM);
    $this->send('<stream:stream to="%s" from="%s" version="%s" xmlns="%s" xmlns:stream="%s">', $conf);
  }

  /**
   * Generates an unique identifier for outgoing stanzas
   *
   * @return string
   */
  public function UID()
  {
    return 'agixmpp'.substr(md5($this->_uid++), -5);
  }

  public function init()
  {
    $this->main();
  }

  /**
   * The core loop
   *
   * @return bool
   */
  protected function main()
  {
    $this->registerDefaultHandlers();

    do {
      if ($this->listen()) {
        $buf = $this->XMLParser->parse($this->getBuffer());

        if ($buf !== false) {
          $response = $this->XMLParser->getResponse($buf);
          $this->handleEvents($response);
        }
      }
      $this->clearBuffer();
      $this->sleep();
    } while(!$this->getSocket()->hasTimedOut() && $this->getSocket()->isConnected());

    // end of main loop
    return true;
  }

  /**
   * @param \XMPP\ResponseObject $response
   */
  protected function handleEvents(ResponseObject $response)
  {
    foreach($this->getBoundEvents() as $id => $data) {
      if ($response->hasAttribute('id', $id)) {
        /**
         * @var $handler \XMPP\EventHandlers\EventReceiver
         */
        $handler = $data['handler'];
        $bound   = $data['bound'];

        $context = new EventObject($response, $this);
        $handler->setEventObject($context);
        $handler->onEvent($bound, $context);
      }
    }

    foreach($this->getEventHandlers() as $event => $handlers) {
      // filter out the specific event for the response object
      if ($response->setFilter($event)) {
        $context = new EventObject($response, $this);

        foreach($handlers as $handler) {
          $handler->setEventObject($context);
          $handler->onEvent($event, $context);
        }
      }
    }
  }

  /**
   * @param string $trigger
   */
  public function triggerEvent($trigger)
  {
    /** @var $handler \XMPP\EventHandlers\EventReceiver */
    foreach($this->_handlers as $handler) {
      $context = new EventObject(new ResponseObject(array()), $this);
      $handler->setEventObject($context);
      $handler->onTrigger($trigger);
    }
  }

  /**
   * @return bool
   */
  protected function listen()
  {
    $buf = $this->getSocket()->read();
    if ($buf !== false) {
      if ($buf == '</stream:stream>') {
        $this->getSocket()->close();
      } else {
        $this->_received_buffer = $buf;
        return true;
      }
    }
    return false;
  }

  /**
   * @return string
   */
  protected function getBuffer()
  {
    return $this->_received_buffer;
  }

  /**
   *
   */
  protected function clearBuffer()
  {
    $this->_received_buffer = '';
  }

  /**
   *
   */
  protected function registerDefaultHandlers()
  {
    $streamHandler   = new StreamHandler();
    $iqHandler       = new InfoQueryHandler();
    $presenceHandler = new PresenceHandler();

    $this->addEventHandlers(array('stream:stream', 'stream:features', 'stream:error', 'starttls', 'proceed', 'success', 'failure', 'bind'), $streamHandler);
    $this->addEventHandlers(array('iq', 'ping'), $iqHandler);
    $this->addEventHandlers(array('presence'), $presenceHandler);
  }

  /**
   * @param $id
   * @param $boundEvent
   * @param \XMPP\EventHandlers\EventReceiver $eventHandler
   */
  public function bindIdToEvent($id, $boundEvent, EventReceiver $eventHandler)
  {
    $this->_bound_handlers[$id] = array('bound' => $boundEvent, 'handler' => $eventHandler);
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
   * @return array
   */
  public function getBoundEvents()
  {
    return $this->_bound_handlers;
  }

  /**
   * @return array
   */
  protected function getEventHandlers()
  {
    return $this->_event_handlers;
  }

  /**
   * @param array $events
   * @param \XMPP\EventHandlers\EventReceiver $eventHandler
   */
  public function addEventHandlers(array $events, EventReceiver $eventHandler)
  {
    $this->addHandler($eventHandler);

    foreach($events as $event) {
      $this->_event_handlers[$event][] = $eventHandler;
    }
  }

  /**
   * @param \XMPP\EventHandlers\EventReceiver $handler
   */
  protected function addHandler(EventReceiver $handler)
  {
    if (!in_array($handler, $this->_handlers)) {
      $this->_handlers[] = $handler;
    }
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
    $this->getSocket()->write($data);
  }

  /**
   * @param bool $closeStream
   */
  public function disconnect($closeStream = false)
  {
    if ($closeStream) {
      $this->getSocket()->write('</stream:stream>');
    }
    $this->getSocket()->close();
    Logger::log('Disconnected');
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
   * @param string $host
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
   * @param string $pass
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
   * @param int $port
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
   * @param string $resource
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
   * @param string $server
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
   * @param string $user
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
   * @param string $JID
   */
  public function setJID($JID)
  {
    $this->JID = $JID;
  }

  /**
   * @return string
   */
  public function getJID()
  {
    return $this->JID;
  }
}