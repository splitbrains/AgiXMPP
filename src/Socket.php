<?php
namespace XMPP;

use XMPP\Logger;

class Socket
{
  const TIMEOUT = 10;

  /**
   * @var resource
   */
  protected $socket = null;

  /**
   * @var bool
   */
  protected $connected = false;

  /**
   * @return resource
   */
  public function getSocket()
  {
    return $this->socket;
  }

  /**
   * @param string $protocol
   * @param string $host
   * @param int $port
   * @param bool $persistent
   *
   * @return bool
   */
  public function open($protocol, $host, $port, $persistent = false)
  {
    $flags = STREAM_CLIENT_CONNECT;
    if ($persistent) {
      $flags |= STREAM_CLIENT_PERSISTENT;
    }

    $this->socket = stream_socket_client(sprintf('%s://%s:%d', $protocol, $host, $port), $errno, $errstr, self::TIMEOUT, $flags);

    if ($this->socket) {
      stream_set_timeout($this->socket, self::TIMEOUT);
      stream_set_blocking($this->socket, 1);
      $this->connected = true;

      return true;
    }
    return false;
  }

  /**
   *
   */
  public function close()
  {
    if ($this->connected) {
      fclose($this->socket);
      $this->socket = null;
      $this->connected = false;
    }
  }

  /**
   * @return bool
   */
  public function isConnected()
  {
    return $this->connected;
  }

  /**
   * @param int $bytes
   * @return bool|string
   */
  public function read($bytes = 8192)
  {
    $r = array($this->socket);
    $w = $e = $sec = null;

    $isUpdated = @stream_select($r, $w, $e, $sec);

    if ($isUpdated > 0) {
      $buf = trim(fread($this->socket, $bytes));
      if (strlen($buf) > 0) {
        Logger::log($buf, 'RECV');
        return $buf;
      }
    } elseif ($isUpdated === false) {
      Logger::err('Cannot read from stream.');
    }
    //return false;
  }

  /**
   * @param $data
   */
  public function write($data)
  {
    $w = array($this->socket);
    $r = $e = $sec = null;

    $mayWrite = @stream_select($r, $w, $e, $sec);

    if ($mayWrite > 0) {
      Logger::log($data, 'SENT');
      fwrite($this->socket, $data);
    } elseif($mayWrite === false) {
      Logger::err('Cannot write to stream.');
    }
  }

  /**
   * @param bool $activate
   */
  public function setCrypt($activate = true)
  {
    Logger::log('Enabling SSL v2/3');
    if (!stream_socket_enable_crypto($this->socket, $activate, STREAM_CRYPTO_METHOD_SSLv23_CLIENT)) {
      Logger::err('Server does not support SSL encryption.', true);
    }
  }

  /**
   * @return bool
   */
  public function hasTimedOut()
  {
    $info = @stream_get_meta_data($this->getSocket());

    if (!$info || $info['timed_out']) {
      $this->connected = false;
      return true;
    }
    return false;
  }
}