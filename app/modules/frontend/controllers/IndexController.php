<?php
declare(strict_types=1);

namespace AirLook\Modules\Frontend\Controllers;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        echo "API List:<br>";
        echo "localhost/api/setting/newToken/token<br>";
        echo "localhost/api/setting/changeStationID/3303/token<br>";
        echo "localhost/api/setting/changeAlertLevel/2/token<br>";
        echo "localhost/api/setting/changeRecoveryEnabled/false/token<br>";
        echo "localhost/api/airdata/id/3303<br>";
        echo "localhost/api/airdata/location/latitude;longitude<br>";
        echo "localhost/api/nearest/id/3303<br>";
        echo "localhost/api/search/city/shanghai<br>";

    }

}

