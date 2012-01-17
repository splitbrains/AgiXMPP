<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

require_once './src/Socket.php';
require_once './src/Connection.php';
require_once './src/Client.php';
require_once './src/XML/Parser.php';
require_once './src/XML/Node.php';
require_once './src/XML/NodeList.php';
require_once './src/ResponseObject.php';
require_once './src/Logger.php';

foreach(glob('./src/EventHandlers/*.php') as $fileName) {
  require_once $fileName;
}