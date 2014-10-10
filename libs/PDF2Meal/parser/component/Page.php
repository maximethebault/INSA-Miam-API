<?php

class Page extends XmlElement
{
    protected $children = array('layout',
                                'textBox' => array('multi' => true, 'accessor' => 'textBoxes', 'cache_attr' => 'id'),
                                'figure'  => array('multi' => true, 'accessor' => 'figures'),
                                'rect'    => array('multi' => true, 'accessor' => 'rects'));

    public function getName() {
        return 'page';
    }
}