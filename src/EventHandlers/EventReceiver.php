<?php
namespace XMPP\EventHandlers;

use XMPP\Client;
use XMPP\Connection;
use XMPP\XML\ResponseObject;

abstract class EventReceiver
{
  /**
   * @var \XMPP\XML\ResponseObject
   */
  public $response;

  /**
   * @var \XMPP\Connection
   */
  public $connection;

  /**
   * @var \XMPP\Socket
   */
  public $socket;

  /**
   * @var \XMPP\Client
   */
  public $client;

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
   * @param \XMPP\Client $client
   */
  public function setObjects(ResponseObject $response, Connection $connection, Client $client)
  {
    $this->response   = $response;
    $this->connection = $connection;
    $this->socket     = $connection->getSocket();
    $this->client     = $client;
  }

  /**
   * @param string $event
   */
  public function trigger($event)
  {
    $this->connection->trigger($event);
  }

  public function addEvent($event)
  {

  }
}