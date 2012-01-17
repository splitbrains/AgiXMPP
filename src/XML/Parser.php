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
  protected $pushRoot = false;

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
   * @param string $string
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
   * @param \XMPP\XML\Node $node
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

require_once 'Node.php';


$p = new Parser();

$s  = '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" from="styx" id="75c95e1c" xml:lang="en" version="1.0">';
$s .= '<stream:features><starttls xmlns="urn:ietf:params:xml:ns:xmpp-tls"></starttls><mechanisms xmlns="urn:ietf:params:xml:ns:xmpp-sasl"><mechanism>DIGEST-MD5</mechanism><mechanism>PLAIN</mechanism><mechanism>ANONYMOUS</mechanism><mechanism>CRAM-MD5</mechanism></mechanisms><compression xmlns="http://jabber.org/features/compress"><method>zlib</method></compression><auth xmlns="http://jabber.org/features/iq-auth"/><register xmlns="http://jabber.org/features/iq-register"/></stream:features>';

$s2  = '<iq to="bla@bla"><jid>abc@def</jid></iq>';
$s2 .= '<stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" from="styx" id="75c95e1c" xml:lang="en" version="1.0">';
$s2  .= '<presence/>';


$p->isValid($s);
print_r($p->getTree());

usleep(500 * 1000);

$p->isValid($s2);
print_r($p->getTree());

