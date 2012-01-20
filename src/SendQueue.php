<?php
/**
 * @author Daniel Lehr, ADITION technologies AG, Freiburg, Germany. <daniel.lehr@adition.com>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.12 11:45.
 */
namespace XMPP;

interface SendQueue
{
  public function getMessages();
}

 
class DbMessages implements SendQueue
{
  public function getMessages()
  {

  }
}