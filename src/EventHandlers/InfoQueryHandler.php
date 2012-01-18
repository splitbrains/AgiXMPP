<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 07.01.12 15:59.
 */
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class InfoQueryHandler extends EventReceiver
{

  const XMPP_NAMESPACE_PING = 'urn:xmpp:ping';

  /**
   * @param string $eventName
   */
  public function onEvent($eventName)
  {
    $response = $this->getResponse();

    if ($eventName == 'iq' && $response->get('ping') && $response->get('ping')->attr('xmlns') == self::XMPP_NAMESPACE_PING) {
      $id = $response->get('iq')->attr('id');
      $from = $response->get('iq')->attr('from');

      $this->getConnection()->send('<iq type="result" id="%s" to="%s" />', array($id, $from));
    }

    if ($eventName == 'disco_items') {
      //$response->setFilter('item');
      print_r($response->get('item')->attrs());
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    switch($trigger) {
      case TRIGGER_SESSION_STARTED:
        $id = $this->getConnection()->UID();
        $this->getConnection()->send('<iq type="get" id="%s"><query xmlns="http://jabber.org/protocol/disco#items"/></iq>', array($id));
        $this->getConnection()->addIdHandler($id, 'disco_items', $this);
        break;
    }
  }
}