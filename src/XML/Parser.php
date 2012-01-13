<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 13.01.12 10:59.
 */
namespace XMPP\XML;

use XMPP\XML\Node;

class Parser
{
  protected $depth = 0;
  protected $queue = array();
  protected $root  = '';

  protected $parser;

  public function __construct()
  {
    $parser = xml_parser_create();
    xml_set_object($parser, $this);

    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

    xml_set_element_handler($parser, 'tag_open', 'tag_close');
    xml_set_character_data_handler($parser, 'cdata');

    $this->parser = $parser;
  }

  public function parse($string)
  {
    return xml_parse($this->parser, $string);
  }

  public function isEmpty()
  {
    return count($this->getStructure()) == 0;
  }

  public function getStructure()
  {
    return $this->queue;
  }

  /**
   * @param Node $node
   */
  protected function setNode(Node $node)
  {
    $this->queue[$this->depth] = $node;
  }

  /**
   * @param int $offset
   *
   * @return \XMPP\XML\Node
   */
  protected function getNode($offset = 0)
  {
    return $this->queue[$this->depth + $offset];
  }

  protected function unsetNode()
  {
    unset($this->queue[$this->depth]);
  }

  protected function tag_open($parser, $tag, $attrs)
  {
    $this->depth++;

    if ($tag == $this->root) {
      $this->queue = array();
      $this->depth = 1;
    }

    $node = new Node();
    $node->tag = $tag;
    $node->attributes = $attrs;
    $node->depth = $this->depth;
    $this->setNode($node);

    if ($this->depth == 1) {
      $this->root = $tag;
    }
  }

  protected function tag_close($parser, $tag)
  {
    $node = $this->getNode();
    $node->tag_closed = 1;

    if ($this->depth > 2) {
      $parent = $this->getNode(-1);
      $parent->children[] = $node;
      $this->unsetNode();
    }

    $this->depth--;
  }

  protected function cdata($parser, $cdata)
  {
    $this->getNode()->cdata .= $cdata;
  }
}