<?php
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once './src/Socket.php';
require_once './src/SocketMock.php';
require_once './src/Connection.php';
require_once './src/Client.php';
require_once './src/XMLParser.php';
require_once './src/Logger.php';

foreach(glob('./src/EventHandlers/*.php') as $fileName) {
  require_once $fileName;
}