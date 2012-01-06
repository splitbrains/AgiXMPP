<?php
namespace XMPP\EventHandlers;

use XMPP\EventHandlers\EventReceiver;

class StreamHandlers implements EventReceiver
{

  protected $sessionId;
  protected $waitForAuth = false;

  /**
   * @param $eventName
   * @param $that EventObject
   */
  public function onEvent($eventName, $that)
  {
    $result = $that->getResult();
    $socket = $that->getSocket();
    $connection = $that->getConnection();


    switch($eventName) {
      case 'stream:stream':
        $this->sessionId = $result->getAttribute('id');
        break;

      case 'stream:features':
        if ($this->waitForAuth) {
          if (!$result->hasTag('starttls')) {

          }
        }
        break;

      case 'starttls':
        $xmlns = $result->getAttribute('xmlns');

        if ($xmlns == 'urn:ietf:params:xml:ns:xmpp-tls') {
          $connection->send('<starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"/>');
        }
        break;

      case 'proceed':
        if ($result->getAttribute('xmlns') == 'urn:ietf:params:xml:ns:xmpp-tls') {
          $that->getSocket()->setCrypt(true);
          $that->getConnection()->sendStart();
          $this->waitForAuth = true;
        }
        break;
    }
  }
}