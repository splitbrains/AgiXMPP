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
use AgiXMPP\EventHandlers\EventTrigger;

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

  const XMPP_TERMINATE_STREAM = '</stream:stream>';


  public function registerTriggers()
  {
    $this->onTrigger(EventTrigger::INIT_STREAM, function(Connection $c) {
      Logger::log('Starting stream');
      $conf = array($c->host, $c->client->user, StreamHandler::XMPP_PROTOCOL_VERSION, StreamHandler::XMPP_STREAM_NAMESPACE, StreamHandler::XMPP_STREAM_NAMESPACE_STREAM);
      $c->send('<stream:stream to="%s" from="%s" version="%s" xmlns="%s" xmlns:stream="%s">', $conf);
    });
  }

  public function registerEvents()
  {
    $this->on('stream:stream', function(Response $r, Connection $c) {
      $c->store('sessionId', $r->get('stream:stream')->attr('id'));
    });

    $this->on('stream:features', function(Response $r, Connection $c) {
      if ($c->fetch('waitForSASL') === true || $c->fetch('initialFeatures') == null) {
        $c->store('initialFeatures', true);
        // as we are waiting for the SASL auth, there MUST NOT be any starttls tag in stream:features
        if (!$r->get('stream:features')->has('starttls')) {
          if ($r->get('mechanisms')->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_SASL) {
            $c->store('waitForSASL', false);

            $user = $c->client->user;
            $pass = $c->client->pass;

            if (empty($user)) {
              $mechanism  = 'ANONYMOUS';
              $authString = '';
            } else {
              $mechanism  = 'PLAIN';
              $authString = base64_encode(chr(0).$user.chr(0).$pass);
            }

            $c->send('<auth xmlns="%s" mechanism="%s">%s</auth>', array(StreamHandler::XMPP_NAMESPACE_SASL, $mechanism, $authString));
            $c->store('waitForAuthSuccess', true);
          }
        }
      }
    });

    $this->on('session', function(Response $r, Connection $c) {
      if ($r->getByAttr('xmlns', StreamHandler::XMPP_NAMESPACE_SESSION)) {
        $c->store('hasSessionFeature', true);
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
        $c->trigger(EventTrigger::INIT_STREAM);
        $c->store('waitForSASL', true);
        // now we wait for the new stream response
        // next: stream:features -> MUST NOT contain starttls tag!
      }
    });

    $this->on('success', function(Response $r, Connection $c) {
      if ($c->fetch('waitForAuthSuccess') === true) {
        // we MUST send a new stream without creating a new TCP connection
        $c->trigger(EventTrigger::INIT_STREAM);
        $c->store('waitForAuthSuccess', false);
      }
    });

    $this->on('failure', function(Response $r, Connection $c) {
      if ($c->fetch('waitForAuthSuccess')) {
        $c->store('waitForAuthSuccess', false);
        Logger::err('Wrong user credentials!', true);
      }
    });

    $this->on('bind', array($this, 'onBind'));
  }

  public function onBind(Response $r, Connection $c)
  {
    $onSessionStart = function(Response $r, Connection $c) {
      if ($r->get('iq')->attr('type') == 'result') {
        Logger::log('Session started');
        $c->trigger(EventTrigger::ROSTER_GET);
      }
    };

    $onIqSet = function(Response $r, Connection $c) use ($onSessionStart) {
      if ($r->get('iq')->attr('type') == 'result') {
        Logger::log('Successfully authed and connected');

        $jid = $r->get('jid')->cdata;

        $c->client->JID = $jid;
        $c->client->authStatus = true;

        if ($c->fetch('hasSessionFeature') === true) {
          $c->send('<iq type="set"><session xmlns="%s"/></iq>', array(StreamHandler::XMPP_NAMESPACE_SESSION), true);
        }
      }
    };

    if (!$r->get('bind')->has('jid') && $r->get('bind')->attr('xmlns') == StreamHandler::XMPP_NAMESPACE_BIND) {
      $resource = $c->client->resource;

      if (!empty($resource)) {
        $binding = sprintf('<bind xmlns="%s"><resource>%s</resource></bind>', StreamHandler::XMPP_NAMESPACE_BIND, $resource);
      } else {
        $binding = sprintf('<bind xmlns="%s"/>', StreamHandler::XMPP_NAMESPACE_BIND);
      }

      $c->send('<iq type="set">%s</iq>', array($binding), true)
        ->onResponse($onIqSet)
        ->onResponse($onSessionStart);
    }
  }
}