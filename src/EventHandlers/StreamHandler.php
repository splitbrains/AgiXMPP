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
    $socket     = $that->getSocket();
    $connection = $that->getConnection();


    switch($eventName) {
      case 'stream:stream':
        $this->sessionId = $response->getAttribute('id');
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
        }
        break;

      case 'proceed':
        if ($response->getAttribute('xmlns') == 'urn:ietf:params:xml:ns:xmpp-tls') {
          $socket->setCrypt(true);
          // we MUST send a new stream without creating a new TCP connection
          $connection->sendStart();
          $this->waitForSASL = true;
          // now we wait for the new stream response
          // stream:features MUST NOT contain starttls now (see the case 'stream:features')
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
        // <bind> is inside <stream:features>
        if ($response->getAttributeFromTag('xmlns', 'bind') == 'urn:ietf:params:xml:ns:xmpp-bind') {
          $connection->setAuthStatus(true);
        }
        break;
    }
  }
}