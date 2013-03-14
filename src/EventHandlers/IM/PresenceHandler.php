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
    $conn = $this->getConnection();
    $allAvailabilities = array(self::SHOW_STATUS_AWAY, self::SHOW_STATUS_CHAT, self::SHOW_STATUS_DND, self::SHOW_STATUS_XA);

    switch($event) {
      case TRIGGER_PRESENCE_INIT:
        // show initial presence

        $availability = $conn->getAvailability();
        $status = $conn->getStatus();
        $priority = $conn->getPriority();

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

        $conn->send('<presence from="%s">%s%s%s</presence>', array($this->getConnection()->getJID(), $stanzaShow, $stanzaStatus, $stanzaPriority));
        break;
    }
  }

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
    $response = $this->getResponse();

    if ($event == 'presence' && $response->get('presence')->attr('to') == $this->getConnection()->getJID()) {

      $from = $response->get('presence')->attr('from');

      $presence = array();
      $presence['type'] = $response->get('presence')->attr('type');
      $presence['show'] = $response->get('show')->cdata();
      $presence['status'] = $response->get('status')->cdata();

      $this->presences[$from] = $presence;
    }
  }

}