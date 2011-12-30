<?php
namespace XMPP;

class Logger
{
  public static $prefix = '[Log]';

  public static $enabled = true;

  public static function Log($msg, $type = 'INFO')
  {
    if (self::$enabled) {
      printf('%s %s: %s', self::$prefix, $type, $msg);
      print PHP_EOL;
    }
  }
}