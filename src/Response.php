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
  private $iterator;

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
   * Returns a node by tag name
   *
   * @param $tag
   * @return Node
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

  /**
   * Returns whether a node with a given tag name exists
   *
   * @param string $tag
   * @return bool
   */
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
   * Returns all nodes in a flat array
   *
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
   * Returns true whether a underlying node has a given attribute with a given value
   *
   * @param $attr
   * @param $val
   *
   * @return bool
   */
  public function hasAttributeValue($attr, $val)
  {
    /** @var Node $node */
    foreach($this->iterator as $node) {
      if ($node instanceof Node && $node->attr($attr) === $val) {
        return true;
      }
    }
    return false;
  }

  /**
   * Returns the root node
   *
   * @return Node
   */
  public function getRoot()
  {
    $this->iterator->rewind();
    return $this->iterator->current();
  }
}