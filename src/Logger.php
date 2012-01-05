<?php
namespace XMPP;

class Logger
{
  public static $enabled = false;

  public static function log($msg, $type = 'INFO')
  {
    if (self::$enabled) {
      self::write(sprintf('%s %s: %s', '[Log]', $type, $msg));
    }
  }

  public static function err($msg)
  {
    if (self::$enabled) {
      self::write('[Err] '.$msg);
    }
  }

  protected static function write($completeMsg)
  {
    print $completeMsg;
    print PHP_EOL;
  }
}