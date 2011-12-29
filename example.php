<?php

error_reporting(E_ALL);
ini_set('display_errors', true);


require_once 'src/app.php';

use XMPP\Connection;

$config = array(
  'host' => 'jabber.net',
  'port' => 5222,
  'user' => 'user',
  'pass' => 'pass',
  'resource' => 'laptop',
  'server' => 'jabber.net',
);


$conn = new Connection($config);
$conn->connect();



//$conn->addEventListener();

//$conn->setCrypt(STREAM_CRYPTO_METHOD_SSLv2_CLIENT, true);


