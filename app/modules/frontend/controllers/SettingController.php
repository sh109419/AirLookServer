<?php
declare(strict_types=1);

namespace AirLook\Modules\Frontend\Controllers;

use AirLook\AirData\mysql;
include_once APP_PATH . '/common/airdata/request.php';

class SettingController extends ControllerBase
{

    public function indexAction()
    {
        echo "Setting API List:<br>";
        echo "localhost/api/setting/newToken/token<br>";
        echo "localhost/api/setting/changeStationID/3303/token<br>";
        echo "localhost/api/setting/changeAlertLevel/2/token<br>";
        echo "localhost/api/setting/changeRecoveryEnabled/false/token<br>";
    }

    public function newTokenAction($deviceToken)
    {
        //echo "localhost/api/setting/newToken/token<br>";
        if (empty($deviceToken)) return false;

        $db = new mysql();
        // save to db
        $db->new_subscriber($deviceToken, 3304, 2, 1);
        // check if device token in db
        $ret = $db->devicetoken_indb($deviceToken) ;
        $t = makeup_responsesettingresult($ret);
        // encode json
        $json = json_encode($t);
        echo $json;
    }

    public function changeStationIDAction($stationID, $deviceToken)
    {
        //echo "localhost/api/setting/changeStationID/3303/token<br>";
        if (empty($stationID)) return false;
        if (empty($deviceToken)) return false;
        $db = new mysql();
        $ret = $db->update_subscriber('station_id', $stationID, $deviceToken);
        $t = makeup_responsesettingresult($ret);
        // encode json
        $json = json_encode($t);
        echo $json;
    }

    public function changeAlertLevelAction($alertLevel, $deviceToken)
    {
        //echo "localhost/api/setting/changeAlertLevel/2/token<br>";
        //if (empty($stationID)) return false;
        if (empty($deviceToken)) return false;
        $db = new mysql();
        $ret = $db->update_subscriber('alert_level', $alertLevel, $deviceToken);
        $t = makeup_responsesettingresult($ret);
        // encode json
        $json = json_encode($t);
        echo $json;
    }

    public function changeRecoveryEnabledAction($recoveryEnabled, $deviceToken)
    {
        //echo "localhost/api/setting/changeRecoveryEnabled/true/token<br>";
        //if (empty($stationID)) return false;
        if (empty($deviceToken)) return false;

        $db = new mysql();
        $ret = $db->update_subscriber('recovery_enabled', $recoveryEnabled=='true'?1:0, $deviceToken);

        $t = makeup_responsesettingresult($ret);
        // encode json
        $json = json_encode($t);
        echo $json;
    }
}
