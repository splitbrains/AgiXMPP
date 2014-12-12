<?php
use AgiXMPP\EventHandlers\IM\PresenceHandler;

return array(
  'host' => 'jabber.net',
  'port' => 5222,
  'user' => 'user',
  'pass' => 'pass',
  'resource' => 'laptop',
  'availability' => PresenceHandler::SHOW_STATUS_AWAY,
  'priority' => 0,
  'status' => '',
);