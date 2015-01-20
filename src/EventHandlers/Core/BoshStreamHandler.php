<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.15 12:21.
 */
namespace AgiXMPP\EventHandlers\Core;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\EventHandlers\Trigger;
use AgiXMPP\Logger;
use AgiXMPP\Response;

class BoshStreamHandler extends EventHandler
{
  const XMLNS_HTTP_BIND_URL = 'http://jabber.org/protocol/httpbind';
  const XMLNS_XMPP_BOSH = 'urn:xmpp:xbosh';
  const XMLNS_STREAM = 'http://etherx.jabber.org/streams';

  public function registerEvents()
  {
    $this->onTrigger(Trigger::INIT_STREAM, function(Connection $c) {
      Logger::log("Starting stream");
      $c->send('<body to="%s" rid="%d" xmlns="%s" xmpp:version="1.0" xmlns:xmpp="%s" xml:lang="en" wait="60" hold="1" content="text/xml; charset=utf-8" />', array(
        $c->host,
        ++$c->rid,
        BoshStreamHandler::XMLNS_HTTP_BIND_URL,
        BoshStreamHandler::XMLNS_XMPP_BOSH
      ));
    });
  }

  public function registerTriggers()
  {
    $this->on('stream:features', function(Response $r, Connection $c) {
      $c->sid = $r->get('body')->attr('sid');
      if ($r->get('mechanisms')->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_SASL) {
        $c->storage->set('waitForSASL', false);
        $client = $c->client;

        if (empty($client->username)) {
          $authMechanism = 'ANONYMOUS';
        } elseif (!isset($client->config['authMechanism'])) {
          $authMechanism = 'PLAIN';
        } else {
          $authMechanism = strtoupper($client->config['authMechanism']);
        }

        switch ($authMechanism) {
          case 'ANONYMOUS':
            $handler = new AuthMechanism\ANONYMOUS();
            break;
          case 'PLAIN':
            $handler = new AuthMechanism\PLAIN();
            break;
          case 'SCRAM-SHA-1':
            $handler = new AuthMechanism\SCRAM_SHA_1();
            break;
          case 'DIGEST-MD5':
            $handler = new AuthMechanism\DIGEST_MD5();
            break;
          default:
            Logger::err('No valid auth mechanism provided.', true);
        }

        $c->addEventHandler($handler);
      }
    });
  }
}