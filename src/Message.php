<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.03.13 13:23.
 */
namespace XMPP;

use XMPP\XML\Parser;
use XMPP\EventHandlers\EventReceiver;

use SimpleXMLElement;

class Message extends EventReceiver
{
  /**
   * @var callable
   */
  private $callback;

  /**
   * @param $message
   * @param \XMPP\Connection $connection
   * @param $awaitsResponse
   */
  public function __construct($message, Connection $connection, $awaitsResponse)
  {
    if ($awaitsResponse === true) {
      libxml_use_internal_errors(true);
      $xml = new SimpleXMLElement($message);

      if (!isset($xml->attributes()->id)) {
        $this->uid = $connection->UID();

        $xml->addAttribute('id', $this->uid);
        $message = trim(preg_replace(Parser::XML_DECL_REGEX, '', $xml->asXML()));
      }
      $connection->addEventHandler(array($xml->getName()), $this);
    }
    $connection->getSocket()->write($message);
  }

  /**
   * @param callable $callback
   */
  public function onResponse($callback)
  {
    $this->callback = $callback;
  }

  /**
   * @param string $event
   */
  public function onEvent($event)
  {
    if ($this->response->get($event)->attr('id') == $this->uid) {
      $cb = $this->callback;

      if ($cb instanceof EventReceiver) {
        $cb->onEvent($event);
      } elseif (is_callable($cb)) {
        $cb($this);
      }
    }
  }

  /**
   * @param string $trigger
   */
  public function onTrigger($trigger)
  {
    // TODO: Implement onTrigger() method.
  }
}