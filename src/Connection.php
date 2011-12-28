<?php
namespace XMPP;

class Connection
{
  protected $host;
  protected $port;
  protected $user;
  protected $pass;
  protected $resource;
  protected $server;

  protected $timeout = 30;
  protected $errno   = '';
  protected $errstr  = '';


  protected $connected = false;

  const CRYPT_SSLV2  = STREAM_CRYPTO_METHOD_SSLv2_CLIENT;
  const CRYPT_SSLV3  = STREAM_CRYPTO_METHOD_SSLv3_CLIENT;
  const CRYPT_SSLV23 = STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
  const CRYPT_TLS    = STREAM_CRYPTO_METHOD_TLS_CLIENT;

  protected $connection = null;

  public function __construct($config)
  {
    $this->setHost($config['host']);
    $this->setPort($config['port']);
    $this->setUser($config['user']);
    $this->setPass($config['pass']);
    $this->setResource($config['resource']);
    $this->setServer($config['server']);
  }

  public function connect()
  {
    $protocol = 'tcp';
    $socket = stream_socket_client(sprintf('tcp://%s:%d', $this->getHost(), $this->getPort()));

    if (!$socket) {
      return false;
    }

    $this->connection = $socket;
    $this->setConnected(true);
    return true;
  }

  protected function setConnected($state)
  {
    $this->connected = $state;
  }

  public function isConnected()
  {
    return $this->connected;
  }

  public function setCrypt($method, $activate = true)
  {
   if ($this->isConnected()) {
     stream_set_blocking($this->connection, true);
     stream_socket_enable_crypto($this->connection, $activate, $method);
     stream_set_blocking($this->connection, false);
   }
  }

  public function setHost($host)
  {
    $this->host = $host;
  }

  public function getHost()
  {
    return $this->host;
  }

  public function setPass($pass)
  {
    $this->pass = $pass;
  }

  public function getPass()
  {
    return $this->pass;
  }

  public function setPort($port)
  {
    if (is_numeric($port)) {
      $this->port = $port;
    }
  }

  public function getPort()
  {
    return $this->port;
  }

  public function setResource($resource)
  {
    $this->resource = $resource;
  }

  public function getResource()
  {
    return $this->resource;
  }

  public function setServer($server)
  {
    $this->server = $server;
  }

  public function getServer()
  {
    return $this->server;
  }

  public function setUser($user)
  {
    $this->user = $user;
  }

  public function getUser()
  {
    return $this->user;
  }

}