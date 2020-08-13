<?php


namespace AirLook\AirData;


class nearest
{
    public $idx;
    public $name;
    public $name2;
    public $aqi;
    public $latitude;
    public $longitude;
    public $vtime;

    public function __construct($obj)
    {
        $this->idx = $obj->idx;
        $this->name = $obj->name;
        $this->name2 = $obj->name2;
        $this->aqi = $obj->aqi;
        $this->latitude = $obj->latitude;
        $this->longitude = $obj->longitude;
        $this->vtime = $obj->vtime;
    }
}