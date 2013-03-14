<?php
namespace XMPP;

class Client
{
  /**
   * @var string User for authentication
   */
  public $user;

  /**
   * @var string Password for authentication
   */
  public $pass;

  /**
   * @var string The resource, which will be shown in the full JID (e.g. laptop, mobile, ..)
   */
  public $resource;

  /**
   * @var string
   */
  public $availability;

  /**
   * @var string
   */
  public $priority;

  /**
   * @var string
   */
  public $status;

  /**
   * @var string
   */
  public $JID;

  /**
   * @var bool
   */
  public $authStatus = false;

  /**
   * @var \XMPP\Connection
   */
  private $connection;

  /**
   * @param array $config
   * @return \XMPP\Client
   */
  public function __construct(array $config)
  {
    $this->user = $config['user'];
    $this->pass = $config['pass'];
    $this->status = $config['status'];
    $this->resource = $config['resource'];
    $this->priority = $config['priority'];
    $this->availability = $config['availability'];

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