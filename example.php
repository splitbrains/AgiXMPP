<?php
require_once 'src/app.php';

use XMPP\Connection;

$xmpp = new XMPP(require_once 'config.php');
$xmpp->connect();

