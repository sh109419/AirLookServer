<?php

namespace AirLook\AirData;

use AirLook\AirData\airdata;
use AirLook\AirData\nearest;
//use AirLook\Library\Mail as Mail;
use Phalcon\Di\Injectable;
    /**
     *  encapsulation of MySQL
     */
    
    class mysql extends Injectable
    {
    
        private $con = null;

        // connect DB
        
        /*
         *   *** MySQL server has gone away ***
         *
        原因：
         内存使用过高时将会挤掉数据库进程（占用内存最高的进程），导致服务挂断
         php占内存不断增加，导致系统关闭mysql（mysql占内存最大）
        优化：
         修改对应的php-fpm.conf中的就是max_requests，
         该值的意思是发送多少个请求后会重启该线程，我们需要适当降低这个值，用以让php-fpm自动的释放内存
       操作：
         vim /etc/php-fpm.d/www.conf
         set max_requests = 500 ? 60 * 12 * 24 (request count * 12/hour * 24/day)
         
         max_requests 不起作用，其他解决办法：
         
         1.检查php进程的内存占用，杀掉内存使用超额的进程
        ps aux|grep php-fpm|grep -v grep|awk '{if($4>=1)print $2}'
         /bin/bash /var/www/html/kill_php_fpm.sh
         -----------------------------------------

         */

        
        public function __construct() {

            $config = $this->config->database;
            
            $con = mysqli_connect(
                $config->host .":" .$config->port,
                $config->username,
                $config->password,
                $config->dbname
            );
            
            if (!$con)
            {
                //$this->mail->send("Connect MySql Server Failed", mysqli_connect_error());
                die("Connect Server Failed: " . mysqli_connect_error());
            }
            
            //php从mysql取出来的数据都是string类型，无论是主键int id还是float。因为php是弱类型的语言
            mysqli_options($con,MYSQLI_OPT_INT_AND_FLOAT_NATIVE,true);
            //需要注意的是：decimal类型的数据，即使有了以上的配置，依然还是输出为string类型。

            $this->con = $con;
        }
        
        public function __destruct()
        {
            if ($this->con != null) mysqli_close($this->con);
        }
        
        public function close()
        {
            mysqli_close($this->con);
            $this->con = null;
        }
        
        public function free_selected($result)
        {
            // 释放内存
            if ($result) {
                mysqli_free_result($result);
            }
        }
        ////////////////////////////// basic functions
        
        private function query($sql) {
            //var_dump($sql);
            //echo "<br/>";
            
            $ret = mysqli_query($this->con, $sql);
            if (!$ret) {
                $logger = $this->di->getShared('logger', ['logtype' => 'DB']);
                $logger->error(mysqli_error($this->con) ." SQL: ".$sql);
                //$mail = $this->di->getShared('mail');
                //$mail->send("MySql SQL error", $sql);
                //echo "SQL error: ".mysqli_error($this->con) ." SQL: ".$sql ."Date: ".date("Y-m-d H:i:s") ."\n" ;
            }
            return $ret;
        }
        
        public function multiquery($sqls) {
            
            if (mysqli_multi_query($this->con, $sqls))
            {
                do
                {
                    // 存储第一个结果集
                    if ($result = mysqli_store_result($this->con)) {
                        mysqli_free_result($result);
                    } else {
                       // send_email("MySql SQL error", $sqls);
                        //echo "SQL error: ".mysqli_error($this->con) ." SQL: ".$sqls ."Date: ".date("Y-m-d H:i:s") ."\n" ;
                    }
                   
                }
                while (mysqli_next_result($this->con));
            }
            
            return $result;
        }
        
        //create table
        public function create($sql) {
            return $this->query($sql);
        }
        
        // drop table
        public function drop($sql) {
            return $this->query($sql);
        }
        
        // select
        public function select($sql) {
            $ret = $this->query($sql);
            $arr = array();
            while ($row = mysqli_fetch_assoc($ret)) {
                $arr[] = $row;
            }
            $this->free_selected($ret);
            
            return $arr;
        }
        
        public function select1row($sql) {
            $ret = $this->query($sql);
            $row = mysqli_fetch_assoc($ret);
            $this->free_selected($ret);
            
            return $row;
        }
        
        // insert
        public function insert($sql) {
            return $this->query($sql);
        }

        // delete
        public function delete($sql) {
            return $this->query($sql);
        }
        
        // update
        public function update($sql) {
            return $this->query($sql);
        }
        
        
        ////////////////////////// utility functions
        
        // air data functions
        
        public function select_airdata($station_id) {
            $sql = "select * from airdata where station_id=".$station_id;
            $row = $this->select1row($sql);
            if ($row == NULL) return false;
            //var_dump($row);
            //echo "<br/>";
            $d = new airdata(NULL);
            $d->idx = $row['station_id'];
            $d->aqi = $row['aqi'];
            $d->city->name = $row['station_name'];
            $d->city->name2 = $row['station_n2'];
            $d->dominentpol = $row['dominentpol'];
            $d->time->s = $row['local_time'];
            $d->time->v = $row['local_stamp'];
            $d->iaqi = json_decode($row['iaqi']);
            $d->forecast_aqi->now = $row['forecast_now'];
            $d->forecast_aqi->hour24 = json_decode($row['aqi_24hour']);
            $d->forecast_aqi->daily = json_decode($row['aqi_daily']);
            
            return $d;
        }
        
        public function insert_airdata($data) {
            
            // statoin_name 可能包含特殊字符，需要转译
            $station_name = mysqli_real_escape_string($this->con, $data->city->name);
            $station_n2   = mysqli_real_escape_string($this->con, $data->city->name2);
            
            $sql = "insert ignore into airdata (
            station_id,
            station_name,
            station_n2,
            aqi,
            dominentpol,
            local_time,
            local_stamp,
            iaqi,
            forecast_now,
            aqi_24hour,
            aqi_daily
            ) values("
                     .$data->idx.","
                     ."'".$station_name."',"
                     ."'".$station_n2."',"
                     .$data->aqi.","
                     ."'".$data->dominentpol."',"
                     ."'".$data->time->s."',"
                     .$data->time->v.","
                     ."'". json_encode($data->iaqi) ."',"
                     .$data->forecast_aqi->now .","
                     ."'". json_encode($data->forecast_aqi->hour24) ."',"
                     ."'". json_encode($data->forecast_aqi->daily) ."'"
                     .")";
        
                     //echo $sql;
            return $this->insert($sql);
        }
                     
                     
        public function update_airdata($data) {
            // statoin_name 可能包含特殊字符，需要转译
            $station_name = mysqli_real_escape_string($this->con, $data->city->name);
            $station_n2   = mysqli_real_escape_string($this->con, $data->city->name2);
                     
            $sql = "update airdata set "
                    ."station_name=" ."'".$station_name."',"
                    ."station_n2=" ."'".$station_n2."',"
                    ."aqi=" . $data->aqi .","
                     ."dominentpol=" ."'".$data->dominentpol."',"
                     ."local_time=" ."'".$data->time->s."',"
                     ."local_stamp=" . $data->time->v .","
                     ."iaqi=" ."'". json_encode($data->iaqi) ."',"
                     ."forecast_now=" .$data->forecast_aqi->now .","
                     ."aqi_24hour=" ."'". json_encode($data->forecast_aqi->hour24) ."',"
                     ."aqi_daily=" ."'". json_encode($data->forecast_aqi->daily) ."'"
                     
                     ." where station_id = ".$data->idx;
                     
                     //echo $sql;
            return $this->update($sql);
                     
        }
           
        public function update_nearest($station_id,$data) {
              //delete all by id
              //insert all
            $sqls = "delete from nearest where airdata_id=" .$station_id.";";
            // multi insert
            $insert_sql = "insert into nearest (
                     airdata_id,
                     station_id,
                     station_name,
                     station_n2,
                     aqi,
                     latitude,
                     longitude,
                     local_stamp
                     ) values";
            $value_part = "";
            foreach ($data as $nearest) {
                     // statoin_name 可能包含特殊字符，需要转译
                     $station_name = mysqli_real_escape_string($this->con, $nearest->name);
                     $station_n2   = mysqli_real_escape_string($this->con, $nearest->name2);
                
                if ($value_part != "") {
                    $value_part .= ",";
                }
                
                $value_part .= "("
                              .$station_id.","
                              .$nearest->idx.","
                              ."'".$station_name."',"
                              ."'".$station_n2."',"
                              .$nearest->aqi.","
                              .$nearest->latitude.","
                              .$nearest->longitude.","
                              .$nearest->vtime
                              .")";
              
                    //$sqls .= $sql.";";
            }
            
            if ($value_part == "") { return false; }
            
            $sqls .= $insert_sql . $value_part;
            //echo $sqls;
            return $this->multiquery($sqls);
                     
        }
        
        public function select_nearestdata($station_id) {
            $sql = "select * from nearest where airdata_id=".$station_id;
            $rows = $this->select($sql);
            
            $nearest_list = array();
            foreach ($rows as $row) {
                $nearest = new nearest(NULL);
                $nearest->idx = $row['station_id'];
                $nearest->name = $row['station_name'];
                $nearest->name2 = $row['station_n2'];
                $nearest->aqi = $row['aqi'];
                $nearest->latitude = $row['latitude'];
                $nearest->longitude = $row['longitude'];
                $nearest->vtime = $row['local_stamp'];
                
                $nearest_list[] = $nearest;
            }
            
            return $nearest_list;
        }
        
        // subscriber functions

        public function devicetoken_indb($token) {//检查token是否在数据库中
            $sql = "select device_token from subscriber where device_token='".$token."'";
            $row = $this->select1row($sql);
            if ($row == NULL) return false;

            return true;
        }

        public function new_subscriber($token, $station_id, $level, $recovery) {
            // insert into subscriber (device_token,station_id) values(1,1) on duplicate key update station_id = 2;
            $sql = "insert into subscriber (
                                        device_token, 
                                        station_id, 
                                        alert_level, 
                                        recovery_enabled
                                        ) values('"
                                        .$token ."',"
                                        .$station_id .","
                                        .$level .","
                                        .$recovery .")" ;

            return $this->insert($sql);

        }

        public function update_subscriber($fieldkey, $fieldvalue, $token) {
            $sql = "update subscriber set alert_status = -1,"
                .$fieldkey ."=" .$fieldvalue
                ." where device_token = '" .$token ."'";
           // echo $sql;
            return $this->update($sql);
        }
                     
        public function update_subscriber_status($token, $status) {
            $sql = "update subscriber set alert_status = " .$status ." where device_token = '" .$token ."'";
            return $this->update($sql);
        }
                     
        public function select_subscriber($station_id) {
            $sql = "select * from subscriber where station_id=".$station_id;
            return $this->select($sql);
        }
                     
        // log functions
                     
        /*public function log($event, $content, $result) {
            $sql = "insert into log (event,content,result) values ('".$event."','".$content."','".$result."')";
            $this->insert($sql);
        }*/
                     

    }
    
?>

