<?php
declare(strict_types=1);

namespace AirLook\Modules\Frontend\Controllers;


include_once APP_PATH . '/common/airdata/request.php';

class SearchController extends ControllerBase
{

/*
 * get airdata by station id
 */
    public function cityAction($city)
    {

        //extract post data
        if (empty($city)) return false;

        // return decode json from network
        $obj = request_searchdata($city);

        // modify data

        $t = makeup_responsesearchdata($obj);
        // encode json
        $json = json_encode($t);
        echo $json;

    }

}
