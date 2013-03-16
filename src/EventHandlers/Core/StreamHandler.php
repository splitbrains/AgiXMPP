<?php
namespace XMPP\EventHandlers;

use XMPP\Handler;
use XMPP\EventHandlers\EventReceiver;
use XMPP\Logger;

class StreamHandler extends EventReceiver
{
  protected $sessionId;
  protected $waitForSASL = false;
  protected $waitForAuthSuccess = false;
  protected $waitForBind = false;
  protected $hasSessionFeature = false;

  const XMPP_PROTOCOL_VERSION  = '1.0';
  const XMPP_STANDARD_LANG     = 'en';
  const XMPP_STREAM_NAMESPACE  = 'jabber:client';
  const XMPP_STREAM_NAMESPACE_STREAM = 'http://etherx.jabber.org/streams';
  const XMPP_NAMESPACE_SASL = 'urn:ietf:params:xml:ns:xmpp-sasl';
  const XMPP_NAMESPACE_TLS  = 'urn:ietf:params:xml:ns:xmpp-tls';
  const XMPP_NAMESPACE_BIND = 'urn:ietf:params:xml:ns:xmpp-bind';
  const XMPP_NAMESPACE_SESSION = 'urn:ietf:params:xml:ns:xmpp-session';

  const XMPP_TERMINATE_STREAM = '</stream:stream>';

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    if ($trigger == TRIGGER_INIT_STREAM) {
      $conf = array($this->connection->host, $this->client->user, self::XMPP_PROTOCOL_VERSION, self::XMPP_STREAM_NAMESPACE, self::XMPP_STREAM_NAMESPACE_STREAM);
      $this->connection->send('<stream:stream to="%s" from="%s" version="%s" xmlns="%s" xmlns:stream="%s">', $conf);
    }
  }

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
    $response   = $this->response;
    $connection = $this->connection;
    $socket     = $this->socket;
    $client     = $this->client;

    switch($event) {
      case 'stream:stream':
        $this->sessionId = $response->get('stream:stream')->attr('id');
        // initiating stream
        // next: starttls
        break;

      case 'stream:features':
        if ($this->waitForSASL) {
          // as we are waiting for the SASL auth, there MUST NOT be any starttls tag in stream:features
          if (!$response->get('stream:features')->has('starttls')) {
           if ($response->get('mechanisms')->attr('xmlns') == self::XMPP_NAMESPACE_SASL) {
              $this->waitForSASL = false;

              $user = $client->user;
              $pass = $client->pass;

              if (empty($user)) {
                $mechanism  = 'ANONYMOUS';
                $authString = '';
              } else {
                $mechanism  = 'PLAIN';
                $authString = base64_encode(chr(0).$user.chr(0).$pass);
              }

              $connection->send('<auth xmlns="%s" mechanism="%s">%s</auth>', array(self::XMPP_NAMESPACE_SASL, $mechanism, $authString));
              $this->waitForAuthSuccess = true;
            }
          }
        } else {
          $session = $response->get('session')->attr('xmlns');

          if ($session == self::XMPP_NAMESPACE_SESSION) {
            $this->hasSessionFeature = true;
          }
        }
        break;

      case 'starttls':
        $xmlns = $response->get('starttls')->attr('xmlns');

        if ($xmlns == self::XMPP_NAMESPACE_TLS) {
          $connection->send('<starttls xmlns="%s"/>', array(self::XMPP_NAMESPACE_TLS));
          // next: proceed
        }
        break;

      case 'proceed':
        if ($response->get('proceed')->attr('xmlns') == self::XMPP_NAMESPACE_TLS) {
          $socket->setCrypt(true);
          // we MUST send a new stream without creating a new TCP connection
          $this->trigger(TRIGGER_INIT_STREAM);
          $this->waitForSASL = true;
          // now we wait for the new stream response
          // next: stream:features -> MUST NOT contain starttls tag!
        }
        break;

      case 'success':
        if ($this->waitForAuthSuccess) {
          // we MUST send a new stream without creating a new TCP connection
          $this->trigger(TRIGGER_INIT_STREAM);
          $this->waitForAuthSuccess = false;
        }
        break;

      case 'failure':
        if ($this->waitForAuthSuccess) {
          $this->waitForAuthSuccess = false;
          Logger::err('Wrong user credentials!', true);
        }
        break;

      case 'bind':
        if (!$response->get('bind')->has('jid') && $response->get('bind')->attr('xmlns') == self::XMPP_NAMESPACE_BIND) {
          $resource = $client->resource;
          if (!empty($resource)) {
            $binding = sprintf('<bind xmlns="%s"><resource>%s</resource></bind>', self::XMPP_NAMESPACE_BIND, $resource);
          } else {
            $binding = sprintf('<bind xmlns="%s"/>', self::XMPP_NAMESPACE_BIND);
          }

          $hasSessionFeature = $this->hasSessionFeature;

          $connection
            ->send('<iq type="set">%s</iq>', array($binding), true)
            ->onResponse(function($h) use ($hasSessionFeature) {
              if ($h->response->get('iq')->attr('type') == 'result') {
                Logger::log('Successfully authed and connected');

                $jid = $h->response->get('jid')->cdata;

                $h->client->JID = $jid;
                $h->client->authStatus = true;

                if ($hasSessionFeature) {
                  $h->connection
                    ->send('<iq type="set"><session xmlns="%s"/></iq>', array(self::XMPP_NAMESPACE_SESSION), true)
                    ->onResponse(function($h) {
                      if ($h->response->get('iq')->attr('type') == 'result') {
                        Logger::log('Session started');
                        $h->trigger(TRIGGER_ROSTER_GET);
                      }
                    });
                }
              }
          });
        }
        break;

//      case 'custom_bind_event':
//        if ($response->get('iq')->attr('type') == 'result') {
//          Logger::log('Successfully authed and connected');
//
//          $jid = $response->get('jid')->cdata;
//
//          $client->JID = $jid;
//          $client->authStatus =true;
//
//          if ($this->hasSessionFeature) {
//            $id = $connection->UID();
//            $connection->send('<iq id="%s" type="set"><session xmlns="%s"/></iq>', array($id, self::XMPP_NAMESPACE_SESSION));
//            $connection->addIdHandler($id, 'session_started', $this);
//          }
//        }
//        break;
//      case 'session_started':
//        if ($response->get('iq')->attr('type') == 'result') {
//          Logger::log('Session started');
//          $this->trigger(TRIGGER_ROSTER_GET);
//        }
//        break;
    }
  }
}