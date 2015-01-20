<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 12.01.12 14:21.
 */
namespace AgiXMPP\EventHandlers\IM;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\EventHandlers\Trigger;
use AgiXMPP\Response;

class RosterHandler extends EventHandler
{
  const ROSTER_FILE = 'data/contacts.json';

  const IQ_ROSTER_NAMESPACE = 'jabber:iq:roster';

  public function registerTriggers()
  {
    $this->onTrigger(Trigger::ROSTER_GET, function(Connection $c) {
      // request roster (contact list)
      $c ->send('<iq from="%s" type="get"><query xmlns="%s"/></iq>',array($c->client->jid, RosterHandler::IQ_ROSTER_NAMESPACE), true)
         ->onResponse(function(Response $r, Connection $c) {
          $children = $r->getAll('item');

          $json = array();
          foreach($children as $child) {
            $json[] = array('jid' => $child->attr('jid'), 'subscription' => $child->attr('subscription'));
          }
          $json = json_encode($json);

          file_put_contents(RosterHandler::ROSTER_FILE, $json);

          $c->trigger(Trigger::PRESENCE_INIT);
        });
    });
  }

  public function registerEvents()
  {
    return;
  }
}