<?php

namespace bigcat\conf;

class ConfigSub
{
    const XHPROF = 0;

    //测试数据库
    const DB_HOST ='';
    const DB_USERNAME = '';
    const DB_PASSWD = '';
    const DB_DBNAME = '';
    const DB_PORT = '';

    //微信公众号
    const WX_APPID = '';
    const WX_SECRET = '';
    const WX_APPID_PUBLIC = '';
    const WX_SECRET_PUBLIC = '';  
    
    //缓存
    const MC_SERVERS = array(array('127.0.0.1',11211));

    const TCP_ARR = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_IOS = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_PRE = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_H5 = [['211.159.159.159:7122','127.0.0.1:122']];

    //高仿DDOS
    const GAOFANG = 'gaofang_close';
    const TCP_ARR_DDOS = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_IOS_DDOS = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_PRE_DDOS = [['211.159.159.159:122','127.0.0.1:122']];
    const TCP_ARR_H5_DDOS = [['211.159.159.159:7122','127.0.0.1:122']];

    //后台系统地址
    const FAIR_AGENT_PATH="http://127.0.0.1/mahjong/game_agent/city_agent/index.php";
}