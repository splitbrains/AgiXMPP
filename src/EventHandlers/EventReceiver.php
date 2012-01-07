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
   * @param $context
   */
  public function onEvent($eventName, $context);
}