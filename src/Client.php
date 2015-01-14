<?php
namespace AgiXMPP;

class Client
{
  /**
   * @var string User for authentication
   */
  public $username;

  /**
   * @var string Password for authentication
   */
  public $password;

  /**
   * @var string
   */
  public $JID;

  /**
   * @var array
   */
  public $config = array();

  /**
   * @var bool
   */
  public $authStatus = false;

  /**
   * @var \AgiXMPP\Connection
   */
  private $connection;

  /**
   * @param array $config
   * @return \AgiXMPP\Client
   */
  public function __construct(array $config)
  {
    if (!isset($config['host'])) {
      Logger::err('Host not set, check your configuration', true);
    }

    if (!isset($config['username'])) {
      Logger::err('User name not set, check your configuration', true);
    }

    if (!isset($config['password'])) {
      Logger::err('User password not set, check your configuration', true);
    }

    $this->username = $config['username'];
    $this->password = $config['password'];

    foreach ($config as $key => $value) {
      $this->config[$key] = $value;
    }

    $this->connection = new Connection($this, $config['host'], $config['port']);
  }

  public function getConnection()
  {
    return $this->connection;
  }

  public function connect()
  {
    return $this->connection->connect();
  }

  public function isConnected()
  {
    return $this->connection->isConnected();
  }
}