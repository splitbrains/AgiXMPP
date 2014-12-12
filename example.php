<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = require_once 'config.php';

$xmpp = new AgiXMPP\Client($config);

if (!$xmpp->connect()) {
  die('Could not connect to server.');
}

while ($xmpp->isConnected()) {
  // nothing to do here
}