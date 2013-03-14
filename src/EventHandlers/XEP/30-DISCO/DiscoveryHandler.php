<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 07.01.12 15:59.
 */
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class DiscoveryHandler extends EventReceiver
{
  public function onEvent($eventName) {
    if ($eventName == 'disco_items') {
      //$response->setFilter('item');
      print_r($this->response->get('item')->attrs());
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    switch($trigger) {
      case TRIGGER_SESSION_STARTED:
        $id = $this->connection->UID();
        $this->connection->send('<iq type="get" id="%s"><query xmlns="http://jabber.org/protocol/disco#items"/></iq>', array($id));
        $this->connection->addIdHandler($id, 'disco_items', $this);
        break;
    }
  }
}