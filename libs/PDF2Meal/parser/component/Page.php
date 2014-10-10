<?php

class Page extends XmlElement
{
    protected $hasOne  = array('layout');
    protected $hasMany = array('textBox' => array('accessor' => 'textBoxes', 'cache_attr' => 'id'),
                               'figure'  => array('accessor' => 'figures'),
                               'rect'    => array('accessor' => 'rects'));

    // TODO: hasOne/hasMany for children?
    private $parsingObject;
    private $textBoxCache;
    private $textBoxes, $figures, $rects, $layout;

    public function __construct($parent, $attrs) {
        parent::__construct($parent);
        $this->textBoxCache = array();
    }

    public function getName() {
        return 'page';
    }

    protected function tagOpen($tag, $attrs) {
        if($this->parsingObject) {
            $this->parsingObject->tagOpen($tag, $attrs);
        }
        else {
            switch($tag) {
                case 'textbox':
                    $id = $attrs['id'];
                    if(array_key_exists($id, $this->textBoxCache)) {
                        $this->parsingObject = $this->textBoxCache[$id];
                    }
                    else {
                        $this->parsingObject = new TextBox($parent, $attrs);
                        $this->textBoxCache[$id] = $this->parsingObject;
                    }
                    break;
                case 'figure':
                    $this->parsingObject = new Figure($parent, $attrs);
                    break;
                case 'rect':
                    $this->parsingObject = new Rect($parent, $attrs);
                    break;
                case 'layout':
                    $this->parsingObject = new Layout($parent, $attrs);
                    break;
                default:
                    throw new Exception('Unexpected tag "' . $tag . '" at ' . $this->getName());
            }
        }
    }

    protected function tagData($data) {
        if($this->parsingObject) {
            $this->parsingObject->tagData($data);
        }
        else {
            throw new Exception('Unexpected data "' . $data . '" at ' . $this->getName());
        }
    }

    protected function tagClosed($tag) {
        if($this->parsingObject) {
            $this->parsingObject->tagClosed($tag);
        }
        else {
            if($tag != $this->parsingObject->getName()) {
                throw new Exception('No one handling closing tag "' . $tag . '" at ' . $this->getName());
            }
            else {
                $this->children[$this->parsingObject->getName()][] = $this->parsingObject;
                $this->parsingObject = null;
            }
        }
    }

    public function textBoxes() {
        return $this->children['textBoxes'];
    }
}