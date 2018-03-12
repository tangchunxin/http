<?php

namespace bigcat\conf;

use bigcat\conf\ConfigSub;

class Config
{
    const XHPROF = ConfigSub::XHPROF;

    //测试数据库
    const DB_HOST = ConfigSub::DB_HOST;
    const DB_USERNAME = ConfigSub::DB_USERNAME;
    const DB_PASSWD = ConfigSub::DB_PASSWD;
    const DB_DBNAME = ConfigSub::DB_DBNAME;
    const DB_PORT = ConfigSub::DB_PORT;

    //IP
    const TCP_ARR = ConfigSub::TCP_ARR;
    const TCP_ARR_IOS = ConfigSub::TCP_ARR_IOS;
    const TCP_ARR_PRE = ConfigSub::TCP_ARR_PRE;
    const TCP_ARR_H5 = ConfigSub::TCP_ARR_H5;

    //高防ddos
    const GAOFANG = ConfigSub::GAOFANG;
    const TCP_ARR_DDOS = ConfigSub::TCP_ARR_DDOS;
    const TCP_ARR_IOS_DDOS = ConfigSub::TCP_ARR_IOS_DDOS;
    const TCP_ARR_PRE_DDOS = ConfigSub::TCP_ARR_PRE_DDOS;
    const TCP_ARR_H5_DDOS = ConfigSub::TCP_ARR_H5_DDOS;

    const FAIR_AGENT_PATH = ConfigSub::FAIR_AGENT_PATH;
    const MC_SERVERS = ConfigSub::MC_SERVERS;

    //微信公众号
    const WX_APPID = ConfigSub::WX_APPID;
    const WX_SECRET = ConfigSub::WX_SECRET;
    const WX_APPID_PUBLIC = ConfigSub::WX_APPID_PUBLIC;
    const WX_SECRET_PUBLIC = ConfigSub::WX_SECRET_PUBLIC;
    
    
    //版本号
    const C_VERSION = '2.0.5';	//android release version
    const C_VERSION_PRE = '2.0.6';	//android test version
    const C_VERSION_IOS_PRE = '2.0.6';	//ios test version
    const C_VERSION_IOS = '2.0.5';	//ios release version
    const C_VERSION_IOS_LAST = '2.0.4';	//ios last version
    const C_VERSION_CHECK = true;	//

    const RULE_VERSION = 2;  //rule version
    const FILTER_WORD_VERSION = 2;  //filter word version

    const NO_LOG_GAME_TYPE = [331,341];

    const NO_CHECK_IP = 1;	//app store sale

    const GAME_TYPE = 1; 	//1 SiChuan XueZhan ; 2 ShaanXi GuanZhong ; 4 ChengDe ;5 baoding ; 6 lishui; 10 dezhou ; 20 xinji
    const DEBUG = false;    //release version must false
   	const FIRST_CURRENCY = 8;
    const SHARE_CURRENCY = 3;
    const IP_CRYPT = 1; //crypt tcp_s
    const PASS_KEY = 'NCBDLinfiy';

   	//const LANGUAGE = 'cn';
    const PLATFORM = 'gfplay';

    const API_KEY = 'NCBDpay';

    const RPC_KEY = 'gfplay is best gfplay is best';

    const SHARE_PICTURE_URL = 'https://cdn.gfplay.cn/down/active/baodingshare_0918.jpg'; //微信分享大图
    const SHARE_TYPE = 1; //微信分享方式  1 图片分享  2 链接分享
    const DOWNLOAD_URL = 'http://sx.gfplay.cn/down/index.html';	//download url
    const ICON_URI = "https://sx.gfplay.cn/down/logo.png";	//icon url
    const PUBLICATION = "新广出审[2016]3953号 软著登字第1461234号";
    const PAY_CURRENCY = "第一局结束由房主支付房间费用";	//开房时不收取费用，总结算时分数最多的人支付房间费用

    const WINNER_CURRENCY = 0;	//is winner pay
    public static $room_type = [
        array('game_type'=>201, 'set_num'=>[4, 8, 16], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>211, 'set_num'=>[4, 8, 16], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>212, 'set_num'=>[4, 8, 16], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>213, 'set_num'=>[4, 8, 16], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
    							];

     public static $room_type_circle = [ array('game_type'=>201, 'set_num'=>[1, 2, 4], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>211, 'set_num'=>[1, 2, 4], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>212, 'set_num'=>[1, 2, 4], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>1)
                                ,array('game_type'=>213, 'set_num'=>[1, 2, 4], 'currency'=>[2, 3, 5], 'default'=>1, 'use_currency'=>2)
                                ];
    public static $wx_pay =array( array("10","0.01")
                                  ,array("20","18")
                                  ,array("50","40")
                                  ,array("100","75")
                                 );  //10钻=>10元
    
    //101vip日卡  102vip周卡  103vip月卡  104vip季卡  105vip半年卡  106vip年卡 
    public static $wx_vip_pay =array( array("7天周卡","8","10","102")
                                  ,array("30天月卡","8","20","103")
                                  ,array("90天季卡","8","30","104")
                                  ,array("365天年卡","8","40","106")
                                 );  //vip/现价/原价/类型

    public static $wx_pay_currency2 = array( 
        array("1","1"),
        array("5","5"),
        array("25","25"),
        array("50","50"),
        array("100","100"),
        array("300","300"),
        array("500","500"),
        array("1000","1000")
        );  //10红钻=>10元
    //roll text
    const scrollText = "代理招募中咨询微信:gfgame01，gfgame001，精彩活动，敬请期待。文明娱乐，禁止赌博。";
    const scrollText_IOS = "精彩活动，敬请期待。文明娱乐，禁止赌博。";
    //const scrollText = "<color=#ffe153>2016-02-21 6:00-7:00服务器维护！</color>";
    //const scrollText_IOS = "<color=#ffe153>2016-02-21 6:00-7:00服务器维护！</color>";

    //game notice
    const gameNoticeText = "<color=#904b2c><size=40>\r\n本游戏仅为\r\n娱乐休闲游戏平台\r\n自觉远离\r\n赌博等非法行为</size>\r\n<size=20>\r\n<size=31>游戏问题请关注\r\n微信公众号:<color=#EA360D>功夫棋牌</color></size></size>\r\n</color>";
    const gameNoticeText_IOS = "<color=#904b2c><size=40>\r\n本游戏仅为\r\n娱乐休闲平台\r\n自觉远离\r\n赌博等非法行为</size>\r\n\r\n<size=35>祝您游戏愉快</size></color>";

    //message
    const mesText = "<size=42><color=#904b2c>\r\n亲爱的玩家:\r\n灵飞麻将在此声明，本游戏无任何外挂，请各位玩家放心使用。\r\n请文明游戏，远离赌博，祝您玩的愉快!\r\n\r\n游戏产品、产品建议或举报请联系微信号：gfplay006，我们将竭诚为您服务。\r\n微信公众号搜索“功夫棋牌”关注功夫游戏，更多活动优惠及时掌握！好礼送不停！</color></size>";
    const mesText_IOS = "<size=42><color=#904b2c>\r\n亲爱的玩家:\r\n欢迎来到灵飞麻将。\r\n请文明游戏，远离赌博，祝您玩的愉快!\r\n\r\n游戏产品、产品建议或举报请联系我们，将竭诚为您>服务。</color></size>";

    //home page notice
    const loginNoticetText  = "抵制不良游戏，拒绝盗版游戏。注意自我保护，谨防受骗上当。适度游戏益脑，沉迷游戏伤身。合理安排时间，享受健康生活。";
    //weiXin share text
    const wxShareText = "本麻将是一款地方特色的棋牌游戏。我开好房了,你快来!你快来!";

    const plusText = "<color=#904b2c><size=35>代理问题gfgame01或gfgame001[微信]\r\n游戏问题建议gfplay006 [微信]\r\n</size></color>";

    const SPREADER_ARR = ['1233123123','13222322223', '1233322232'];
    const NEWS_INFO = [[], 1];
    const BLACK_LIST = [];  //玩家黑名单

    const RANDOM_MATCH_TIMEOUT = 300;	// random match timeout second
    const RANDOM_MATCH_CURRENCY = 5;

    static $rule =
    [
        "version"=>self::RULE_VERSION   // *服务器控制版本
        , "values"=>[1]     // *分级导航默认索引值
        , "level_label"=>[]     // *分级导航名称
        , "data"=>
        [                         // 规则数组
            // 规则1, 规则2, ...
        ]
    ];
    

    //积分场官方扣除比例
    const SCORE_DEDUCT_PERCENT = 0.05;
    const CURRENCY_EXCHANGE_SCORE = 100;

    //礼品
    const CURRENCY_5 = 1;
    const CURRENCY_20 = 2;
    const CURRENCY_50 = 3;
    const CURRENCY_100 = 4;

    //礼品
    static $gift_group = [
        self::CURRENCY_5 => ['name' => '5颗蓝钻石','currency'=> '5','need_score' => '450','need_cup' => '150','picture' => 'https://cdn.gfplay.cn/down/gift/diamond5.png'],
        self::CURRENCY_20 => ['name' => '20颗蓝钻石','currency'=> '20','need_score' => '1800','need_cup' => '500','picture' => 'https://cdn.gfplay.cn/down/gift/diamond20.png'],
        self::CURRENCY_50 => ['name' => '50颗蓝钻石','currency'=> '50','need_score' => '4000','need_cup' => '1000','picture' => 'https://cdn.gfplay.cn/down/gift/diamond50.png'],
        self::CURRENCY_100 => ['name' => '100颗蓝钻石','currency'=> '100','need_score' => '7800','need_cup' => '1600','picture' => 'https://cdn.gfplay.cn/down/gift/diamond100.png'],
    ];

    //奖杯赠送(普通场大赢家赠送一个,积分场无积分,不赠送奖杯和积分)
    static $cup_present = [
        0 => [1,0,0,0],
        100 => [10,6,2,2],
        500 => [60,30,10,10],
        2500 => [400,200,60,60],
        8000 => [3000,1400,300,300],
        15000 => [6000,2800,400,400],
        40000 => [16000,6000,2000,2000],
        100000 => [42000,16000,5000,5000],
    ];

    //招募代理
    static $join_wechat_id = [
        '305022834',
        'czdbqp888',
        '18003376688',
        'A15733705583',
        'wq13111702685',
        'czbdqp777',
        '1148689501',
        'czqp005'
    ];

    //客服微信
    static $customer_service = [
        'bdqp007'
    ];

    //积分活动配置
    static $score_activity = [
        'score' => 100,
        'start_time' => '2017-11-21',
        'duration' => 3
    ];

    //更多游戏
    static $more_games = [
        [
            'download_url' => '',
            'icon' => '',
            'name' => '保定棋牌'
        ],
      
    ];

    //用户拉用户
    const NEW_USER_MESSAGE = true;
    static $invite_user_reward = [
        'new_user' => 3,
        'first_recharge' => 10,
        'complete_game' => 8
    ];

    const SUBSCRIBE_CURRENCY = 3;
    const SERVER_CTR_HIDE = true;
    const VIP_START = 1;  //是否开启vip会员制
}