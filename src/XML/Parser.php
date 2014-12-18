<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 13.01.12 10:59.
 */
namespace AgiXMPP\XML;

use AgiXMPP\XML\Node;

class Parser
{
  /**
   * @var int
   */
  private $depth = 0;

  /**
   * @var array
   */
  private $tree = array();

  /**
   * @var string
   */
  private $root  = '';

  /**
   * @var \resource
   */
  private $parser;
  /**
   * @var \AgiXMPP\XML\Node;
   */
  protected $rootNode = null;

  const XML_DECL_REGEX = '/<\?xml.*?[^?>]\?>/i';

  /**
   *
   */
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

  /**
   * @param $string
   * @return bool
   */
  public function isValid($string)
  {
    // the XML parser, stops parsing when re-sent
    // https://bugs.php.net/bug.php?id=60792
    $string = preg_replace(self::XML_DECL_REGEX, '', $string);

    if (!$this->parse($string)) {
      return false;
    }
    // at least the second level has to be closed; that ensures that all information is gathered
    $tagClosed = isset($this->tree[2]) ? $this->tree[2]->tag_closed : true;
    return count($this->tree) > 0 && $tagClosed;
  }

  /**
   * @param string $string
   * @return int
   */
  protected function parse($string)
  {
    return xml_parse($this->parser, $string);
  }

  /**
   * @return array
   */
  public function getTree()
  {
    if ($this->hasRootNode()) {
      $rootNode = array(1 => $this->rootNode);
      $this->rootNode = null;
    } else {
      $rootNode = array(1 => new Node());
    }

    return $rootNode + $this->tree;
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
   * @return \AgiXMPP\XML\Node
   */
  protected function getNode($offset = 0)
  {
    if (isset($this->tree[$this->depth + $offset])) {
      return $this->tree[$this->depth + $offset];
    }
  }

  /**
   * @param $depth
   */
  protected function unsetNode($depth)
  {
    unset($this->tree[$depth]);
  }

  /**
   * @return boolean
   */
  public function hasRootNode()
  {
    return !is_null($this->rootNode);
  }

  /**
   * @param \resource $parser
   * @param string $tag
   * @param array $attrs
   */
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
      $this->rootNode = $node;
    }
  }

  /**
   * @param \resource $parser
   * @param string $tag
   */
  protected function tag_close($parser, $tag)
  {
    $node = $this->getNode();
    $node->tag_closed = true;

    if ($this->depth > 2) {
      $parent = $this->getNode(-1);
      $parent->children[] = $node;

      // don't return root depth, it will be added manually later if given
      $this->unsetNode(1);
      // no double flat nodes! (because they are pushed to their parents)
      $this->unsetNode($this->depth);
    }
    $this->depth--;
  }

  /**
   * @param \resource $parser
   * @param string $cdata
   */
  protected function cdata($parser, $cdata)
  {
    $this->getNode()->cdata .= $cdata;
  }
}
