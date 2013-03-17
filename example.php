<?php
require_once 'XMPP.php';

$config = require_once (!file_exists('config.mine.php') ? 'config.php' : 'config.mine.php');

$xmpp = new XMPP\Client($config);

// add your awesome event handlers somewhere here
if (!$xmpp->connect()) {
  die('Could not connect to server.');
}

while($xmpp->isConnected()) {

}