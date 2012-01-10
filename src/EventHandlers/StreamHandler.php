<?php
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;
use XMPP\Logger;

class StreamHandler implements EventReceiver
{
  protected $sessionId;
  protected $waitForSASL = false;
  protected $waitForAuthSuccess = false;
  protected $waitForBind = false;

  /**
   * @param $eventName
   * @param $that EventObject
   */
  public function onEvent($eventName, $that)
  {
    $response   = $that->getResponse();
    $connection = $that->getConnection();
    $socket     = $that->getSocket();


    switch($eventName) {
      case 'stream:stream':
        $this->sessionId = $response->getAttribute('id');
        // initiating stream
        // next: starttls
        break;

      case 'stream:features':
        if ($this->waitForSASL) {
          // as we are waiting for the SASL auth, there MUST NOT be any starttls tag in stream:features
          if (!$response->hasTag('starttls')) {
            if ($response->getAttributeFromTag('xmlns', 'mechanisms') == 'urn:ietf:params:xml:ns:xmpp-sasl') {
              $this->waitForSASL = false;

              $user = $connection->getUser();
              $pass = $connection->getPass();

              if (empty($user)) {
                $mechanism  = 'ANONYMOUS';
                $authString = '';
              } else {
                $mechanism  = 'PLAIN';
                $authString = base64_encode(chr(0).$user.chr(0).$pass);
              }

              $connection->send('<auth xmlns="urn:ietf:params:xml:ns:xmpp-sasl" mechanism="%s">%s</auth>', array($mechanism, $authString));
              $this->waitForAuthSuccess = true;
            }
          }
        }
        break;

      case 'starttls':
        $xmlns = $response->getAttribute('xmlns');

        if ($xmlns == 'urn:ietf:params:xml:ns:xmpp-tls') {
          $connection->send('<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>');
          // next: proceed
        }
        break;

      case 'proceed':
        if ($response->getAttribute('xmlns') == 'urn:ietf:params:xml:ns:xmpp-tls') {
          $socket->setCrypt(true);
          // we MUST send a new stream without creating a new TCP connection
          $connection->sendStart();
          $this->waitForSASL = true;
          // now we wait for the new stream response
          // next: stream:features -> MUST NOT contain starttls tag!

        }
        break;

      case 'success':
        if ($this->waitForAuthSuccess) {
          // we MUST send a new stream without creating a new TCP connection
          $connection->sendStart();
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
        if (!$response->hasTag('jid') && $response->getAttribute('xmlns') == 'urn:ietf:params:xml:ns:xmpp-bind') {
            $id = $connection->UID();
            $connection->send('<iq id="%s" type="set"><bind xmlns="urn:ietf:params:xml:ns:xmpp-bind"/></iq>', array($id));
            $connection->bindIdToEvent($id, 'bound', $this);
        }
        break;

      case 'bound':
        Logger::log('Successfully authed and connected');
        $jid = $response->getTag('jid');
        $connection->setJID($jid);
        $connection->setAuthStatus(true);
        break;
    }
  }
}