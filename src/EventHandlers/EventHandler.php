<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 11.01.12 14:19.
 */
namespace AgiXMPP\EventHandlers;

abstract class EventHandler
{
  const PRIORITY_LOWEST  = -2;
  const PRIORITY_LOW     = -1;
  const PRIORITY_NORMAL  = 0;
  const PRIORITY_HIGH    = 1;
  const PRIORITY_HIGHEST = 2;

  /**
   * @var \AgiXMPP\Response
   */
  public $response;

  /**
   * @var \AgiXMPP\Connection
   */
  public $connection;

  /**
   * @var \AgiXMPP\Socket
   */
  public $socket;

  /**
   * @var \AgiXMPP\Client
   */
  public $client;

  /**
   * @var string|null
   */
  public $uid = null;

  /**
   * @var int
   */
  public $priority;

  /**
   * @var array
   */
  private $events = array();

  /**
   * @var array
   */
  private $triggers = array();

  /**
   * This method is called as soon as the event handler is mounted into the event handling system.
   * If overwritten/extended, don't forget to call parent::onMount() at the end of your method.
   */
  public function onMount()
  {
    $this->registerTriggers();
    $this->registerEvents();
  }

  /**
   * Register all events.
   * An event is triggered by XMPP stanzas.
   * For example, if the client receives a message: <message from="..." to="..."><body>...</body></message>
   * You could register an event for 'message' like this:
   *   $this->on('message', function() { ...  }, EventPriority::LOW);
   *
   * Every stanza in any depth will trigger an event (in this example you could also hook to 'body').
   */
  abstract public function registerEvents();

  /**
   * Register all triggers (triggers are global).
   * Triggers are like manual fired events
   */
  abstract public function registerTriggers();

  /**
   * @param string $eventName
   * @param callable $callback
   */
  public function on($eventName, $callback)
  {
    $this->events[$eventName][] = new Event($eventName, $callback);
  }

  /**
   * @param string $triggerName
   * @param callable $callback
   */
  public function onTrigger($triggerName, $callback)
  {
    $this->triggers[$triggerName] = new Event($triggerName, $callback);
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