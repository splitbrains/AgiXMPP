<?php
namespace XMPP;

class XMLParser
{
  protected $xmlParser;

  public function __construct()
  {
    $this->xmlParser = xml_parser_create();

    xml_set_object($this->xmlParser, $this);
    xml_set_element_handler($this->xmlParser, 'startTag', 'endTag');
    xml_set_character_data_handler($this->xmlParser, 'cdata');
  }

  public function parse($data)
  {
    $values = array();
    xml_parse_into_struct($this->xmlParser, $data, $values);

    return $values;
  }

  protected function startTag($parser, $tag, $attributes)
  {

  }

  protected function endTag($parser, $tag)
  {

  }

  protected function cdata($parser, $cdata)
  {

  }
}