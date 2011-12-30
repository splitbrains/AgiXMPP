<?php
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class StreamHandlers implements EventReceiver
{
  public function onEvent($eventName, $context)
  {
    switch($eventName) {
      case 'stream:stream':
        break;
    }
  }
}