<?php

class Pages extends XmlElement
{
    protected $children = array('page' => array('multi' => true, 'accessor' => 'pages'));

    public function getName() {
        return 'pages';
    }
}