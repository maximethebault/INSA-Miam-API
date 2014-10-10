<?php

/**
 * Class Parser
 *
 * Parses a PDF file and converts it into an object representation
 */
class Parser
{
    private $pdfPath;
    private $pages;
    private $parsingObject;

    public function __construct($pdfPath) {
        $this->pdfPath = $pdfPath;
    }

    public function parse() {
        $xmlData = $this->pdfToXml();
        return $this->parseXml($xmlData);
    }

    private function pdfToXml() {
        $xmlUniqueName = uniqid() . '.xml';
        exec('pdf2txt.py -o ' . $xmlUniqueName . ' ' . $this->pdfPath);
        $xmlData = file_get_contents($xmlUniqueName);
        unlink($xmlUniqueName);
        return $xmlData;
    }

    /**
     * Parses a XML string into PHP objects
     *
     * @param $xmlData string the XML input string
     *
     * @throws Exception
     * @return array the different pages that were retrieved from the PDF
     */
    private function parseXml($xmlData) {
        $parser = xml_parser_create();
        xml_set_object($parser, $this);
        xml_set_element_handler($parser, 'tagOpen', 'tagClosed');
        xml_set_character_data_handler($parser, 'tagData');

        if(!xml_parse($parser, $xmlData)) {
            throw new Exception(sprintf("XML error: %s at line %d",
                                        xml_error_string(xml_get_error_code($parser)),
                                        xml_get_current_line_number($parser)));
        }

        xml_parser_free($parser);
        return $this->pages;
    }

    /**
     *
     * Called by the parser whenever a tag is opened at this or a deeper depth in the XML tree
     *
     * @param $parser  Object the parser
     * @param $tagName string the tag name of the opened XML tag
     * @param $attrs   array  the attributes of the opened XML tag
     *
     * @throws Exception
     */
    private function tagOpen($parser, $tagName, $attrs) {
        if($this->parsingObject) {
            $this->parsingObject->tagOpen($tagName, $attrs);
        }
        else {
            switch($tagName) {
                case 'pages':
                    $this->parsingObject = new Pages();
                    break;
                default:
                    throw new Exception('Unexpected tag "' . $tagName . '" at root');
            }
        }
    }

    /**
     * Called by the parser whenever a tag at this or a deeper depth in the XML tree has got data
     *
     * @param $parser  Object the parser
     * @param $data    string the data of the tag
     *
     * @throws Exception
     */
    private function tagData($parser, $data) {
        if($this->parsingObject) {
            $this->parsingObject->tagData($data);
        }
        else {
            throw new Exception('Unexpected data "' . $data . '" at root');
        }
    }

    /**
     * Called by the parser whenever a tag is closed at this or a deeper depth in the XML tree
     *
     * @param $parser  Object the parser
     * @param $tagName string the tag name of the closed XML tag
     *
     * @throws Exception
     */
    private function tagClosed($parser, $tagName) {
        if($this->parsingObject) {
            $this->parsingObject->tagClosed($tagName);
        }
        else {
            switch($tagName) {
                case 'pages':
                    $this->pages = $this->parsingObject;
                    break;
                default:
                    throw new Exception('No one handling closing tag "' . $tagName . '" at root');
                    break;
            }
        }
    }
}