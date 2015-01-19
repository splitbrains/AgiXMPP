<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 09.01.12 17:06.
 */
namespace AgiXMPP\EventHandlers\Core;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgiXMPP\Logger;
use AgiXMPP\Response;
use AgiXMPP\EventHandlers\Trigger;

class StreamHandler extends EventHandler
{
  const XMPP_PROTOCOL_VERSION  = '1.0';
  const XMPP_STANDARD_LANG     = 'en';
  const XMPP_STREAM_NAMESPACE  = 'jabber:client';
  const XMPP_STREAM_NAMESPACE_STREAM = 'http://etherx.jabber.org/streams';
  const XMPP_NAMESPACE_SASL = 'urn:ietf:params:xml:ns:xmpp-sasl';
  const XMPP_NAMESPACE_TLS  = 'urn:ietf:params:xml:ns:xmpp-tls';
  const XMPP_NAMESPACE_BIND = 'urn:ietf:params:xml:ns:xmpp-bind';
  const XMPP_NAMESPACE_SESSION = 'urn:ietf:params:xml:ns:xmpp-session';
  const XMPP_SERVICE_NAME  = 'xmpp';

  const XMPP_TERMINATE_STREAM = '</stream:stream>';


  public function registerTriggers()
  {
    $this->onTrigger(Trigger::INIT_STREAM, function(Connection $c) {
      Logger::log('Starting stream');
      $conf = array($c->host, $c->client->username, StreamHandler::XMPP_PROTOCOL_VERSION, StreamHandler::XMPP_STREAM_NAMESPACE, StreamHandler::XMPP_STREAM_NAMESPACE_STREAM);
      $c->send('<stream:stream to="%s" from="%s" version="%s" xmlns="%s" xmlns:stream="%s">', $conf);
    });
  }


  public function registerEvents()
  {
    $this->on('stream:stream', function(Response $r, Connection $c) {
      $c->storage->set('sessionId', $r->get('stream:stream')->attr('id'));
    });

    $that = $this;

    $this->on('stream:features', function(Response $r, Connection $c) use ($that) {
      if ($c->storage->get('waitForSASL') === true || $c->storage->get('initialFeatures') == null) {
        $c->storage->set('initialFeatures', true);

        // as we are waiting for the SASL auth, there MUST NOT be any starttls tag in stream:features
        if (!$r->get('stream:features')->has('starttls')) {
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
        }
      }
    });

    $this->on('session', function(Response $r, Connection $c) {
      if ($r->hasAttributeValue('xmlns', StreamHandler::XMPP_NAMESPACE_SESSION)) {
        $c->storage->set('hasSessionFeature', true);
      }
    });

    $this->on('starttls', function(Response $r, Connection $c) {
      $xmlns = $r->get('starttls')->attr('xmlns');

      if ($xmlns == StreamHandler::XMPP_NAMESPACE_TLS) {
        $c->send('<starttls xmlns="%s"/>', array(StreamHandler::XMPP_NAMESPACE_TLS));
        // next: proceed
      }
    });

    $this->on('proceed', function(Response $r, Connection $c) {
      if ($r->get('proceed')->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_TLS) {
        $c->getSocket()->setCrypt(true);
        // we MUST send a new stream without creating a new TCP connection
        $c->trigger(Trigger::INIT_STREAM);
        $c->storage->set('waitForSASL', true);
        // now we wait for the new stream response
        // next: stream:features -> MUST NOT contain starttls tag!
      }
    });

    $this->on('success', function(Response $r, Connection $c) {
      if ($r->hasAttributeValue('xmlns', StreamHandler::XMPP_NAMESPACE_SASL)) {
        // we MUST send a new stream without creating a new TCP connection
        $c->trigger(Trigger::INIT_STREAM);
      }
    });

    $this->on('failure', function(Response $r) {
      if ($r->has('not-authorized')) {
        Logger::err('Wrong user credentials!', true);
      }
      if ($r->has('no-mechanism')) {
        Logger::err('No valid auth mechanism provided.');
      }
      Logger::err('Unspecified error. Check logs for details.', true);
    });

    $this->on('bind', array($this, 'onBind'));
  }

  public function onBind(Response $r, Connection $c)
  {
    $onSessionStart = function(Response $r, Connection $c) {
      if ($r->get('iq')->attr('type') == 'result') {
        Logger::log('Session started');
        $c->trigger(Trigger::ROSTER_GET);
      }
    };

    $onIqSet = function(Response $r, Connection $c) use ($onSessionStart) {
      if ($r->get('iq')->attr('type') == 'result') {
        Logger::log('Successfully authed and connected');

        $jid = $r->get('jid')->cdata;

        $c->client->JID = $jid;
        $c->client->authStatus = true;

        if ($c->storage->get('hasSessionFeature') === true) {
          $c->send('<iq type="set"><session xmlns="%s"/></iq>', array(StreamHandler::XMPP_NAMESPACE_SESSION), true)
            ->onResponse($onSessionStart);
        }
      }
    };

    if (!$r->get('bind')->has('jid') && $r->get('bind')->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_BIND) {
      $resource = $c->client->config['resource'];

      if (!empty($resource)) {
        $binding = sprintf('<bind xmlns="%s"><resource>%s</resource></bind>', StreamHandler::XMPP_NAMESPACE_BIND, $resource);
      } else {
        $binding = sprintf('<bind xmlns="%s"/>', StreamHandler::XMPP_NAMESPACE_BIND);
      }

      $c->send('<iq type="set">%s</iq>', array($binding), true)
        ->onResponse($onIqSet);
    }
  }
}