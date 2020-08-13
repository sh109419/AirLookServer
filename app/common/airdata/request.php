<?php

error_reporting(0);

use AirLook\AirData\airdata;
use AirLook\AirData\nearest;
use AirLook\AirData\searchdata;

// remote server
define("SERVER", "https://api.waqi.info/");
define("TOKEN", $di->getShared('config')->path('aqicntoken'));

//class request
//{
/*
 *  functions for AirData
 */
    function get_airdata($station_id) {

        $obj = request_airdata($station_id);
        return parse_obs_en_json($obj);
    }

    function request_airdata($text) {
        if (empty($text)) return false;

        $url = SERVER."api/feed/@".$text."/obs.en.json";
        return perform_request($url);
    }

    // return decode json data
    function perform_request($url) {
        $json = curl_file_get_contents($url);
        $obj = json_decode($json);
        return $obj;
    }


    function curl_file_get_contents($durl){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
        //curl_setopt($ch, CURLOPT_REFERER,_REFERER_);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    function parse_obs_en_json($obj) {
        $data = $obj->rxs->obs[0];
        if(strcasecmp($data->status, "ok") != 0) return NULL;
        $msg = $data->msg;

        $d = new airdata(NULL);
        $d->idx = $msg->city->idx;
        if (is_numeric($msg->aqi)) {
            $d->aqi = $msg->aqi;
        }
        $d->city->name = $msg->city->name;
        //$d->city->name2 = $msg->i18n->name->zh-CN;结果为‘0’，估计是‘zh-CN’中的连字符影响了解析
        $d->city->name2 = getN2me($msg->i18n->name);
        //echo $d->city->name2;
        $d->dominentpol = "pm25";
        if ($msg->dominentpol != NULL) {
            $d->dominentpol = $msg->dominentpol;
        }
        $d->time->s = $msg->time->utc->s;
        $d->time->v = $msg->time->utc->v;
        $d->time->tz = $msg->time->utc->tz;

        // iaqi
        foreach ($msg->iaqi as $iaqi) {
            $p = $iaqi->p;
            $v = $iaqi->v[0];
            if (is_numeric($v)) {
                $d->iaqi->$p = $v;
            }
        }

        // forecast_aqi
        /* {"now":1542510000,"24hour":[[89,94],[100,121]],"daily":[[89,94],[100,121]]}
         3小时一条记录，确定第一条记录时间，后面的时间都可以推算出来

         data source:

         "utc": {
         "s": "2018-11-18 11:00:00", //local time
         "tz": "+08:00", // time zone = local time - utc timestamp
         "v": 1542510000 // utc timestamp
         },

         */
        $hour_list = array();
        $daily_list = array();
        $utc_timestamp = 0;
        $d->forecast_aqi->now = 0;
        $tz_int = strtotime($d->time->s) - $d->time->v;// integer value for timezone
        foreach ($msg->forecast->aqi as $forecast) {
            $t = $forecast->t;
            $v = $forecast->v;
            // get the timestamp of 1st record,
            // the following records are comulate from it by 3 hours per step
            if ($utc_timestamp == 0) {
                //first_record_time
                $utc_timestamp = strtotime($t);
            } else {
                // the timestamp of next record, coulate by 3 hours
                $utc_timestamp = $utc_timestamp + 3*60*60;
            }
            // filter 'past' records
            if ($d->time->v - $utc_timestamp >= 3*60*60) continue;

            // "now" -- the 1st local time of forecast records
            if ($d->forecast_aqi->now == 0) {
                $d->forecast_aqi->now = $utc_timestamp + $tz_int;
                $daily_min = $v[0];
                $daily_max = $v[1];
                $day_id = getdate($d->forecast_aqi->now)['yday'];
            }

            // from "now" on, 8 records
            if (count($hour_list) < 8) $hour_list[] = $v;

            // from "today" on, daily records
            /*how to get local datetime
             utc_timestamp + tz -> local date string
             */
            // if day changed
            $day_cur = getdate($utc_timestamp + $tz_int)['yday'];
            if ($day_id != $day_cur) {
                $daily_list[] = [$daily_min,$daily_max];
                $daily_min = $v[0];
                $daily_max = $v[1];
                $day_id = $day_cur;
            } else {
                if ($daily_min > $v[0]) $daily_min = $v[0];
                if ($daily_max < $v[1]) $daily_max = $v[1];
            }

        }
        // the last day
        $daily_list[] = [$daily_min,$daily_max];

        if ($d->forecast_aqi->now > 0) {// if now = 0, there are no forecast data
            $d->forecast_aqi->hour24 = $hour_list;
            $d->forecast_aqi->daily = $daily_list;
        }

        // nearest
        $nearestlist = array();
        foreach ($msg->nearest_v2 as $nearest) {
            // filter the record that aqi = '-'
            if (!is_numeric($nearest->aqi)) { continue; }

            $temp_nearest = new nearest(NULL);

            $temp_nearest->idx = $nearest->x;
            $temp_nearest->name = $nearest->name;
            //$temp_nearest->vtime = $nearest->t;
            $temp_nearest->vtime = 0;
            // "aqi": "158"
            $temp_nearest->aqi = (int)$nearest->aqi;

            //$geo = $nearest->geo
            $temp_nearest->latitude = (float)$nearest->g[0];
            $temp_nearest->longitude = (float)$nearest->g[1];

            $nearestlist[] = $temp_nearest;
        }

        return array($d, $nearestlist);

    }

    function  getN2me($names) {
        //因为key中包含‘-’，不能通过obj->key取值，
        //通过遍历方式取值
        foreach($names as $key=>$value)
        {
            if (strcasecmp($key, "zh-CN") == 0) return $value;
        }

        return "";
    }

//}


function makeup_responseairdata($data) {
    $t = new responseairdata(NULL);
    $t->status = "ok";
    $t->data = $data;
    return $t;
}

class responseairdata {
    public $status;
    public $data;

    public function __construct($obj) {
        $this->status = $obj->status;
        $this->data = new airdata($obj->data);
    }
}

/*
 * get station by location 30.6250145;104.0670559
 */
function request_stationid($text) {
    if (empty($text)) return false;
    $url = SERVER."feed/geo:".$text."/?token=".TOKEN;
    return perform_request($url);
}

/*
 * functions for nearest
 */
class responsenearestdata {// need to destory??????
    public $status;
    public $data = array();

    public function __construct($obj) {
        //$this->status = $obj->status;
        //$this->data = new nearest($obj->data);
    }
}

function makeup_responsenearestdata($data) {
    $t = new responsenearestdata(NULL);
    $t->status = "ok";
    $t->data = $data;
    return $t;
}


/*
 * functions for search
 */

function request_searchdata($text) {
    if (empty($text)) return false;
    $url = SERVER."search/?token=".TOKEN."&keyword=".$text;
    return perform_request($url);
}



// netwrok response data for searchbyname
//中文 和 英文 search结果不同？不知道aqicn的search api如何工作
class responsesearchdata {// need to destory??????
    public $status;
    public $data = array();

    public function __construct($obj) {
        $this->status = $obj->status;
        //$this->data = new searchdata($obj->data);
        foreach ($obj->data as $searchdata) {
            // filter unused record
            if (is_numeric($searchdata->aqi)) {// aqi is string in aqicn.com
                $this->data[] = new searchdata($searchdata);
            }
        }
    }
}

function makeup_responsesearchdata($data) {
    $t = new responsesearchdata($data);
    $t->status = "ok";
    //$t->data = $data;
    return $t;
}

/*
 * functions for settings
 */

class responsesettingresult {
    public $status;
    public $data;
}

function makeup_responsesettingresult($data) {
    $t = new responsesettingresult($data);
    $t->status = "ok";
    $t->data = new resultData();
    $t->data->result = $data;
    return $t;
}

class resultData {
    public $result;
}
