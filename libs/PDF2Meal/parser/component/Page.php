<?php

class Page extends XmlElement
{
    private $parsingObject;
    private $textBoxCache;

    public function __construct($parent) {
        parent::__construct($parent);
        $this->textBoxCache = array();
    }

    public function getName() {
        return 'page';
    }

    protected function tagClosed($tag) {
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

    protected function tagData($data) {
        if($this->parsingObject) {
            $this->parsingObject->tagData($data);
        }
        else {
            throw new Exception('Unexpected data "' . $data . '" at root');
        }
    }

    protected function tagOpen($tag, $attrs) {
        if($this->parsingObject) {
            $this->parsingObject->tagOpen($tag, $attrs);
        }
        else {
            switch($tag) {
                case 'textbox':
                    $this->parsingObject = new TextBox($parent);
                    break;
                case 'rect':
                    $this->parsingObject = new Rect($parent);
                    break;
                case 'layout':
                    $this->parsingObject = new Layout($parent);
                    break;
                default:
                    throw new Exception('Unexpected tag "' . $tag . '" at root');
            }
        }
    }
}