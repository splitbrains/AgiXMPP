<?php
namespace AgiXMPP\EventHandlers\Core\AuthMechanism;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\Core\StreamHandler;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\Logger;
use AgiXMPP\Response;

/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.01.15 15:23.
 */

class SCRAM_SHA_1 extends EventHandler
{
  public function onMount()
  {
    $authSASL = $this->connection->createSASLAuth('SCRAM-SHA-1', array(
      'authcid' => $this->connection->client->username,
      'secret' => $this->connection->client->password
    ));

    // unfortunately they same instace is needed, so the object is stored as class member would not work because of the timing
    $this->connection->storage->set('SCRAM_SHA_1_INSTANCE', $authSASL);
    $authString = base64_encode($authSASL->createResponse());
    $this->connection->send('<auth xmlns="%s" mechanism="SCRAM-SHA-1">%s</auth>', array(StreamHandler::XMPP_NAMESPACE_SASL, $authString));

    parent::onMount();
  }

  public function registerEvents()
  {
    /** @var \Fabiang\Sasl\Authentication\SCRAM $authSASL */
    $authSASL = $this->connection->storage->get('SCRAM_SHA_1_INSTANCE');
    $this->connection->storage->remove('SCRAM_SHA_1_INSTANCE');
    $this->on('challenge', function(Response $r, Connection $c) use ($authSASL) {
      $challengeNode = $r->get('challenge');
      if ($challengeNode->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_SASL) {
        $challenge = base64_decode($challengeNode->cdata);

        $response = $authSASL->createResponse($challenge);
        $response = base64_encode($response);

        $c->send('<response xmlns="%s">%s</response>', array(StreamHandler::XMPP_NAMESPACE_SASL, $response));
      }
    });

    $this->on('success', function(Response $r) use ($authSASL) {
      $cdata = $r->get('success')->cdata;
      if ($r->hasAttributeValue('xmlns', StreamHandler::XMPP_NAMESPACE_SASL)) {
        if (!$authSASL->verify(base64_decode($cdata))) {
          Logger::err('Server verification failed. Possible man in the middle attack. Abort!', true);
        }
      }
    });
  }

  public function registerTriggers() {}
}