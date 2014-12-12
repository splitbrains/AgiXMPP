<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 13.01.12 16:40.
 */
namespace AgiXMPP\XML;

use AgiXMPP\Response;

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
  public function has($tag)
  {
    $r = new Response($this->children);
    if (!empty($r->get($tag)->tag)) {
      return true;
    }
    return false;
  }

  /**
   * @param string $tag
   * @return Node
   */
  public function get($tag)
  {
    $r = new Response($this->children);
    if (!empty($r->get($tag)->tag)) {
      return $r->get($tag);
    }
    return false;
  }

  /**
   * @return string
   */
  public function cdata()
  {
    return $this->cdata;
  }

  /**
   * @return array
   */
  public function children()
  {
    return $this->children;
  }
}