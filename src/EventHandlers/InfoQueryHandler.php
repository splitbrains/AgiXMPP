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
    if ($eventName == 'iq' && $response->hasTag('ping') && $response->getAttributeFromTag('xmlns', 'ping') == self::XMPP_NAMESPACE_PING) {
      $id = $response->getAttribute('id');
      $from = $response->getAttribute('from');

      $this->getConnection()->send('<iq type="result" id="%s" to="%s" />', array($id, $from));
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger) {}
}