<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 11.01.12 14:19.
 */
namespace AgiXMPP\EventHandlers\IM;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\EventHandlers\EventTrigger;
use AgiXMPP\Response;

class PresenceHandler extends EventHandler
{
  const SHOW_STATUS_AWAY = 'away';
  const SHOW_STATUS_CHAT = 'chat';
  const SHOW_STATUS_DND  = 'dnd';
  const SHOW_STATUS_XA   = 'xa';

  protected $presences = array();

  public static function getPresences()
  {
  }

  public static function getAvailabilities()
  {
    return array(
      self::SHOW_STATUS_AWAY,
      self::SHOW_STATUS_CHAT,
      self::SHOW_STATUS_DND,
      self::SHOW_STATUS_XA
    );
  }

  public function registerTriggers()
  {
    $this->onTrigger(EventTrigger::PRESENCE_INIT, function(Connection $c) {
      $client = $c->client;
      // show initial presence

      $status = $client->status;
      $priority = $client->priority;
      $availability = $client->availability;

      $stanzaShow = '';
      $stanzaStatus = '';
      $stanzaPriority = '';

      if (!empty($availability) && in_array($availability, PresenceHandler::getAvailabilities())) {
        $stanzaShow = sprintf('<show>%s</show>', $availability);
      }
      if (!empty($status)) {
        $stanzaStatus = sprintf('<status>%s</status>', $status);
      }
      if (is_numeric($priority) && $priority > -128 && $priority < 127) {
        $stanzaPriority = sprintf('<priority>%d</priority>', (int)$priority);
      }

      $c->send('<presence from="%s">%s%s%s</presence>', array($client->JID, $stanzaShow, $stanzaStatus, $stanzaPriority));
    });
  }

  public function registerEvents()
  {
    $this->on('presence', function(Response $r, Connection $c) {
      if ($r->get('presence')->attr('to') == $c->client->JID) {
        $from = $r->get('presence')->attr('from');

        $presence = array();
        $presence['type'] = $r->get('presence')->attr('type');
        $presence['show'] = $r->get('show')->cdata();
        $presence['status'] = $r->get('status')->cdata();
      }
    });
  }
}