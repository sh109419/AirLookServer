<?php
declare(strict_types=1);

namespace AirLook\Modules\Frontend\Controllers;

use AirLook\AirData\mysql;
include_once APP_PATH . '/common/airdata/request.php';

//error_reporting(E_ALL);

class AirdataController extends ControllerBase
{

/*
 * get airdata by station id
 */
    public function idAction($stationID)
    {

        $db = new mysql();

        /*
         * 从数据库中读取天气数据
         * 如果数据库中不存在，则从aqicn.org获取
         * 后台task会自动刷新天气数据
         *
         */
        // get data from db
        $data = $db->select_airdata($stationID);
        if ($data != NULL) {
            $temp = makeup_responseairdata($data);
            echo json_encode($temp);
            return;
        }

        // get decode json from network, return is jsondata
        list($obj,$nearest) = get_airdata($stationID);
        if ($obj == NULL) {// try again
            list($obj,$nearest) = get_airdata($stationID);
            if ($obj == NULL) return 'try again';
        }

        // modify response data
        $t = makeup_responseairdata($obj);
        $json = json_encode($t);
        echo $json;

        // save record to db
        $db->insert_airdata($obj);
        $db->update_nearest($stationID,$nearest);

    }

/*
 * get airdata by location
 * the subscriber should be updated because the station changed after get airdata
 */
    public function locationAction($location)
    {

        // get decode json
        $obj = request_stationid($location);
        if(strcasecmp($obj->status, "ok") == 0) {
            // get station id
            $stationID = $obj->data->idx;
            // ivoke api cityfeed
            if (!empty($stationID)) {
                $this->idAction($stationID);
            }
        }

    }
}

