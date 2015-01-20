<?php
/**
 * @author Daniel Lehr <daniel@agixo.de>
 * @internal-coding = utf-8
 * @internal UTF-Chars: ÄÖÜäöüß∆
 * created on 20.01.15 10:35.
 */
namespace AgiXMPP\Transport;

interface TransportInterface
{
  public function open($host, $port);
  public function send($data);
  public function close();
  public function read();
  public function isConnected();
  public function hasTimedOut();
}