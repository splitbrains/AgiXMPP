<?php

require_once 'src/app.php';

use XMPP\Connection;

$config = array(
  'host' => 'jabber.fastit.net',
  'port' => 5222,
  'user' => 'daniel.lehr',
  'pass' => base64_decode('ZnJhbnphbGN0cmw='),
  'resource' => 'work',
  'server' => 'jabber.fastit.net',
);

$conn = new Connection($config);
$conn->connect();

//$conn->addEventListener();

//$conn->setCrypt(STREAM_CRYPTO_METHOD_SSLv2_CLIENT, true);


