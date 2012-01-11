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
   * @var \XMPP\Socket The basic socket stream
   */
  protected $socket;

  /**
   * @var \XMPP\Logger
   */
  protected $Logger;

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

  ///// Variables changed by the event handlers

  /**
   * @var string
   */
  protected $JID;

  /**
   * @var bool
   */
  protected $auth_status = false;

  /**
   * @param array $config
   */
  public function __construct(array $config)
  {
    $this->setHost($config['host']);
    $this->setPort($config['port']);
    $this->setUser($config['user']);
    $this->setPass($config['pass']);
    $this->setResource($config['resource']);

    $this->setSocket(new Socket());
    $this->registerDefaultHandlers();
  }

  /**
   * Connects to the host with the settings from the config (set in the constructor)
   */
  public function connect()
  {
    $conn = $this->getSocket()->open('tcp', $this->getHost(), $this->getPort());
    Logger::log('Attempting to connect to '.$this->getHost().':'.$this->getPort().'.');

    if ($conn) {
      $this->main();
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
   * Generates an unique identifier for outgoing stanzas
   *
   * @return string
   */
  public function UID()
  {
    return substr(uniqid('agixmpp'), 0, -5);
  }

  /**
   * @return bool
   */
  protected function receive()
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
   * @return string
   */
  protected function getReceived()
  {
    return $this->_received_buffer;
  }

  /**
   *
   */
  protected function clearReceived()
  {
    $this->_received_buffer = '';
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
   * The core loop
   *
   * @return bool
   */
  protected function main()
  {
    $this->triggerEvent(TRIGGER_INIT_STREAM);

    $xmlParser = new XMLParser();
    do {
      if ($this->receive()) {
        $buf = $xmlParser->parse($this->getReceived());

        if ($buf !== false) {
          $response = $xmlParser->getResponse($buf);

          $this->handleEvents($response);
        }
      }
      $this->clearReceived();
      $this->sleep();
    } while(!$this->getSocket()->hasTimedOut() && $this->getSocket()->isConnected());

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
   *
   * @todo make it more flexible, with custom attr name?
   */
  public function bindIdToEvent($id, $boundEvent, EventReceiver $eventHandler)
  {
    $this->_bound_handlers[$id] = array('bound' => $boundEvent, 'handler' => $eventHandler);
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
   * @param \XMPP\Socket $socket
   */
  public function setSocket(Socket $socket)
  {
    $this->socket = $socket;
  }

  /**
   * @param string $host
   */
  public function setHost($host)
  {
    $this->host = $host;
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
   * @param string $user
   */
  public function setUser($user)
  {
    $this->user = $user;
  }

  /**
   * @param string $pass
   */
  public function setPass($pass)
  {
    $this->pass = $pass;
  }

  /**
   * @param string $resource
   */
  public function setResource($resource)
  {
    $this->resource = $resource;
  }

  /**
   * @param string $JID
   */
  public function setJID($JID)
  {
    $this->JID = $JID;
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
}