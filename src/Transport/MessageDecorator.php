<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.15 16:39.
 */
namespace AgiXMPP\Transport;

use AgiXMPP\Connection;
use AgiXMPP\EventHandlers\Core\BoshStreamHandler;
use AgiXMPP\Message;

class MessageDecorator
{
  private $decoratedMessage;

  public function __construct(Message $messageEvent, TransportInterface $transport, Connection $c)
  {
    $message = $messageEvent->preparedMessage;

    if ($transport instanceof BOSH && strpos($message, '<body') === false) {
      $message = vsprintf('<body sid="%s" rid="%s" xmlns="%s" xmlns:xmpp="%s" xmlns:stream="%s">%s</body>', array(
        $c->sid,
        $c->rid,
        BoshStreamHandler::XMLNS_HTTP_BIND_URL,
        BoshStreamHandler::XMLNS_XMPP_BOSH,
        BoshStreamHandler::XMLNS_STREAM,
        $message
      ));
    }

    $this->decoratedMessage = $message;
  }

  public function getMessage()
  {
    return $this->decoratedMessage;
  }
}