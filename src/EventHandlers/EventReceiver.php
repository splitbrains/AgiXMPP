<?php

namespace XMPP\EventHandlers;

interface EventReceiver
{
  public function onEvent($eventName, $context);
}