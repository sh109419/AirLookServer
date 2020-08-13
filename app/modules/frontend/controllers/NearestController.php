<?php
declare(strict_types=1);

namespace AirLook\Modules\Frontend\Controllers;

use AirLook\AirData\mysql;
include_once APP_PATH . '/common/airdata/request.php';

class NearestController extends ControllerBase
{

/*
 * get airdata's nearest  by station id
 */
    public function idAction($stationID)
    {

        if (empty($stationID)) return false;

        $db = new mysql();

        // get data from db
        $data = $db->select_nearestdata($stationID);

        if ($data != NULL) {
            $temp = makeup_responsenearestdata($data);
            echo json_encode($temp);

        }

    }
}

