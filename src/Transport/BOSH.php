<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.15 10:35.
 */
namespace AgiXMPP\Transport;

use AgiXMPP\Logger;

class BOSH implements TransportInterface
{
  /**
   * @var string
   */
  private $response = '';

  /**
   * Change the path if it differs
   *
   * @var string
   */
  public $path = 'http-bind';

  private $url;

  /**
   * @param string $host
   * @param int $port
   * @return bool
   */
  public function open($host, $port)
  {
    $this->url = sprintf('http://%s:%d/%s', $host, $port, $this->path);
    return true;
  }

  /**
   * @param string $data
   */
  public function send($data)
  {
    $handle = curl_init($this->url);
    curl_setopt($handle, CURLOPT_HEADER, 0);
    curl_setopt($handle, CURLOPT_POST, 1);

    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt($handle, CURLOPT_HTTPHEADER, array(
      'Accept-Encoding: gzip, deflate',
      'Content-Type: text/xml; charset=utf-8'
    ));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($handle, CURLOPT_VERBOSE, 1);

    curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($handle);

    $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    Logger::log($data, 'SENT');

    if ($statusCode != 200) {
      Logger::err("HTTP Status code $statusCode. Check validity of the sent data",  true);
    }

    if ($response === false) {
      Logger::err(sprintf("curl error (#%d): %s\n", curl_errno($handle), curl_error($handle)), true);
    }

    curl_close($handle);
    $this->response = $response;
  }

  public function close()
  {
    return true;
  }

  public function read()
  {
    $response = $this->response;
    if (strlen($response) > 0) {
      $this->response = '';
      Logger::log($response, 'RECV');

      return $response;
    }
    return false;
  }

  public function isConnected()
  {
    usleep(100000);
    return true;
  }

  public function hasTimedOut()
  {
    return false;
  }
}