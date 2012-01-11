<?php
namespace XMPP\EventHandlers;

abstract class EventReceiver
{
  /**
   * @var \XMPP\ResponseObject
   */
  protected $response;

  /**
   * @var \XMPP\Connection
   */
  protected $connection;

  /**
   * @var \XMPP\Socket
   */
  protected $socket;

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
   * @param EventObject $obj
   */
  public function setEventObject(EventObject $obj)
  {
    $this->response   = $obj->getResponse();
    $this->connection = $obj->getConnection();
    $this->socket     = $obj->getSocket();
  }

  /**
   * @return \XMPP\Connection
   */
  public function getConnection()
  {
    return $this->connection;
  }

  /**
   * @return \XMPP\ResponseObject
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
    $this->getConnection()->triggerEvent($event);
  }
}