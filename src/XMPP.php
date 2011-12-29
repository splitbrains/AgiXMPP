<?php

use XMPP\Connection;

class XMPP
{

  protected $connection;

  public function __construct(array $config)
  {
    $this->connection = new Connection($config);
  }

  public function connect()
  {
    $this->connection->connect();
  }
}