<?php

abstract class XmlElement
{
    private $parent;
    private $children;

    protected function __construct($parent) {
        $this->parent = $parent;
        $this->children = array();
    }

    abstract public function getName();

    abstract protected function tagOpen($tag, $attrs);

    abstract protected function tagData($data);

    abstract protected function tagClosed($tag);
} 