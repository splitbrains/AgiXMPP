<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 12.01.12 14:21.
 */
 
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

\XMPP\Logger::$enabled = true;

class RosterHandler extends EventReceiver
{
  const IQ_ROSTER_NAMESPACE = 'jabber:iq:roster';

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
    switch($event) {
      case 'roster_response':
        // @todo handle this stuff

        print_r($this->getResponse()->get('query'));

        $this->trigger(TRIGGER_PRESENCE_INIT);
        break;
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    if ($trigger == TRIGGER_ROSTER_GET) {
      // request roster (contact list)
      $id = $this->getConnection()->UID();
      $this->getConnection()->send('<iq from="%s" id="%s" type="get"><query xmlns="%s"/></iq>', array($this->getConnection()->getJID(), $id, self::IQ_ROSTER_NAMESPACE));
      $this->getConnection()->addIdHandler($id, 'roster_response', $this);
    }
  }
}