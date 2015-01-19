<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 13.01.15 11:21.
 */
namespace AgiXMPP\EventHandlers\XEP\INBAND_REGISTRATION_77;

use AgiXMPP\Logger;
use AgiXMPP\Response;
use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\EventHandler;
use AgixMPP\EventHandlers\Trigger;
use AgiXMPP\XML\Node;

class RegistrationHandler extends EventHandler
{
  const XMPP_JABBER_IQ_REGISTER = 'jabber:iq:register';

  /**
   * Register all events
   */
  public function registerEvents()
  {
    $registrationResponse = function(Response $r, Connection $c) {
      if ($r->has('error')) {
        Logger::err("An error occured while trying to register: {$r->get('error')->children[0]->tag} (Code: {$r->get('error')->attr('code')})", true);
      }
    };

    $registerInformation = function(Response $r, Connection $c) use ($registrationResponse) {
      if ($r->has('registered')) {
        Logger::err('User name already registered.', true);
      }

      $send = array();
      $send[] = '<iq type="set">';
      $send[] = sprintf('<query xmlns="%s">', RegistrationHandler::XMPP_JABBER_IQ_REGISTER);
      /** @var Node $node */
      foreach ($r->get('query')->children as $node) {
        if ($node->tag == 'instructions') {
          Logger::log('Registration instructions: ' . $node->cdata);
        } elseif (isset($c->client->config[$node->tag])) {
          $send[] = sprintf('<%s>%s</%s>', $node->tag, $c->client->config[$node->tag], $node->tag);
        }
      }
      $send[] = '</query>';
      $send[] = '</iq>';

      $c->send(implode('', $send), array(), true)
        ->onResponse($registrationResponse);
    };


    $this->on('stream:stream', function(Response $r, Connection $c) use ($registerInformation) {
      if (is_null($c->storage->get('sessionId'))) {
        $c->send('<iq type="get"><query xmlns="%s" /></iq>', array(RegistrationHandler::XMPP_JABBER_IQ_REGISTER), true, true)
          ->onResponse($registerInformation);
      }
    });
  }

  /**
   * Register all triggers (triggers are global)
   */
  public function registerTriggers()
  {
    return;
  }
}