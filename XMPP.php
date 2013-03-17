<?php
namespace XMPP;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

define('DS', DIRECTORY_SEPARATOR);
define('SRC_PATH', __DIR__ .DS .'src');

spl_autoload_register(function($class) {
  // remove the leading namespace
  $class = substr($class, strlen(__NAMESPACE__), strlen($class));
  $file = SRC_PATH .str_replace('\\', DS, $class).'.php';

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