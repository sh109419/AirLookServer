<?php


namespace AirLook\AirData;


class city
{
    public $name;
    public $name2;

    public function __construct($obj)
    {
        $this->name = $obj->name;
        $this->name2 = $obj->name2;
    }
}