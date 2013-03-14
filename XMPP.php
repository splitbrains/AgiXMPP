<?php
namespace XMPP;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

define('SRC_PATH', __DIR__ . '/src');

$dir = new RecursiveDirectoryIterator(SRC_PATH, FilesystemIterator::CURRENT_AS_FILEINFO);
$it  = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);

/** @var $it \SplFileInfo */
foreach($it as $file => $obj) {
  if ($it->isFile() && $it->getExtension() == 'php') {
    require_once $file;
  }
}