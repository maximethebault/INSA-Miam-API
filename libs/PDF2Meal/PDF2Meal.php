<?php

class PDF2Text
{
    private $pdfPath;

    public function __construct($pdfPath) {
        $this->pdfPath = $pdfPath;
    }

    /**
     *
     */
    public function parse() {
        return [new Page()];
    }
}