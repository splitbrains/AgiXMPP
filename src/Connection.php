<?php
namespace XMPP;

use XMPP\Socket;
use XMPP\Client;
use XMPP\XMLParser;
use XMPP\ResponseObject;
use XMPP\Logger;

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
   * @var
   */
  protected $_parsed;

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
   * @var string The queue buffer
   */
  protected $_received_buffer = '';

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

    $this->xmlParser = new \XMPP\XML\Parser();

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

        print_r($this->getParsed());
        exit;

        $this->handleEvents();
        $this->clearReceived();
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
    $readBytes = 8192;

    $buf = $this->getSocket()->read($readBytes);

    if ($buf) {
      //if ($buf == '</stream:stream>') {        $this->getSocket()->close();      } else {}

      if (!$this->xmlParser->parse($buf) || $this->xmlParser->isEmpty()) {
        $this->_received_buffer .= $buf;
        $this->_invalid_xml = true;
        Logger::log('Invalid XML, appending...');
        return false;
      } else {
        if ($this->_invalid_xml) {
          $this->_received_buffer .= $buf;
          Logger::log('String completely received ('.(strlen($this->_received_buffer)).' chars)');
          $this->_invalid_xml = false;
        } else {
          Logger::log('String completely received (simple)');
          $this->_received_buffer = $buf;
          $this->_parsed = $this->xmlParser->getStructure();
        }
        return true;
      }
    }
    return false;
  }

  /*
  protected function receive()
  {
    $readBytes = 8192;

    $buf = $this->getSocket()->read($readBytes);

    if ($buf) {
      if ($buf == '</stream:stream>') {
        $this->getSocket()->close();
      } else {
        // woah! an unclosed XML tag, receive more!
        if (substr(trim($buf), -1) != '>' || strlen($buf) == $readBytes || preg_match('/<\/?.+[^>]$/', $buf)) {
          $this->_received_buffer .= $buf;
          $this->_invalid_xml = true;
          Logger::log('Invalid XML, appending...');
          return false;
        } else {
          if ($this->_invalid_xml) {
            $this->_received_buffer .= $buf;
            Logger::log('String completely received ('.(strlen($this->_received_buffer)).' chars)');
            $this->_invalid_xml = false;
          } else {
            Logger::log('String completely received (simple)');
            $this->_received_buffer = $buf;
          }
          return true;
        }
      }
    }
    return false;
  }
   */

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
  protected function getParsed()
  {
    return $this->_parsed;
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
   * @param \XMPP\ResponseObject $response
   */
  protected function handleEvents(ResponseObject $response)
  {
    foreach($this->getCustomEvents() as $key => $data) {
      if ($response->hasAttribute($data['attr'], $data['value'])) {
        /** @var $handler \XMPP\EventHandlers\EventReceiver */
        $handler = $data['handler'];
        $handler->setObjects($response, $this);
        $handler->onEvent($data['customEventName']);

        $this->unsetCustomEvent($key);
      }
    }

    foreach($this->getEventHandlers() as $event => $handlers) {
      // filter out the specific event for the response object
      if ($response->setFilter($event)) {
        foreach($handlers as $handler) {
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
   * @param EventHandlers\EventReceiver $eventHandler
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
   * @param int $key
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