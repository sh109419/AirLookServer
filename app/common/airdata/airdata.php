<?php


namespace AirLook\AirData;


class airdata
{
    public $idx = -1;
    public $aqi = -1;
    public $city;
    public $time;
    public $dominentpol = "pm25";
    public $iaqi; // Individual AQI
    public $forecast_aqi;
    // public $nearestlist = array();

    public function __construct($obj)
    {
        $this->idx = $obj->idx;
        if (is_numeric($obj->aqi)) {
            $this->aqi = $obj->aqi;
        }
        $this->city = new city($obj->city);
        $this->time = new timeobj($obj->time);
        $this->iaqi = $obj->iaqi;
        $this->forecast_aqi = new forecast_aqi($obj->forecast_aqi);
        //$this->nearest = new nearest($obj->nearest);
        //foreach ($obj->nearest as $nearest) {
        //  $this->nearestlist[] = new nearest($nearest);
        //}
    }
}