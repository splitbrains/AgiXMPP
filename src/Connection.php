<?php
namespace XMPP;

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
  protected $Socket;

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


  /* @todo:
      All variables for packet handling
                                        */
  protected $_last_received_packet = 0;
  protected $_received_packets = 0;
  protected $_handlers = array();
  protected $_queue    = array();
  protected $_received_buffer;


  const XMPP_PROTOCOL_VERSION = '1.0';

  public function __construct($config)
  {
    $this->setHost($config['host']);
    $this->setPort($config['port']);
    $this->setUser($config['user']);
    $this->setPass($config['pass']);
    $this->setDomain($config['server']);
    $this->setResource($config['resource']);

    $this->XMLParser = new XMLParser();
    $this->Socket    = new Socket();
  }

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

  public function reconnect()
  {
    $this->disconnect();
    $this->connect();
  }

  public function main()
  {
    $this->registerDefaultHandlers();

    while($this->isConnected() && !$this->isTimedOut()) {
      $packets = $this->getReceivedPackets();

      if ($packets > 0) {
        echo strlen($this->_received_buffer);
      }

      $this->sleep();
    }

    // end of main loop
    return false;
  }

  protected function getReceivedPackets()
  {
    $this->getSocket()->read();
  }

  protected function receivePackets()
  {
    while(true) {
      $buf = $this->getSocket()->read();

      if (!empty($buf)) {
        $this->_last_received_packet = microtime(true);
        $this->_received_packets++;
        $this->_received_buffer += $buf;
      }

      if ($this->_last_received_packet + 1 > microtime(true)) {
        echo '[Log] INFO: Finished packet. Last received packet: '.$this->_last_received_packet.PHP_EOL;
        break;
      }
    }
    $rcvd = $this->_received_packets;
    $this->_received_packets = 0;

    return $rcvd;
  }

  protected function processBufferQueue($data)
  {
    if (!empty($data)) {
      print_r($this->XMLParser->parse($data));

    }
  }

  public function registerDefaultHandlers()
  {
    $streamHandler = new StreamHandlers();

    $this->addEventHandler('stream:stream', $streamHandler);
    $this->addEventHandler('stream:features', $streamHandler);
    $this->addEventHandler('stream:error', $streamHandler);
    //$this->addEventHandler('presence', array($this, '_event_presence'));
    //$this->addEventHandler('iq', array($this, '_event_iq'));
    //$this->addEventHandler('message', array($this, '_event_message'));
  }


  public function addEventHandler($event, EventReceiver $eventHandler)
  {
    $this->_handlers[$event][] = $eventHandler;
  }


  public function send($data, $args = array())
  {
    if (count($args) > 0) {
      $data = vsprintf($data, $args);
    }
    $this->getSocket()->write($data);
  }

  public function disconnect()
  {
    $this->getSocket()->write('</stream:stream>');
    $this->getSocket()->close();
    $this->connected = false;
  }

  public function isConnected()
  {
    return $this->connected;
  }

  protected function sleep()
  {
    usleep(mt_rand(50, 300) * 1000);
  }

  public function isTimedOut()
  {
    $info = stream_get_meta_data($this->getSocket()->getResource());
    return $info['timed_out'];
  }

  public function setCrypt($method, $activate = true)
  {
    stream_set_blocking($this->Socket, true);
    stream_socket_enable_crypto($this->Socket, $activate, $method);
    stream_set_blocking($this->Socket, false);
  }
  
  /**
   * @return \XMPP\Socket
   */
  public function getSocket()
  {
    return $this->Socket;
  }

  public function setHost($host)
  {
    $this->host = $host;
  }

  public function getHost()
  {
    return $this->host;
  }

  public function setPass($pass)
  {
    $this->pass = $pass;
  }

  public function getPass()
  {
    return $this->pass;
  }

  public function setPort($port)
  {
    if (is_numeric($port)) {
      $this->port = $port;
    }
  }

  public function getPort()
  {
    return $this->port;
  }

  public function setResource($resource)
  {
    $this->resource = $resource;
  }

  public function getResource()
  {
    return $this->resource;
  }

  public function setDomain($server)
  {
    $this->domain = $server;
  }

  public function getDomain()
  {
    return $this->domain;
  }

  public function setUser($user)
  {
    $this->user = $user;
  }

  public function getUser()
  {
    return $this->user;
  }
}