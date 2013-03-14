<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 07.01.12 15:59.
 */
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

/**
 * Handler for XEP-0199 (XMPP Ping)
 * See http://xmpp.org/extensions/xep-0030.html
 *
 * Class PingHandler
 * @package XMPP\EventHandlers
 */
class PingHandler extends EventReceiver
{
  const XMPP_NAMESPACE_PING = 'urn:xmpp:ping';

  /**
   * @param string $eventName
   */
  public function onEvent($eventName)
  {
    $response = $this->response;

    if ($eventName == 'iq' && $response->get('ping') && $response->get('ping')->attr('xmlns') == self::XMPP_NAMESPACE_PING) {
      $id = $response->get('iq')->attr('id');
      $from = $response->get('iq')->attr('from');

      $this->connection->send('<iq type="result" id="%s" to="%s" />', array($id, $from));
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
  }
}