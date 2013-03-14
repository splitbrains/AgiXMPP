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
    switch($event) {
      case 'roster_response':
        // @todo handle this stuff

        $children = $this->response->getAll('item');

        $json = array();
        foreach($children as $child) {
          $json[] = array('jid' => $child->attr('jid'), 'subscription' => $child->attr('subscription'));
        }
        $json = json_encode($json);

        file_put_contents(self::ROSTER_FILE, $json);

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
      $id = $this->connection->UID();
      $this->connection->send('<iq from="%s" id="%s" type="get"><query xmlns="%s"/></iq>', array($this->client->JID, $id, self::IQ_ROSTER_NAMESPACE));
      $this->connection->addIdHandler($id, 'roster_response', $this);
    }
  }
}