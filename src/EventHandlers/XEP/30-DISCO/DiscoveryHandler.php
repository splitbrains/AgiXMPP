<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 07.01.12 15:59.
 */
namespace XMPP\EventHandlers;

use XMPP\Connection;
use XMPP\EventHandlers\EventHandler;
use XMPP\Response;

class DiscoveryHandler extends EventHandler
{
  public function registerTriggers()
  {
    $this->onTrigger(TRIGGER_SESSION_STARTED, function(Connection $c) {
      $c ->send('<iq type="get"><query xmlns="http://jabber.org/protocol/disco#items"/></iq>', true)
         ->onResponse(function(Response $r) {
           print_r($r->get('item')->attrs());
         });
    });
  }

  public function registerEvents()
  {
    return;
  }
}