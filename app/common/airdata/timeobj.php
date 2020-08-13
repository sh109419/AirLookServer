<?php


namespace AirLook\AirData;


class timeobj
{
    public $s;
    public $v;

    public function __construct($obj)
    {
        $this->s = $obj->s;
        $this->v = $obj->v;
    }
}