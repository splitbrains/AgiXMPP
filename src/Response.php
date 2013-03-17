<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 18.01.12 11:31.
 */
namespace XMPP;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use XMPP\XML\Node;

class Response
{
  /**
   * @var RecursiveIteratorIterator
   */
  protected $iterator;

  /**
   * @var array
   */
  private $plain;

  /**
   * @param array $nodes
   */
  public function __construct($nodes = array())
  {
    $this->plain = $nodes;
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

  public function has($tag)
  {
    foreach($this->iterator as $key => $node) {
      if ($node instanceof Node || is_numeric($key)) {
        if ($node->tag === $tag) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * @param string $tag
   * @return Node[]
   */
  public function getAll($tag)
  {
    $ret = array();

    foreach($this->iterator as $key => $node) {
      if ($node instanceof Node || is_numeric($key)) {
        if ($node->tag === $tag) {
          $ret[] = $node;
        }
      }
    }
    return $ret;
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