<?php
namespace AgiXMPP\Utility;

/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 15.01.2015 14:08.
 */

class Storage
{
  /**
   * @var array
   */
  private $storage = array();

  /**
   * @param $key
   * @param $val
   */
  public function set($key, $val)
  {
    $this->storage[$key] = $val;
  }

  /**
   * @param $key
   */
  public function remove($key)
  {
    unset($this->storage[$key]);
  }

  /**
   * @param $key
   * @return bool
   */
  public function has($key)
  {
    return isset($this->storage[$key]);
  }

  /**
   * @param $key
   * @return mixed
   */
  public function get($key)
  {
    if (isset($this->storage[$key])) {
      return $this->storage[$key];
    }
  }
}