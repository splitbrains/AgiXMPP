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
}