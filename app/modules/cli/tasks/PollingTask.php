<?php
declare(strict_types=1);

namespace AirLook\Modules\Cli\Tasks;

use AirLook\AirData\mysql;
include_once APP_PATH . '/common/airdata/request.php';

//error_reporting(E_ALL);
/*
 * 系统每5分钟轮询获取空气质量数据；
 * 如果轮询开始的时候，上一轮轮询还没结束，那么，放弃当前轮询；
 * 轮询开始，set pollingstatus = true； 轮询结束 set pollingstatus = false
 * 轮询开始时，如果 pollingstatus == true，说明上一轮的轮询还未结束； set pollingstatus = false，确保下一次轮询可以进行； 放弃当前轮询
 */
class PollingTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $logger = $this->getDI()->getShared(
            'logger',
            ['logtype' => 'CLI']
        );

        // 设置轮询信号量
        $lockfile = BASE_PATH . '/public/task/task.lock';

        if (file_exists($lockfile)) {
            $status = file_get_contents($lockfile);
            if ($status==1) {
                file_put_contents($lockfile, 0);
                $logger->info("The polling is still running");
                return;
            }

        }

        file_put_contents($lockfile, 1);
        $logger->info("The polling begin");

        $db = new mysql();
        // the site update air data about 1 hour; so connect to site after 50 mins to check new data
        // the interval is 50 not 60, in order to keep data same as site as far as possible
        $select_sql = "select station_id, local_stamp from airdata where TIMESTAMPDIFF(MINUTE, last_modify_time, now()) > 50";
         //$select_sql = "select station_id, local_stamp from airdata where station_id=3303";

        $rows = $db->select($select_sql);
        $logger->info("select airdata count is " . count($rows));

        foreach ($rows as $row) {
            $station_id = $row['station_id'];
            // get data from network
            // get decode json from network
            list($obj,$nearest) = get_airdata($station_id);

            $logger->info("get airdata which station id is " . $station_id);

            if ($obj == NULL) continue;
            if ($obj->time->v <= $row['local_stamp']) continue;
            //update db if new data found
            $ret = $db->update_airdata($obj);
            $logger->info("update airdata which station id is " .$station_id);
            $db->update_nearest($station_id,$nearest);
            // ignore unexcepted AQI record
            $aqi = $obj->aqi;
            if ($aqi == -1) continue;

            $aqi_level = $this->get_aqi_level($aqi);


            // push notification to subscribers
            $subscribers = $db->select_subscriber($station_id);
            $logger->info("select " . count($subscribers) ." subscriber(s) whose station id is " .$station_id);

            foreach ($subscribers as $subscriber) {
                $alert_level = $subscriber['alert_level'];
                $recoveryenabled = $subscriber['recovery_enabled'];
                $alert_status = $subscriber['alert_status'];
                // push alert notification
                if (($aqi_level >= $alert_level) AND ($aqi_level > $alert_status)) {
                    $title = "Alert";
                    $pushresult = $this->pushto($subscriber['device_token'], $title, $aqi, $obj, true);
                    $logger->info("push Alert to " .$subscriber['device_token']);
                    // update status
                    $db->update_subscriber_status($subscriber['device_token'],$aqi_level);
                    // for developing
                    //pushto($subscriber['device_token'], $title, $aqi, $obj, false);
                }
                // push all clear notification
                if (($alert_status >= $alert_level) AND ($aqi_level < $alert_level)) {
                    // update status
                    $db->update_subscriber_status($subscriber['device_token'],$aqi_level);
                    // push clear to app if user agreed, however, alert alway push
                    if ($recoveryenabled) {
                        $title = "All Clear";
                        $pushresult = $this->pushto($subscriber['device_token'], $title, $aqi, $obj, true);
                        $logger->info("push All Clear to " .$subscriber['device_token']);
                        // for developing
                        //pushto($subscriber['device_token'], $title, $aqi, $obj, false);
                    }
                }

            }

        }
        $db->close();
        file_put_contents($lockfile, 0);
        $logger->info("The polling is closed");
    }

    private function get_aqi_level($aqi) {
        $level = 0;
        if ($aqi > 300) { $level = 5; }
        else if ($aqi > 200) { $level = 4; }
        else if ($aqi > 150) { $level = 3; }
        else if ($aqi > 100) { $level = 2; }
        else if ($aqi > 50) { $level = 1; }

        return $level;
    }

    // deviceToken, title, aqi, data, releasedversion
    private function pushto($deviceToken, $title, $aqi, $data, $releasedVersion) {
        // push notification include air data

        // Put your private key's passphrase here:
        $passphrase = '';

        // make sure the version
        if ($releasedVersion) {
            $apnsHost = 'ssl://gateway.push.apple.com:2195'; // official server
            $local_cert = CERT_PATH . '/airalert.pem';
        } else {
            $apnsHost = 'ssl://gateway.sandbox.push.apple.com:2195';// developing server
            $local_cert = CERT_PATH . '/airalert_developing.pem';
        }

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $local_cert);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        // Open a connection to the APNS server
        //$apnsHost = 'ssl://gateway.sandbox.push.apple.com:2195';// developing server
        //$apnsHost = 'ssl://gateway.push.apple.com:2195'; // official server
        $fp = stream_socket_client(
            $apnsHost, $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        //echo 'Connected to APNS' . PHP_EOL;

        //'loc-key' => 'the current AQI is %@ %@'
        $loc_key = $this->get_loc_key($aqi);

        // Create the payload body
        $basicBody['aps'] = array(
            'alert' => array(
                'title-loc-key' => $title,
                'loc-key' => $loc_key,
                'loc-args' => array($aqi)
            ),
            'sound' => 'default'
        );

        $customBody['data'] = $data;
        $body = array_merge($basicBody, $customBody);

        // Encode the payload as JSON
        $payload = json_encode($body);
        //echo $payload;
        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        /*if (!$result)
         echo 'Message not delivered' . PHP_EOL;
         else
         echo 'Message successfully delivered' . PHP_EOL;
         */
        // Close the connection to the server
        fclose($fp);

        return $result;
    }

    private function get_loc_key($aqi) {

        $apl_array = array("Good","Moderate","Unhealthy for Sensitive Groups","Unhealthy","Very Unhealthy","Hazardous");

        $base = 'the current AQI is %@ ';

        $aqi_level = $this->get_aqi_level($aqi);

        return $base . $apl_array[$aqi_level];

    }
}
