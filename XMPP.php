<?php
namespace XMPP;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

define('SRC_PATH', __DIR__ . '/src');
define('NS', __NAMESPACE__);


spl_autoload_register(function($class) {
  // remove the leading namespace
  if (substr($class, 0, strlen(NS)) == NS) {
    $class = substr($class, strlen(NS), strlen($class));
  }
  $file = SRC_PATH . str_replace('\\', '/', $class) . '.php';

  require_once $file;
});

$dir = new RecursiveDirectoryIterator(SRC_PATH, FilesystemIterator::CURRENT_AS_FILEINFO);
$it  = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);

/** @var $it \SplFileInfo */
foreach($it as $file => $obj) {
  if ($it->isFile() && $it->getExtension() == 'php') {
    require_once $file;
  }
}