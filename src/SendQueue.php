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

abstract class SendQueue
{
}

 
class FileMessages extends SendQueue
{
  protected $file = '';

  public function __construct($file)
  {
    $this->file = $file;
  }
}

$sendQ = new FileMessages('../data/sendqueue.txt');

foreach($sendQ as $data) {
  //echo $sendQ->getItem();
  echo $data;


  //$xmpp->send($msg);

  $sendQ->deleteItem();
}