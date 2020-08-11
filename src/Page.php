<?php


namespace falkemedia\PdfExtractor;


class Page
{
    public $number;
    public $text;


    /**
     * Page constructor.
     * @param $number
     * @param $text
     */
    public function __construct($number, $text)
    {
        $this->number = $number;
        $this->text = $text;
    }


}
