<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 13.01.12 16:40.
 */
namespace XMPP\XML;

class Node
{
  public $tag = '';
  public $attributes = array();
  public $cdata = '';
  public $depth = 0;
  public $tag_closed = 0;
  public $children = array();

  /**
   * @param $attr
   * @return bool|array
   */
  public function attr($attr)
  {
    if (isset($this->attributes[$attr])) {
      return $this->attributes[$attr];
    }
    return false;
  }

  /**
   * @return array
   */
  public function attrs()
  {
    return $this->attributes;
  }

  /**
   * @param $tag
   * @return bool
   */
  public function hasSub($tag)
  {
    $r = new ResponseObject($this->children);
    if (!empty($r->get($tag)->tag)) {
      return true;
    }
    return false;
  }
}