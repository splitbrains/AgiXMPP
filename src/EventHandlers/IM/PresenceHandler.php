<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 11.01.12 14:19.
 */
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class PresenceHandler extends EventReceiver
{
  const SHOW_STATUS_AWAY = 'away';
  const SHOW_STATUS_CHAT = 'chat';
  const SHOW_STATUS_DND  = 'dnd';
  const SHOW_STATUS_XA   = 'xa';

  protected $presences = array();

  public static function getPresences()
  {

  }

  /**
   * @param string $event
   */
  public function onTrigger($event)
  {
    $allAvailabilities = array(self::SHOW_STATUS_AWAY, self::SHOW_STATUS_CHAT, self::SHOW_STATUS_DND, self::SHOW_STATUS_XA);

    switch($event) {
      case TRIGGER_PRESENCE_INIT:
        // show initial presence

        $availability = $this->client->availability;
        $status = $this->client->status;
        $priority = $this->client->priority;

        $stanzaShow = '';
        $stanzaStatus = '';
        $stanzaPriority = '';

        if (!empty($availability) && in_array($availability, $allAvailabilities)) {
          $stanzaShow = sprintf('<show>%s</show>', $availability);
        }
        if (!empty($status)) {
          $stanzaStatus = sprintf('<status>%s</status>', $status);
        }
        if (is_numeric($priority) && $priority > -128 && $priority < 127) {
          $stanzaPriority = sprintf('<priority>%d</priority>', (int)$priority);
        }

        $this->connection->send('<presence from="%s">%s%s%s</presence>', array($this->client->JID, $stanzaShow, $stanzaStatus, $stanzaPriority));
        break;
    }
  }

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
    $response = $this->response;

    if ($event == 'presence' && $response->get('presence')->attr('to') == $this->client->JID) {

      $from = $response->get('presence')->attr('from');

      $presence = array();
      $presence['type'] = $response->get('presence')->attr('type');
      $presence['show'] = $response->get('show')->cdata();
      $presence['status'] = $response->get('status')->cdata();

      $this->presences[$from] = $presence;
    }
  }

}