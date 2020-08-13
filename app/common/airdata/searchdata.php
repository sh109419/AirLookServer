<?php


namespace AirLook\AirData;


class searchdata
{
    public $idx = -1;
    public $aqi = 0;
    public $station;
    public $stime;

    public function __construct($obj)
    {
        $this->idx = $obj->uid;
        $this->aqi = (Int)$obj->aqi;// sting to int
        $this->station = $obj->station->name;
        $this->stime = $obj->time->stime;
    }
}