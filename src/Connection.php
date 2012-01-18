<?php
namespace XMPP;

use XMPP\Socket;
use XMPP\Client;
use XMPP\Logger;
use XMPP\XML\ResponseObject;
use XMPP\XML\Parser;
use XMPP\EventHandlers\EventReceiver;

// default handlers which are registered in registerDefaultHandlers()
use XMPP\EventHandlers\StreamHandler;
use XMPP\EventHandlers\InfoQueryHandler;
use XMPP\EventHandlers\RosterHandler;
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
   * @var string
   */
  protected $availability;

  /**
   * @var string
   */
  protected $priority;

  /**
   * @var string
   */
  protected $status;

  /**
   * @var \XMPP\Socket The basic socket stream
   */
  protected $socket;

  /**
   * @var \XMPP\XML\Parser
   */
  protected $xmlParser;

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
   * @var array
   */
  protected $_custom_handlers = array();

  /**
   * @var int
   */
  protected $_uid = 0;

  protected $_invalid_xml = false;

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
    $this->setAvailability($config['availability']);
    $this->setStatus($config['status']);
    $this->setPriority($config['priority']);

    $this->xmlParser = new Parser();

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
      $this->getSocket()->write(StreamHandler::XMPP_TERMINATE_STREAM);
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
    return 'agixmpp_'.substr(md5(microtime().$this->_uid++), 0, 8);
  }

  /**
   * The core loop
   *
   * @return bool
   */
  protected function main()
  {
    $this->trigger(TRIGGER_INIT_STREAM);

    do {
      if ($this->receive()) {
        $response = new ResponseObject($this->xmlParser->getTree());
        $this->handleEvents($response);
      }
      $this->sleep();
    } while(!$this->getSocket()->hasTimedOut() && $this->getSocket()->isConnected());

    return true;
  }

  /**
   * @return bool
   */
  protected function receive()
  {
    $buf = $this->getSocket()->read();
    if ($buf) {
      if ($buf == StreamHandler::XMPP_TERMINATE_STREAM) {
        $this->getSocket()->close();
      } else {
        return $this->xmlParser->isValid($buf);
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
   * Let the main loop sleep a bit to reduce the load
   *
   * @param int $min
   * @param int $max
   */
  protected function sleep($min = 100, $max = 300)
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
        $handler->setObjects($response, $this);
        $handler->onEvent($data['customEventName']);

        $this->unsetCustomEvent($key);
      }
    }

    foreach($this->getEventHandlers() as $event => $handlers) {
      foreach($handlers as $handler) {
        if (!empty($response->get($event)->tag)) {
          $handler->setObjects($response, $this);
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
      $handler->setObjects(new ResponseObject(array()), $this);
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
    $rosterHandler   = new RosterHandler();

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
  protected function unsetCustomEvent($key)
  {
    unset($this->_custom_handlers[$key]);
    $this->_custom_handlers = array_values($this->_custom_handlers);
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
   * @param \XMPP\Socket $socket
   */
  protected function setSocket(Socket $socket)
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
}