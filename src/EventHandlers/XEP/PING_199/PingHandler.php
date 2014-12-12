<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 07.01.12 15:59.
 */
namespace AgiXMPP\EventHandlers\XEP\PING_199;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\Response;

/**
 * Handler for XEP-0199 (XMPP Ping)
 * See http://xmpp.org/extensions/xep-0199.html
 *
 * Class PingHandler
 * @package XMPP\EventHandlers
 */
class PingHandler extends EventHandler
{
  const XMPP_NAMESPACE_PING = 'urn:xmpp:ping';

  public function registerTriggers()
  {
    return;
  }

  public function registerEvents()
  {
    $this->on('iq', function(Response $r, Connection $c) {
      if ($r->get('ping') && $r->get('ping')->attr('xmlns') == PingHandler::XMPP_NAMESPACE_PING) {
        $id = $r->get('iq')->attr('id');
        $from = $r->get('iq')->attr('from');

        $c->send('<iq type="result" id="%s" to="%s" />', array($id, $from));
      }
    });
  }
}