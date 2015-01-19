<?php
namespace AgiXMPP\EventHandlers\Core\AuthMechanism;

use AgiXMPP\EventHandlers\Core\StreamHandler;
use AgiXMPP\EventHandlers\EventHandler;

/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.01.15 15:23.
 */
 
class PLAIN extends EventHandler
{
  public function onMount()
  {
    $c = $this->connection;
    $authString = base64_encode(
      $c->createSASLAuth('PLAIN', array('authcid' => $c->client->username, 'secret' => $c->client->password))
    );
    $this->connection->send('<auth xmlns="%s" mechanism="PLAIN">%s</auth>', array(StreamHandler::XMPP_NAMESPACE_SASL, $authString));

    parent::onMount();
  }

  public function registerEvents() {}
  public function registerTriggers() {}
}