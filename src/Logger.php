<?php
namespace XMPP;

class Logger
{
  public static $enabled = true;

  public static $allowExit = true;

  public static function log($msg, $type = 'INFO')
  {
    if (self::$enabled) {
      self::write(sprintf('%s %s: %s', '[Log]', $type, $msg));
    }
  }

  public static function err($msg, $exit = false)
  {
    if (self::$enabled) {
      self::write('[Err] '.$msg);
    }

    if ($exit && self::$allowExit) {
      exit;
    }
  }

  protected static function write($completeMsg)
  {
    print $completeMsg;
    print PHP_EOL;
  }
}