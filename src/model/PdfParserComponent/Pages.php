<?php

class Pages extends XmlRootElement
{
    protected $children = array('page' => array('multi' => true));

    public function __construct() {
        parent::__construct(null, null);
    }

    public function getName() {
        return 'pages';
    }
}