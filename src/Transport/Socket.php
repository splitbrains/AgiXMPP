<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.15 10:35.
 */
namespace AgiXMPP\Transport;

use AgiXMPP\Logger;

class Socket implements TransportInterface
{
  const TIMEOUT = 10;

  /**
   * @var resource
   */
  private $socket = null;

  /**
   * @var bool
   */
  private $connected = false;

  /**
   * @param string $host
   * @param int $port
   * @return bool
   */
  public function open($host, $port)
  {
    $this->socket = stream_socket_client(sprintf('tcp://%s:%d', $host, $port), $errno, $errstr, self::TIMEOUT, STREAM_CLIENT_CONNECT);

    if ($this->socket) {
      stream_set_blocking($this->socket, 1);
      $this->connected = true;

      return true;
    }
    return false;
  }

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
   * @return string
   */
  public function read($bytes = 8192)
  {
    $buffer = fread($this->socket, $bytes);
    Logger::log($buffer, 'RECV');
    if (strlen($buffer) > 0) {
      return $buffer;
    }
  }

  /**
   * @param $data
   * @return bool
   */
  public function send($data)
  {
    Logger::log($data, 'SENT');
    $write = fwrite($this->socket, $data);

    return $write === strlen($data);
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
    $info = @stream_get_meta_data($this->socket);

    if (!$info || $info['timed_out']) {
      $this->connected = false;
      return true;
    }
    return false;
  }
}