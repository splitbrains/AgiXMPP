<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 12.01.12 14:21.
 */
 
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class RosterHandler extends EventReceiver
{
  const ROSTER_FILE = 'data/contacts.json';

  const IQ_ROSTER_NAMESPACE = 'jabber:iq:roster';

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    if ($trigger == TRIGGER_ROSTER_GET) {
      // request roster (contact list)
      $this->connection
        ->send('<iq from="%s" type="get"><query xmlns="%s"/></iq>',array($this->client->JID, self::IQ_ROSTER_NAMESPACE), true)
        ->onResponse(function($e) {
          $children = $e->response->getAll('item');

          $json = array();
          foreach($children as $child) {
            $json[] = array('jid' => $child->attr('jid'), 'subscription' => $child->attr('subscription'));
          }
          $json = json_encode($json);

          file_put_contents(self::ROSTER_FILE, $json);

          $e->trigger(TRIGGER_PRESENCE_INIT);
        });
    }
  }
}