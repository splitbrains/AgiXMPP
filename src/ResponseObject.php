<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 06.01.12 13:29.
 */
namespace XMPP;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;

class ResponseObject
{
  /**
   * @var array
   */
  protected $response;

  /**
   * @var array
   */
  protected $original;

  /**
   * @param array $response
   */
  public function __construct(array $response)
  {
    $this->response = $this->original = $response;
  }

  protected function findRecursive($needle)
  {
    $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->response), RecursiveIteratorIterator::SELF_FIRST);

    foreach($iterator as $val) {
      $key = $iterator->key();

      if ($key === $needle) {
        return true;
      }
    }
    return false;
  }

  public function hasTag($tag)
  {
    return $this->findRecursive($tag);
  }

  /**
   * @param $event
   * @return bool
   */
  public function filter($event) {
    $haystack = $this->response;

    $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack), RecursiveIteratorIterator::SELF_FIRST);
    $needle_attr = $event.'_attr';

    $ret = array('values' => array(), 'attributes' => array());

    foreach ($iterator as $val) {
      $key = $iterator->key();

      if (!is_numeric($key)) {
        if ($key === $event) {
          $ret['values'] = $val;
        } elseif ($key == $needle_attr) {
          $ret['attributes'] = $val;
        }
      }
    }
    if (count($ret['values']) > 0 || count($ret['attributes']) > 0) {
      $this->response = $ret;
      return true;
    }
    return false;
  }

  public function getAttributes()
  {
    $result = $this->response;
    return $result['attributes'];
  }

  public function getAttribute($attr)
  {
    $result = $this->response;

    if (isset($result['attributes'][$attr])) {
      return $result['attributes'][$attr];
    }
    return false;
  }
}