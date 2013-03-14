<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
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
      $id   = $response->get('iq')->attr('id');
      $from = $response->get('iq')->attr('from');

      $this->getConnection()->send('<iq type="result" id="%s" to="%s" />', array($id, $from));
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
  }
}