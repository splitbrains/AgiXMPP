<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 11.01.12 14:19.
 */
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;
use XMPP\Logger;
 
class PresenceHandler extends EventReceiver
{
  /**
   * @param string $event
   */
  public function onEvent($event)
  {
  }

  /**
   * @param string $event
   */
  public function onTrigger($event)
  {
    switch($event) {
      case TRIGGER_SESSION_STARTED:
        // show presence
        break;
    }
  }
}