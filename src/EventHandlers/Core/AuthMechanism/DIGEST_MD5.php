<?php
namespace AgiXMPP\EventHandlers\Core\AuthMechanism;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\Core\StreamHandler;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\Response;

/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.01.15 15:23.
 */
 
class DIGEST_MD5 extends EventHandler
{
  public function onMount()
  {
    $this->connection->send('<auth xmlns="%s" mechanism="DIGEST-MD5"/>', array(StreamHandler::XMPP_NAMESPACE_SASL));

    parent::onMount();
  }

  public function registerEvents()
  {
    $this->on('challenge', function(Response $r, Connection $c) {
      $challengeNode = $r->get('challenge');

      // see http://wiki.xmpp.org/web/SASLandDIGEST-MD5
      if ($challengeNode->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_SASL) {
        $challenge = base64_decode($challengeNode->cdata);
        //??? $response = '';

        if (strpos($challenge, 'nonce') !== false) {
          $response = $c->createSASLAuth('DIGEST-MD5', array(
            'authcid' => $c->client->username,
            'secret' => $c->client->password,
            'hostname' => $c->host,
            'service' => StreamHandler::XMPP_SERVICE_NAME
          ))->createResponse($challenge);

          $response = base64_encode($response);
        } elseif (strpos($challenge, 'rspauth') !== false) {
          $response = '';
        }

        $c->send('<response xmlns="%s">%s</response>', array(StreamHandler::XMPP_NAMESPACE_SASL, $response));
      }
    });
  }

  public function registerTriggers() {}
}