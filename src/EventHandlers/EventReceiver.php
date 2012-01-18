<?php
namespace XMPP\EventHandlers;

use XMPP\Connection;
use XMPP\XML\ResponseObject;

abstract class EventReceiver
{
  /**
   * @var \XMPP\XML\ResponseObject
   */
  private $response;

  /**
   * @var \XMPP\Connection
   */
  private $connection;

  /**
   * @var \XMPP\Socket
   */
  private $socket;

  /**
   * Events are fired on rules
   *
   * @abstract
   * @param string $eventName
   */
  abstract public function onEvent($eventName);

  /**
   * @abstract
   * @param string $trigger
   */
  abstract public function onTrigger($trigger);

  /**
   * @param \XMPP\XML\ResponseObject $response
   * @param \XMPP\Connection $connection
   */
  public function setObjects(ResponseObject $response, Connection $connection)
  {
    $this->response   = $response;
    $this->connection = $connection;
    $this->socket     = $connection->getSocket();
  }

  /**
   * @param \XMPP\Connection $connection
   */
  public function setConnection($connection)
  {
    $this->connection = $connection;
  }

  /**
   * @param \XMPP\XML\ResponseObject $response
   */
  public function setResponse($response)
  {
    $this->response = $response;
  }

  /**
   * @param \XMPP\Socket $socket
   */
  public function setSocket($socket)
  {
    $this->socket = $socket;
  }

  /**
   * @return \XMPP\Connection
   */
  public function getConnection()
  {
    return $this->connection;
  }

  /**
   * @return \XMPP\XML\ResponseObject
   */
  public function getResponse()
  {
    return $this->response;
  }

  /**
   * @return \XMPP\Socket
   */
  public function getSocket()
  {
    return $this->socket;
  }

  /**
   * @param string $event
   */
  public function trigger($event)
  {
    $this->getConnection()->trigger($event);
  }

  public function addEvent($event)
  {

  }
}