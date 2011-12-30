<?php
namespace XMPP;

use XMPP\Logger;

class Socket
{
  const TIMEOUT = 20;

  /**
   * @var resource
   */
  protected $socket;


  public function getResource()
  {
    return $this->socket;
  }

  public function open($protocol, $host, $port)
  {
    $socket = stream_socket_client(sprintf('%s://%s:%d', $protocol, $host, $port), $errno, $errstr, self::TIMEOUT);

    if ($socket) {
      stream_set_timeout($socket, self::TIMEOUT);
      $this->socket = $socket;

      return true;
    }
    return false;
  }

  public function close()
  {
    fclose($this->socket);
  }

  public function read($bytes = 4096)
  {
    $buf = trim(fread($this->socket, $bytes));
    Logger::log($buf, 'RECV');
    return $buf;
  }

  public function write($data)
  {
    Logger::log($data, 'SENT');
    fwrite($this->socket, $data);
  }
}