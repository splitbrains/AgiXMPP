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
  protected $tree = array();
  protected $root  = '';
  protected $parser;
  protected $showRoot = true;

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

  public function isValid($string)
  {
    if (!$this->parse($string)) {
      return false;
    }
    // at least the second level has to be closed; that ensures that all information is gathered
    $tagClosed = isset($this->tree[2]) ? $this->tree[2]->tag_closed : true;
    return count($this->tree) > 0 && $tagClosed;
  }

  /**
   * @param $string
   * @return int
   */
  protected function parse($string)
  {
    return xml_parse($this->parser, $string);
  }

  public function getTree()
  {
    return $this->tree;
  }

  /**
   * @param Node $node
   */
  protected function setNode(Node $node)
  {
    $this->tree[$this->depth] = $node;
  }

  /**
   * @param int $offset
   *
   * @return \XMPP\XML\Node
   */
  protected function getNode($offset = 0)
  {
    return $this->tree[$this->depth + $offset];
  }

  protected function unsetNode($depth)
  {
    unset($this->tree[$depth]);
  }

  protected function tag_open($parser, $tag, $attrs)
  {
    $this->depth++;

    $node = new Node();
    $node->tag = $tag;
    $node->attributes = $attrs;
    $node->depth = $this->depth;
    $this->setNode($node);

    if ($tag == $this->root) {
      $this->tree = array();
      $this->depth = 1;
    }

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

      // don't return root depth
      $this->unsetNode(1);
      // no double flat nodes! (because they are pushed to their parents)
      $this->unsetNode($this->depth);
    }
    $this->depth--;
  }

  protected function cdata($parser, $cdata)
  {
    $this->getNode()->cdata .= $cdata;
  }
}