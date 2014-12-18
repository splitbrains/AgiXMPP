<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 18.01.12 11:31.
 */
namespace AgiXMPP;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use AgiXMPP\XML\Node;

class Response
{
  /**
   * @var RecursiveIteratorIterator
   */
  protected $iterator;

  /**
   * @var Node[]
   */
  private $nodes;

  /**
   * @param array $nodes
   */
  public function __construct($nodes = array())
  {
    $this->nodes = $nodes;

    $this->iterator = new RecursiveIteratorIterator(
      new RecursiveArrayIterator($nodes), RecursiveIteratorIterator::SELF_FIRST
    );
  }

  /**
   * @param $tag
   * @return \AgiXMPP\XML\Node
   */
  public function get($tag)
  {
    foreach($this->iterator as $node) {
      if ($node instanceof Node && $node->tag === $tag) {
        return $node;
      }
    }
    return new Node();
  }

  public function has($tag)
  {
    foreach($this->iterator as $node) {
      if ($node instanceof Node && $node->tag === $tag) {
        return true;
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

    foreach($this->iterator as $node) {
      if ($node instanceof Node && $node->tag === $tag) {
        $ret[] = $node;
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
    /** @var \AgiXMPP\XML\Node $node */
    foreach($this->iterator as $node) {
      if ($node instanceof Node && $node->attr($attr) === $val) {
        return true;
      }
    }
    return false;
  }

  /**
   * @return \AgiXMPP\XML\Node
   */
  public function getRoot()
  {
    $this->iterator->rewind();
    return $this->iterator->current();
  }
}