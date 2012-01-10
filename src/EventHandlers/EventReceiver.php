<?php
namespace XMPP\EventHandlers;

/**
 *
 */
interface EventReceiver
{
  /**
   * @abstract
   * @param $eventName
   * @param $that EventObject
   */
  public function onEvent($eventName, $that);
}