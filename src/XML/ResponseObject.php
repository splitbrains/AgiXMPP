<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 18.01.12 11:31.
 */
namespace XMPP\XML;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use XMPP\XML\Node;

class ResponseObject
{
  /**
   * @var RecursiveIteratorIterator
   */
  protected $iterator;

  /**
   * @param array $nodes
   */
  public function __construct(array $nodes)
  {
    $this->iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($nodes), RecursiveIteratorIterator::SELF_FIRST);
  }

  /**
   * @param $tag
   * @return \XMPP\XML\Node
   */
  public function get($tag)
  {
    foreach($this->iterator as $key => $node) {
      if ($node instanceof Node || is_numeric($key)) {
        if ($node->tag === $tag) {
          return $node;
        }
      }
    }
    return new Node();
  }

  /**
   * @param $attr
   * @param $val
   *
   * @return bool
   */
  public function getByAttr($attr, $val)
  {
    /** @var \XMPP\XML\Node $node */
    foreach($this->iterator as $key => $node) {
      if ($node instanceof Node || is_numeric($key)) {
        if ($node->attr($attr) === $val) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * @return \XMPP\XML\Node
   */
  public function getRoot()
  {
    $this->iterator->rewind();
    return $this->iterator->current();
  }
}