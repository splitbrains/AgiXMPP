<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 06.01.12 01:11.
 */
namespace XMPP\EventHandlers;


use XMPP\Socket;
use XMPP\Connection;
use XMPP\ResponseObject;


use RecursiveIteratorIterator;
use RecursiveArrayIterator;

class EventObject
{
  /**
   * @var \XMPP\Socket;
   */
  protected $socket;

  /**
   * @var \XMPP\ResponseObject
   */
  protected $result;

  /**
   * @var \XMPP\Connection
   */
  protected $connection;

  public function __construct(Socket $socket, ResponseObject $result, Connection $conn)
  {
    $this->setSocket($socket);
    $this->setResult($result);
    $this->setConnection($conn);
  }

  public function setResult($array)
  {
    $this->result = $array;
  }

  public function getResult()
  {
    return $this->result;
  }

  public function setSocket($socket)
  {
    $this->socket = $socket;
  }

  public function getSocket()
  {
    return $this->socket;
  }

  public function setConnection($connection)
  {
    $this->connection = $connection;
  }

  public function getConnection()
  {
    return $this->connection;
  }
}