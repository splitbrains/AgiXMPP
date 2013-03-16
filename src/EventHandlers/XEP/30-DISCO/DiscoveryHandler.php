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
  public function onEvent($event)
  {
//    if ($event == 'disco_items') {
//      //$response->setFilter('item');
//      print_r($this->response->get('item')->attrs());
//    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    switch($trigger) {
      case TRIGGER_SESSION_STARTED:
        $this->connection
             ->send('<iq type="get"><query xmlns="http://jabber.org/protocol/disco#items"/></iq>', true)
             ->onResponse(function($h) {
               print_r($h->response->get('item')->attrs());
            });
        break;
    }
  }
}