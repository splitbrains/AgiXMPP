<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 06.01.12 01:11.
 */
namespace XMPP\EventHandlers;


/**
 * All neccessary information for the event handlers are wrapped in here and given to the EventReceiver
 */
class EventObject
{
  /**
   * @var \XMPP\Socket;
   */
  protected $socket;

  /**
   * @var \XMPP\ResponseObject
   */
  protected $response;

  /**
   * @var \XMPP\Connection
   */
  protected $connection;

  /**
   * @param \XMPP\ResponseObject $result
   * @param \XMPP\Connection $conn
   */
  public function __construct(\XMPP\ResponseObject $result, \XMPP\Connection $conn)
  {
    $this->setResponse($result);
    $this->setConnection($conn);
    $this->setSocket($conn->getSocket());
  }

  public function setResponse($array)
  {
    $this->response = $array;
  }

  public function getResponse()
  {
    return $this->response;
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