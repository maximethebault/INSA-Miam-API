<?php

class Page extends XmlElement
{
    protected $children = array('layout',
                                'textBox' => array('multi' => true, 'cache_attr' => 'id'),
                                'figure'  => array('multi' => true),
                                'rect'    => array('multi' => true));

    public function getName() {
        return 'page';
    }
}