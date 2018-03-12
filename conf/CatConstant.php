<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\conf;

class CatConstant
{
	const SECRET = 'Keep it simple stupid!';
	const CDKEY  = 'God bless you!';
	const LOG_FILE = './log/log.log';
	const CACHE_TYPE = '\bigcat\inc\CatMemcache';

	const OK = 0;
	const ERROR = 1;
	const ERROR_MC = 2;
	const ERROR_INIT = 3;
	const ERROR_UPDATE = 4;
	const ERROR_VERIFY = 5;
	const ERROR_ARGUMENT = 6;
	const ERROR_VERSION = 7;
	const ERROR_VERSION_DESC = 8;
	const ERROR_NETWORK = 9;
	const ERROR_SEARCH = 10;
	const ERROR_CONFIG = 11;

	const CNT_PER_PAGE = 20;
	
	const MODELS = array(
		'Business' => '\bigcat\controller\Business',
		'BackgroundSystem' => '\bigcat\controller\BackgroundSystem',
	);

	const UNCHECK_C_CERSION_ACT = array('Business' => ['update_income_pay_status','get_filter_word','get_table_log','remote_call_back','find_play_recharge','find_play_video','get_rule', 'agent_info', 'get_room_user', 'get_conf', 'kpi_crontab', 'kpi_get', 'set_game_log', 'checkout_open_room', 'get_user', 'get_game_log', 'get_game_log_save','bind_agent_id','bind_out_agent_id','play_list','pull_blacklist','blacklist','income_statistics','kpi_get_new','change_user_agent', 'set_log','get_score','get_gift','get_gift_log','show_gift_exchange_log','update_someday_kpi','player_recharge_list_new', 'agent_open_room_log',"change_read_state",'recordKpiThreeLevels','judge_uid','message_list','update_message_state','getTotalInvitee','getAddedMessageAmount','get_jsapi_ticket','getWechatHead','wechatSubscibe','wechatPublicShare','getWechatId','mysqlTruncate','getUserGame','getUserBelongsAnAgent']);
	
	const UNCHECK_VERIFIED_ACT = array('Business' => ['get_filter_word','get_table_log', 'get_rule', 'agent_info', 'get_room_user', 'get_conf', 'login', 'login_check', 'logout', 'get_user', 'open_room', 'join_random_room', 'join_room', 'real_name_reg', 'kpi_crontab', 'kpi_get', 'set_game_log', 'get_game_log', 'get_game_log_save', 'share_currency','bind_agent_id','pull_blacklist','blacklist','income_statistics','kpi_get_new','change_user_agent', 'set_log','get_score','get_gift','get_gift_log','show_gift_exchange_log','update_someday_kpi','player_recharge_list_new','agent_open_room_log', "change_read_state",'recordKpiThreeLevels','judge_uid','message_list','update_message_state','getTotalInvitee','getAddedMessageAmount','checkout_open_room','get_jsapi_ticket','getWechatHead','wechatSubscibe','make_card','wechatPublicShare','getWechatId','mysqlTruncate','getUserGame','getUserBelongsAnAgent']);

	const SUB_DESC = array(
		'Business_login' => array('sub_code_1'=>''),
		'Business_login_check' => array('sub_code_1'=>'登录失败'),
		'Business_open_room' => array(
			'sub_code_1'=>'用户登录失败',
			'sub_code_2'=>'已经有正在进行的游戏',
			'sub_code_3'=>'系统忙请稍后再试',
			'sub_code_4'=>'钻不够啦', 
			'sub_code_7'=>'积分不够啦',
			'sub_code_8'=>'公会钻石不够啦',
			'sub_code_9'=>"公会未开通此付费方式",
			'sub_code_10'=>"会长和玩家不在同一公会",
			'sub_code_11'=>"找不到会长玩家"
		),
		'Business_join_room' => array(
			'sub_code_1'=>'用户登录失败',
			'sub_code_2'=>'已经有正在进行的游戏',
			'sub_code_3'=>'系统忙请稍后再试',
			'sub_code_4'=>'房间人数已满',
			'sub_code_5'=>'房间号错误',
			'sub_code_6'=>'钻不够啦', 
			'sub_code_7'=>'积分不够啦', 
			'sub_code_8' => '不同公会玩家不能进入同一公会房'
		),
		'Business_get_game_log' => array('sub_code_1'=>'用户登录失败', 'sub_code_2'=>'没有记录'),
		'Business_get_game_log_save' => array('sub_code_1'=>'用户登录失败'),
		'Business_share_currency' => array('sub_code_1'=>'用户登录失败', 'sub_code_2'=>'已经奖励过了', 'sub_code_3'=>'活动关闭'),
		'Business_bind_agent_id' => array('sub_code_1'=>'非推广员', 'sub_code_2'=>'无法再次绑定'),
		'Business_bind_out_agent_id' => array('sub_code_2'=>'没有绑定过推广员', 'sub_code_2'=>'推广员id错误'),
		'Business_get_table_log' => array('sub_code_1'=>'用户登录失败', 'sub_code_2'=>'没有记录'),
		'Business_get_gift' => array('sub_code_1'=>'用户登录失败', 'sub_code_2'=>'积分或者奖杯不足'),
		'Business_get_score' => array('sub_code_1'=>'用户登录失败', 'sub_code_2'=>'红钻数量不足', 'sub_code_3' => '找不到该用户'),
		'Business_get_gift_log' => array('sub_code_1'=>'用户登录失败'),
		'Business_wechatSubscibe' => array(
			'sub_code_1'=>'无法获取玩家信息',
			'sub_code_2'=>'玩家未订阅公众号',
			'sub_code_3'=>'玩家ID或昵称错误',
			'sub_code_4'=>'玩家id不正确', 
			'sub_code_5'=>'您已经领取过啦，不能重复领取'
		),
		'Business_wechatPublicShare' => array('sub_code_1'=>'您还不是我们的游戏用户，快去下载游戏吧')
	);

	const CODE_DESC = array(
		'code_'.self::OK => '成功',
		'code_'.self::ERROR => '错误',
		'code_'.self::ERROR_MC => '缓存错误',
		'code_'.self::ERROR_INIT => '初始化错误',
		'code_'.self::ERROR_UPDATE  => '数据库更新错误', 
		'code_'.self::ERROR_VERIFY  => '参数校验错误', 
		'code_'.self::ERROR_ARGUMENT  => '参数校验错误', 
		'code_'.self::ERROR_VERSION  => '版本错误', 
		'code_'.self::ERROR_NETWORK  => '网络错误', 
		'code_'.self::ERROR_SEARCH  => '查询错误', 
		'code_'.self::ERROR_CONFIG  => '配置项错误', 
	);

	const BACKGROUND_ACTION = ['kpi_crontab','kpi_get','bind_out_agent_id','play_list','pull_blacklist','blacklist','kpi_get_new','income_statistics','change_user_agent','find_play_recharge','find_play_video','update_income_pay_status','show_gift_exchange_log','update_someday_kpi','player_recharge_list_new','recordKpiThreeLevels','getUserBelongsAnAgent'];
}
