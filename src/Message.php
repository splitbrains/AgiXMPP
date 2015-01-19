<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.03.13 13:23.
 */
namespace AgiXMPP;

use AgiXMPP\XML\Parser;
use AgiXMPP\EventHandlers\EventHandler;

use SimpleXMLElement;

/**
 * This class extends from EventHandler so that we can use the power of the event handling system.
 * This is particulary useful if you expect an answer from a message you just sent.
 * Simple example:
 *
 * $connection
 *   ->send('<iq type="set">something</iq>')
 *   ->onResponse(function($response, $connection) { ... });
 *
 * The message will automatically wrap it with an ID and when the server responds,
 * the registered callback will be called.
 * @package AgiXMPP
 */
class Message extends EventHandler
{
  /**
   * @var string
   */
  private $eventTag;

  /**
   * @var string
   */
  private $originalMessage;

  /**
   * @var string
   */
  public $preparedMessage;

  /**
   * @param $message
   * @param $awaitsResponse
   */
  public function __construct($message, $awaitsResponse)
  {
    $this->originalMessage = $this->preparedMessage = $message;
    if ($awaitsResponse === true) {
      libxml_use_internal_errors(true);
      $xml = new SimpleXMLElement($message);

      if (!isset($xml->attributes()->id)) {
        $this->uid = Connection::UID();
        $this->eventTag = $xml->getName();

        $xml->addAttribute('id', $this->uid);
        $this->preparedMessage = trim(preg_replace(Parser::XML_DECL_REGEX, '', $xml->asXML()));
      } else {
        $this->uid = $xml->attributes()->id;
      }
    }
  }

  /**
   * @param callable $callback
   * @return $this
   */
  public function onResponse($callback)
  {
    $uid = $this->uid;
    $this->on($this->eventTag, function(Response $r, Connection $c) use ($callback, $uid) {
      if ($r->hasAttributeValue('id', $uid)) {
        $c->invokeEvent($callback, array($r, $c));
      }
    });

    return $this;
  }

  public function registerTriggers() {}
  public function registerEvents() {}

  public function __toString()
  {
    return $this->preparedMessage;
  }
}