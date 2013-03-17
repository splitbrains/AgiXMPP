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

abstract class EventHandler
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
  private $events = array();

  /**
   * @var array
   */
  private $triggers = array();

  /**
   *
   */
  public function __construct()
  {
    $this->registerTriggers();
    $this->registerEvents();
  }

  /**
   * Register all events
   */
  abstract public function registerEvents();

  /**
   * Register all triggers (triggers are global)
   */
  abstract public function registerTriggers();

  /**
   * @param string $event
   * @param callable $callback
   */
  public function on($event, $callback)
  {
    $this->events[$event][] = $callback;
  }

  /**
   * @param string $trigger
   * @param callable $callback
   */
  public function onTrigger($trigger, $callback)
  {
    $this->triggers[$trigger] = $callback;
  }

  /**
   * @return array
   */
  public function getEvents()
  {
    return $this->events;
  }

  /**
   * @return array
   */
  public function getTriggers()
  {
    return $this->triggers;
  }
}