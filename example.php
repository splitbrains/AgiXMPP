<?php
require_once 'XMPP.php';

$config = require_once (!file_exists('config.mine.php') ? 'config.php' : 'config.mine.php');

$xmpp = new XMPP\Client($config);
$xmpp->connect();

$xmpp->main();

//$xmpp->addEvent(function($xml) {}, );

//do {
  //$xmpp->waitForEvent(XMPP::HANDSHAKE);

  /*

  switch($xmpp->getEvent()) {
    case 'connection':
      echo 'successfully connected';

      $xmpp->send();
      break;
  }*/


//} while($xmpp->isConnected());

//$xmpp->disconnect();