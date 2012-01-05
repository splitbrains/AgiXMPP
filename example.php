<?php
require_once 'XMPP.php';

$config = require_once (!file_exists('config.mine.php') ? 'config.php' : 'config.mine.php');

$xmpp = new XMPP\Client($config);
$xmpp->connect();

$xmpp->main();


/*
$joinRoom = new JoinRoom();

$xmpp->addEventHandler($joinRoom, 'presence', array('from' => 'sebastian.strzelec@jabber.fastit.net'));

class JoinRoom implements \XMPP\EventHandlers\EventReceiver {
  public function onEvent($event, $context)
  {
  }
}*/