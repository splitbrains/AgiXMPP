<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.01.15 15:23.
 */
namespace AgiXMPP\EventHandlers\Core\AuthMechanism;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\Core\StreamHandler;
use AgiXMPP\EventHandlers\EventHandler;

class ANONYMOUS extends EventHandler
{
  public function onMount(Connection $c)
  {
    $this->connection->send('<auth xmlns="%s" mechanism="ANONYMOUS"/>', array(StreamHandler::XMPP_NAMESPACE_SASL));

    parent::onMount();
  }

  public function registerEvents() {}
  public function registerTriggers() {}
}