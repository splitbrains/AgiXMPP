<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 11.01.12 14:19.
 */
namespace XMPP\EventHandlers;

use XMPP\Client;
use XMPP\Connection;
use XMPP\Response;

abstract class EventReceiver
{
  /**
   * @var \XMPP\Response
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
   * @param \XMPP\Response $response
   * @param \XMPP\Connection $connection
   * @param \XMPP\Client $client
   */
  public function setObjects(Response $response, Connection $connection, Client $client)
  {
    $this->response = $response;
    $this->connection = $connection;
    $this->socket = $connection->getSocket();
    $this->client = $client;
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