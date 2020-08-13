<?php


namespace AirLook\AirData;


class forecast_aqi
{
    public $now;
    public $hour24;
    public $daily;

    public function __construct($obj)
    {
        $this->now = $obj->now;
        $this->hour24 = $obj->hour24;
        $this->daily = $obj->daily;
    }
}