<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 14.03.13 23:01.
 */
namespace XMPP;

use XMPP\Connection;
use XMPP\EventHandlers\EventReceiver;

class Event extends EventReceiver
{
  /**
   * @var callable
   */
  private $callback;

  /**
   * @var string
   */
  private $uid;

  public function __construct(Connection $connection, $event, $uid)
  {
    $this->uid = $uid;
    $connection->addCustomHandler('id', $uid, $uid, $this);
  }

  /**
   * @param callable $callback
   */
  public function onResponse(callable $callback)
  {
    $this->callback = $callback;
  }

  /**
   * @param string $eventName
   */
  public function onEvent($eventName)
  {
    if ($eventName == $this->uid) {
      $cb = $this->callback;
      $cb($this);
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
  }
}