<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 14.01.15 15:30.
 */
namespace AgiXMPP\EventHandlers;

class Event
{
  /**
   * @var string
   */
  public $name;

  /**
   * @var callable
   */
  public $callback;

  /**
   * @var int
   */
  public $priority;

  /**
   * @param string $name
   * @param callable $callback
   */
  public function __construct($name, $callback)
  {
    $this->name = $name;
    $this->callback = $callback;
  }
}