<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.12 11:45.
 */
namespace XMPP;

use Iterator;
use ArrayAccess;

interface SendQueue
{
  public function getMessages();
  public function getItem();
  public function deleteItem();
}

 
class FileMessages implements SendQueue
{
  protected $file = '';

  protected $messages = array();

  protected $current = 0;

  public function __construct($file)
  {
    if (!is_writable($file)) {
      throw new \Exception('Unable to write the send queue file. Check permissions');
    }
    $this->file = $file;
    return true;
  }

  public function getItem()
  {
    if (isset($this->messages[$this->current])) {
      return $this->messages[$this->current++];
    }
    return false;
  }

  public function deleteItem()
  {
    echo 'deleting '.$this->messages[$this->current].' ('.$this->current.')';
    unset($this->messages[$this->current]);
    $this->messages = array_values($this->messages);
    $this->save();

    return true;
  }

  public function getMessages()
  {
    $this->messages = file($this->file);

    return $this->messages;
  }

  public function save()
  {
    file_put_contents($this->file, implode(PHP_EOL, $this->messages));
  }
}


function send($msg) {
  echo $msg.PHP_EOL;
}
/*
$sendQ = new FileMessages('../data/sendqueue.txt');

while(true) {
  $sendQ->getMessages();

  while($msg = $sendQ->getItem()) {
    send($msg);
    $sendQ->deleteItem();
  }

  usleep(500 * 1000);
}
*/

$queue = new \SplQueue();

foreach(file('../data/sendqueue.txt') as $line) {
  $queue->push($line);
}

foreach($queue as $msg) {
  send(trim($msg));
}