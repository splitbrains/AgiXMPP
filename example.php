<?php
require_once 'XMPP.php';

use XMPP\EventHandlers\EventReceiver;

$config = require_once (!file_exists('config.mine.php') ? 'config.php' : 'config.mine.php');

$xmpp = new XMPP\Client($config);
$xmpp->connect();

$xmpp->init();


$xmpp->disconnect();