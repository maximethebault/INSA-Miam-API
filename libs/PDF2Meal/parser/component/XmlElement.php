<?php

abstract class XmlElement
{
    private $parent;

    protected function __construct($parent) {
        $this->parent = $parent;
    }

    abstract public function getName();

    abstract protected function tagOpen($tag, $attrs);

    abstract protected function tagData($data);

    abstract protected function tagClosed($tag);
} 