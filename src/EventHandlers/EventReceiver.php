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
use XMPP\Handler;
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
   * @var string|null
   */
  public $uid = null;

  /**
   * @var array
   */
  public $eventTags = array();

  /**
   * Events are fired on rules
   *
   * @abstract
   * @param string $event
   */
  abstract public function onEvent($event);

  /**
   * @abstract
   * @param string $trigger
   */
  abstract public function onTrigger($trigger);

  /**
   * @param \XMPP\Response $response
   * @param \XMPP\Connection $connection
   */
  public function setObjects(Response $response, Connection $connection)
  {
    $this->response = $response;
    $this->connection = $connection;
    $this->client = $connection->client;
    $this->socket = $connection->getSocket();
  }

  /**
   * @param string $event
   */
  public function trigger($event)
  {
    $this->connection->trigger($event);
  }
}