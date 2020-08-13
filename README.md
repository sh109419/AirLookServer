# AirLook Server/空气看看服务端

AirLook项目包括前端 iOS App [Air Look](https://github.com/sh109419/AirLook) 和服务端。
这是空气看看项目的服务端。
是一个使用Phalcon框架的多模块项目，包括了三个模块：

- `CLI` 用来刷新空气质量数据，后台使用
- `API` 提供给前端使用的API
- `Admin` 后台管理模块，供管理员使用（仅搭建模块结构）

### API List

* localhost/AirLook/setting/newToken/token
* localhost/AirLook/setting/changeStationID/3303/token
* localhost/AirLook/setting/changeAlertLevel/2/token
* localhost/AirLook/setting/changeRecoveryEnabled/false/token
* localhost/AirLook/airdata/id/3303
* localhost/AirLook/airdata/location/latitude;longitude
* localhost/AirLook/nearest/id/3303
* localhost/AirLook/search/city/shanghai

注意Linux系统是否大小写敏感

## 进入项目

### 要求

To run this application on your machine, you need at least:

* PHP >= 7.2
* Phalcon >= 4.0
* MySQL >= 5.5
* Apache Web Server with `mod_rewrite enabled`, and `AllowOverride Options` (or `All`) in your `httpd.conf` or Nginx Web Server

### 安装

1. Copy project to local environment 
2. Copy file `cp .env.example .env`
3. Edit .env file with your DB connection information & mail sent information & aqi token
4. Create DB - `airlook/db/schema.sql`
5. Install Composer - `curl -s http://getcomposer.org/installer | php`
6. Installing Dependencies via Composer - `composer install`
7. Set write permission - `airlook/public/logs  airlook/public/task`
8. Install your Apple Push Cetifications - `airlook/public/certificates/airalert.pem`
9. Modify Linux crontab - `airlook/bin/crontab`
10. Get your aqi Token from - `https://aqicn.org/data-platform/token/`
