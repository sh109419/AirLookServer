
    
    
    /* 2019-11-20
     
     问题：保存印地语India (नई दिल्ली अमेरिकी दूतावास, India) 导致sql失败：ERROR 1366 (HY000): Incorrect string value:  '\xE0\xA4\xA8\xE0\xA4\x88...' for column 'station_name'
     原因：因为Mysql的utf8字符集是3字节的，而印地语是4字节，这样就无法存储了。
     方案：使用utf8mb4字符集
     步骤：1） vim /etc/my.cnf
     [mysqld]
     character-set-server=utf8mb4
     
     [mysql]
     default-character-set=utf8mb4
     
     [client]
     default-character-set=utf8mb4
     
     2) 修改配置文件后保存，并重启mysql服务
     
     service mysqld restart
     或
     service mysql restart
     
     3) 设置数据库、表、存储字段的编码格式：utf8mb4
     alter database APP_AIR_DB character set utf8mb4;
     alter table nearest character set utf8mb4;
     alter table nearest change station_name station_name VARCHAR(100) character set utf8mb4;
     alter table nearest change station_n2 station_n2 VARCHAR(100) character set utf8mb4;
     
     4) 修改其它中文字段
     alter table airdata character set utf8mb4;
     alter table airdata change station_name station_name VARCHAR(100) character set utf8mb4;
     alter table airdata change station_n2 station_n2 VARCHAR(100) character set utf8mb4;
     
     
     检查配置：
     SHOW VARIABLES WHERE Variable_name LIKE 'character_set_%' OR Variable_name LIKE 'collation%';
     检查字段
     SHOW FULL COLUMNS FROM nearest;
     
     */
    
    
    CREATE DATABASE APP_AIR_DB;

    USE APP_AIR_DB;

    // subscriber, settings, alert status
    CREATE TABLE IF NOT EXISTS subscriber (
    device_token VARCHAR(64) NOT NULL PRIMARY KEY,
    station_id INT(6) UNSIGNED NOT NULL,
    alert_level TINYINT UNSIGNED NOT NULL,
    recovery_enabled BOOLEAN,
    alert_status TINYINT NOT NULL DEFAULT -1,
    reg_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    
    // air data
    /*
     save iaqi(Individual AQI) as string in json format:
     {"pm10":28,"pm25":89,"so2":3.1}
     
     iaqi_humidity FLOAT,
    iaqi_no2 FLOAT,
    iaqi_presure FLOAT,
    iaqi_pm10 FLOAT,
    iaqi_pm25 FLOAT,
    iaqi_temp FLOAT,
    iaqi_wind FLOAT,
    iaqi_co FLOAT,
    iaqi_o3 FLOAT,
    iaqi_so2 FLOAT,
     
     forcast aqi: now & today
     {"now":1542510000,"24hour":[[89,94],[100,121]],"daily":[[89,94],[100,121]]}
     */
    
    CREATE TABLE IF NOT EXISTS airdata (
    station_id INT(6) UNSIGNED NOT NULL PRIMARY KEY,
    station_name VARCHAR(100),
    station_n2   VARCHAR(100),
    aqi INT,
    dominentpol VARCHAR(8),
    local_time VARCHAR(20),
    local_stamp INT,
    iaqi VARCHAR(256),
    forecast_now INT,
    aqi_24hour VARCHAR(128),
    aqi_daily VARCHAR(128),
    create_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_modify_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    
    // nearest for air data
    
    CREATE TABLE IF NOT EXISTS nearest (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    airdata_id INT(6) UNSIGNED NOT NULL,
    
    station_id INT(6) UNSIGNED NOT NULL,
    station_name VARCHAR(100),
    station_n2   VARCHAR(100),
    aqi INT,
    latitude FLOAT,
    longitude FLOAT,
    local_stamp INT
    );


