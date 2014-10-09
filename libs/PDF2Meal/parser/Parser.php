<?php

/**
 * Class Parser
 *
 * Parses a PDF file and converts it into an object representation
 */
class Parser
{
    private $pdfPath;
    private $parsedPages;
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
        return $this->parsedPages;
    }

    private function tagOpen($parser, $tag, $attrs) {
        if($this->parsingObject) {
            $this->parsingObject->tagOpen($tag, $attrs);
        }
        else {
            switch($tag) {
                case 'pages':
                    $this->parsedPages = array();
                    break;
                case 'page':
                    $this->parsingObject = new Page();
                    break;
                default:
                    throw new Exception('Unexpected tag "' . $tag . '" at root');
            }
        }
    }

    private function tagData($parser, $data) {
        if($this->parsingObject) {
            $this->parsingObject->tagData($data);
        }
        else {
            throw new Exception('Unexpected data "' . $data . '" at root');
        }
    }

    private function tagClosed($parser, $tag) {
        switch($tag) {
            case 'pages':

                break;
            case 'page':
                array_push($this->parsedPages, $this->parsingObject);
                $this->parsingObject = null;
                break;
            default:
                if($this->parsingObject) {
                    $this->parsingObject->tagClosed($tag);
                }
                else {
                    throw new Exception('No one handling closing tag "' . $tag . '" at root');
                }
                break;
        }
    }
}