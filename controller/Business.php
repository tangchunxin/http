<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\controller;

use bigcat\conf\Config;
use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;

use bigcat\model\Uid;

use bigcat\model\User;
use bigcat\model\UserFactory;
use bigcat\model\UserListFactory;
use bigcat\model\UserMultiFactory;

use bigcat\model\UserGame;
use bigcat\model\UserGameFactory;
use bigcat\model\UserGameListFactory;
use bigcat\model\UserGameMultiFactory;

use bigcat\model\Room;
use bigcat\model\RoomFactory;
use bigcat\model\RoomListFactory;

use bigcat\model\GameLog;
use bigcat\model\GameLogListFactory;
use bigcat\model\GameLogMultiFactory;

use bigcat\model\GameLogUser;
use bigcat\model\GameLogUserListFactory;

use bigcat\model\UserLog;
use bigcat\model\UserLogListFactory;
use bigcat\model\UserLogMultiFactory;

use bigcat\model\Kpi;
use bigcat\model\KpiListFactory;
use bigcat\model\KpiMultiFactory;

use bigcat\model\KpiNew;
use bigcat\model\KpiNewListFactory;
use bigcat\model\KpiNewMultiFactory;

use bigcat\model\Agent;
use bigcat\model\AgentFactory;

use bigcat\model\GameTableLog;
use bigcat\model\GameTableLogFactory;
use bigcat\model\GameTableLogListFactory;
use bigcat\model\GameTableLogMultiFactory;

use bigcat\model\GiftExchangeLog;
use bigcat\model\GiftExchangeLogFactory;
use bigcat\model\GiftExchangeLogListFactory;
use bigcat\model\GiftExchangeLogMultiFactory;

use bigcat\model\RechargeableCard;
use bigcat\model\RechargeableCardFactory;
use bigcat\model\RechargeableCardListFactory;
use bigcat\model\RechargeableCardMultiFactory;

use bigcat\model\RewardMessage;
use bigcat\model\RewardMessageFactory;
use bigcat\model\RewardMessageListFactory;
use bigcat\model\RewardMessageMultiFactory;

use bigcat\model\WXTicket;
use bigcat\model\WXTicketFactory;
use bigcat\model\WXTokenFactory;

use bigcat\model\UserActive;
use bigcat\model\UserActiveFactory;
use bigcat\model\UserActiveListFactory;
use bigcat\model\UserActiveMultiFactory;

class Business
{
	public static $user_game_key = 'user_game_lock_key_';

	private $log = CatConstant::LOG_FILE;
	private $login_timeout = 604800;	//3600 * 24 * 7
	//private $login_timeout = 60480000;	//3600 * 24 * 7	 * 100
	public $cache_handler = null;

	private $tcp_arr;
	private $tcp_arr_ios;
	private $tcp_arr_pre;
	private $tcp_arr_h5;
	
	public function __construct()
	{
		if(defined("bigcat\\conf\\Config::GAOFANG") && Config::GAOFANG == 'gaofang_open')
		{
			$this->tcp_arr = Config::TCP_ARR_DDOS;
			$this->tcp_arr_ios = Config::TCP_ARR_IOS_DDOS;
			$this->tcp_arr_pre = Config::TCP_ARR_PRE_DDOS; 
			$this->tcp_arr_h5 = Config::TCP_ARR_H5_DDOS; 
		}
		else
		{
			$this->tcp_arr = Config::TCP_ARR;
			$this->tcp_arr_ios = Config::TCP_ARR_IOS;
			$this->tcp_arr_pre = Config::TCP_ARR_PRE;
			$this->tcp_arr_h5 = Config::TCP_ARR_H5;
		}
	}

    //发送tcp数据格式整理
	public static function tcp_encode($buffer)
	{
		return pack('N', strlen($buffer)) . $buffer;
	}
	//接收tcp数据格式整理
	public static function tcp_decode($buffer)
	{
		return substr($buffer, 4);
	}

	public static function cmp_log_id($a, $b)
	{
		$return = 0;
		$key_name = 'id';
		$order_sort = 'desc';

		if ($a->$key_name == $b->$key_name)
		{
			$return = 0;
		}
		else if ($a->$key_name < $b->$key_name)
		{
			$return = -1;
		}
		else if ($a->$key_name > $b->$key_name)
		{
			$return = 1;
		}

		if($order_sort == 'desc')
		{
			$return = -$return;
		}
		return $return;
	}

	public static function cmp_list($a, $b)
	{
		$return = 0;
		$key_name = 'time';
		$order_sort = 'desc';

		if ($a->$key_name == $b->$key_name)
		{
			$return = 0;
		}
		else if ($a->$key_name < $b->$key_name)
		{
			$return = -1;
		}
		else if ($a->$key_name > $b->$key_name)
		{
			$return = 1;
		}

		if($order_sort == 'desc')
		{
			$return = -$return;
		}
		return $return;
	}

	public static function cmp_list_update_time($a, $b)
	{
		$return = 0;
		$key_name = 'update_time';
		$order_sort = 'desc';

		if ($a->$key_name == $b->$key_name)
		{
			$return = 0;
		}
		else if ($a->$key_name < $b->$key_name)
		{
			$return = -1;
		}
		else if ($a->$key_name > $b->$key_name)
		{
			$return = 1;
		}

		if($order_sort == 'desc')
		{
			$return = -$return;
		}
		return $return;
	}

	//生成房间码
	private function _get_rid_key($rid)
	{
		$itime = time();
		$rid_all = sprintf("%06d", $rid);//最短6位的十进制数
		$key = BaseFunction::sub_encryptMD5($rid_all.$itime);
		return $key.$rid_all.$itime;
	}

	//获取一个tcp服务器（数组）
	private function _get_tcp_s($rid, $c_version, $platform)
	{
		$tcp_s = [];
		if($rid)
		{
			$ridhash = base_convert( substr(md5($rid), 0, 8), 16, 10 );
			$s_count = count($this->tcp_arr);
			$tcp_s = $this->tcp_arr[$ridhash%$s_count];
			//苹果专用审核服务器
			if( $platform == 'gfplay_ios' )
			{
				$s_count = count($this->tcp_arr_ios);
				$tcp_s = $this->tcp_arr_ios[$ridhash%$s_count];
			}
			if( ($platform == 'gfplay_ios' && Config::C_VERSION_IOS != Config::C_VERSION_IOS_PRE && $c_version == Config::C_VERSION_IOS_PRE))
			{
				$tcp_s = $this->tcp_arr_pre[0];
			}
			if($platform == 'gfplay_h5')
			{
				$tcp_s = $this->tcp_arr_h5[0];
			}
			if(Config::DEBUG &&  Config::C_VERSION != Config::C_VERSION_PRE && $c_version == Config::C_VERSION_PRE && $platform != 'gfplay_h5')
			{
				//测试2号tcp
				$tcp_s = $this->tcp_arr_pre[0];
			}
		}
		if(defined("bigcat\\conf\\Config::IP_CRYPT") && Config::IP_CRYPT)
		{
			$str_time = ''.time();
			$tcp_s[0] = BaseFunction::encryptRandAuth($str_time , $tcp_s[0]);
			$tcp_s[2] = $str_time;
		}

		return $tcp_s;
	}

	//用户绑定房间号
	private function _bind_rid($host_port, $rid)
	{
		//获得客户端TCP服务器对象
		$client = BaseFunction::getTCP($host_port);
		if(!$client)
		{
			return false;
		}

		$client->send(self::tcp_encode(json_encode(array('act'=>'c_bind', 'rid'=>$rid, 'rid_key'=>$this->_get_rid_key($rid)))));
		$re_str = self::tcp_decode($client->recv());
		$re = json_decode($re_str,true);

		if($re['act'] == 's_result' && $re['info'] == 'c_bind' && $re['code'] == 0)
		{
			return $client;
		}
		return false;
	}

	//对应局数需要的钻数
	private function _need_currency($game_type, $set_num, $is_circle = false, &$use_currency = 1, $is_score_field = false)
	{
		if ($is_score_field) 
		{
			$tmp_room_type = Config::$room_type_score;
		}
		else
		{
			if(!$is_circle)
			{
				$tmp_room_type = Config::$room_type;
			}
			else
			{
				$tmp_room_type = Config::$room_type_circle;
			}
		}

		$return = 0;
		foreach ($tmp_room_type as $obj_game_currency)
		{
			if($obj_game_currency['game_type'] == $game_type)
			{
				foreach ($obj_game_currency['set_num'] as $key => $val)
				{
					if($val == $set_num)
					{
						$return = $obj_game_currency['currency'][$key];
					}
				}

				if(empty($obj_game_currency['use_currency']))
				{
					$use_currency = 1;
				}
				else
				{
					$use_currency = $obj_game_currency['use_currency'];
				}
			}
		}
		return $return;
	}

	//校验用户货币是否够
	private function _is_score_enough($rule, $obj_user_game)
	{
		$error_code = 0;
		
		if (!empty($rule['score'])) 
		{
			if ($rule['score'] > $obj_user_game->score) 
			{
				$error_code = 1;
			}
		}

		return $error_code;
	}

	//检查蓝钻
	private function _is_currency_enough($rule, $obj_user_game, $is_room_owner = false)
	{
		$tmp_need_currency = 0;
		$use_currency = 1;	//币种
		$now_currency = 0;
		$error_code = 0;
		
		if (!empty($rule)) 
		{
			if (isset($rule['game_type']['rulename']))
	        {
	            $rule['game_type'] = $rule['game_type']['rulename'];
	        }

	        if (!empty($rule['is_score_field'])) 
	        {
	        	$tmp_need_currency = $this->_need_currency($rule['game_type'], $rule['set_num'], false, $use_currency, $rule['is_score_field']);
	        }
	        else
	        {
	        	if(empty($rule['is_circle']))
		        {
					$tmp_need_currency = $this->_need_currency($rule['game_type'], $rule['set_num'], false, $use_currency);
		        }
		        else if(!empty($rule['is_circle']))
		        {
		            $tmp_need_currency = $this->_need_currency($rule['game_type'], $rule['is_circle'], true, $use_currency);
		        }
	        }
		}

        do{
	        if (isset($rule['pay_type']))
	        {
	            $pay_type = $rule['pay_type'];

	            //房主付费
	            if ($pay_type == 0 && $is_room_owner)
	            {
	            	break;
	            }

	            //AA付费
	            if ($pay_type == 1)
	            {
	            	$tmp_need_currency = ceil($tmp_need_currency / $rule['player_count']);
	            	break;
	            }

	            //大赢家扣费
	            if ($pay_type == 2)
	            {
	                break;
	            }

	            //公会房
	            if ($pay_type == 3 && $is_room_owner)
	            {
	            	$tmp_need_currency = 20;
	                break;
	            }

				$tmp_need_currency = 0;
				break;
	        }
	        else
	        {
	            if(!empty(Config::WINNER_CURRENCY))
	            {
	                $tmp_need_currency = 1;
	                break;
	            }
	            if($is_room_owner)
	            {
	                break;
	            }

	            $tmp_need_currency = 0;
	            break;
	        }
    	}while (false);

		$now_currency = $obj_user_game->currency;
		if ($tmp_need_currency > $now_currency) 
		{
			$error_code = 1;
		}

    	return $error_code;
	}

	////////////////////////////////////////////////////////////////////////

	//获取敏感词（公开权限）
	public function get_filter_word($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);

		do {
			$data['filter_word']["version"] = Config::FILTER_WORD_VERSION;
			$data['filter_word']["data"] = include('./inc/filter_word.php');

			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//获取配置数据（公开权限）
	public function get_rule($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);

		do {
			if ($params['c_version'] == Config::C_VERSION_IOS_PRE && $params['platform'] == 'gfplay_ios' && isset(Config::$examine_rule)) 
			{
				$data['rule'] = Config::$examine_rule;
			}
			else
			{
				$data['rule'] = Config::$rule;
			}

			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//获取配置数据（公开权限）
	public function get_conf($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();

		do {
			$data['c_version'] = Config::C_VERSION;
			$data['c_version_pre'] = Config::C_VERSION_PRE;
			$data['c_version_ios'] = Config::C_VERSION_IOS;
			$data['c_version_ios_pre'] = Config::C_VERSION_IOS_PRE;

			$data['scrollText'] = Config::scrollText;
			$data['gameNoticeText'] = Config::gameNoticeText;
			$data['mesText'] = Config::mesText;
			$data['loginNoticetText'] = Config::loginNoticetText;
			$data['wxShareText'] = Config::wxShareText;

			$data['plusText'] = Config::plusText;
			$data['room_type'] = Config::$room_type;
			if(isset(Config::$room_type_circle))
			{
				$data['room_type_circle'] = Config::$room_type_circle;
			}

			if(defined("bigcat\\conf\\Config::RANDOM_MATCH_TIMEOUT") && defined("bigcat\\conf\\Config::RANDOM_MATCH_CURRENCY"))
			{
				$data['random_match_timeout'] = Config::RANDOM_MATCH_TIMEOUT;
				$data['random_match_currency'] = Config::RANDOM_MATCH_CURRENCY;
			}

			if(defined("bigcat\\conf\\Config::RULE_VERSION"))
			{
				$data['rule_version'] = Config::RULE_VERSION;
			}

			if(defined("bigcat\\conf\\Config::FILTER_WORD_VERSION"))
			{
				$data['filter_word_version'] = Config::FILTER_WORD_VERSION;
			}

			if(defined("bigcat\\conf\\Config::SPREADER_ARR"))
			{
				$data['spreader_arr'] = Config::SPREADER_ARR;
			}

			if(defined("bigcat\\conf\\Config::NEWS_INFO"))
			{
				$data['news_info'] = Config::NEWS_INFO;
			}
			if(defined("bigcat\\conf\\Config::SHARE_PICTURE_URL"))
			{
				$data['share_picture'] = Config::SHARE_PICTURE_URL;
			}
			if(defined("bigcat\\conf\\Config::SHARE_TYPE"))
			{
				$data['share_type'] = Config::SHARE_TYPE;
			}

			if(isset(Config::$gift_group))
			{
				$data['gift_group'] = Config::$gift_group;
			}

			if (defined("bigcat\\conf\\Config::CURRENCY_EXCHANGE_SCORE")) 
			{
				$data['exchange_ratio'] = Config::CURRENCY_EXCHANGE_SCORE;
			}

			if(isset(Config::$cup_present))
			{
				$data['cup_present'] = Config::$cup_present;
			}

			if(defined("bigcat\\conf\\Config::SCORE_DEDUCT_PERCENT"))
			{
				$data['score_decuct_percent'] = Config::SCORE_DEDUCT_PERCENT;
			}

			// 项目icon 地址
			$data['icon_uri'] = Config::ICON_URI;
			// 版号信息
			$data['publication'] = Config::PUBLICATION;

			//付钻费用说明文字
			$data['pay_currency'] = Config::PAY_CURRENCY;
			//是否大赢家付费
            $data['winner_currency'] = Config::WINNER_CURRENCY;

			//			if(empty(Config::NO_CHECK_IP))
			//			{
			//				//美国ip 苹果审核做处理
			//				require(__DIR__."/../plug/IP.class.php");
			//				$c_ip = BaseFunction::get_client_ip();
			//				$ip_info = \IP::find($c_ip);
			//
			//				BaseFunction::logger($this->log, "【c_ip】:\n".var_export($ip_info, true)."\n".__LINE__."\n");
			//				if($ip_info[0] == '美国')
			//				{
			//					BaseFunction::logger($this->log, "【c_ip】:\n".var_export($ip_info, true)."\n".__LINE__."\n");
			//					$data['c_version_ios_pre'] = Config::C_VERSION_IOS;
			//					$data['c_version_ios'] = Config::C_VERSION_IOS_LAST;
			//
			//					$data['scrollText'] = Config::scrollText_IOS;
			//					$data['gameNoticeText'] = Config::gameNoticeText_IOS;
			//					$data['mesText'] = Config::mesText_IOS;
			//				}
			//			}

			if(!empty($params['platform']) && $params['platform'] == 'gfplay_ios'
				&& Config::C_VERSION_IOS != Config::C_VERSION_IOS_PRE && $params['c_version'] == Config::C_VERSION_IOS_PRE
				)
			{
				$data['scrollText'] = Config::scrollText_IOS;
				$data['gameNoticeText'] = Config::gameNoticeText_IOS;
				$data['mesText'] = Config::mesText_IOS;
				//审核版恢复版本号
				$data['c_version_ios'] = Config::C_VERSION_IOS;
				$data['c_version_ios_pre'] = Config::C_VERSION_IOS_PRE;
			}
			if(isset(Config::$wx_pay))
			{
				$data['wx_pay'] = Config::$wx_pay;
			}
			
			if(isset(Config::$wx_vip_pay))
			{
				$data['wx_vip_pay'] = Config::$wx_vip_pay;
			}
			
			if(isset(Config::$wx_pay_currency2))
			{
				$data['wx_pay_currency2'] = Config::$wx_pay_currency2;
			}

			if(isset(Config::$room_type_score))
			{
				$data['room_type_score'] = Config::$room_type_score;
			}

			if(isset(Config::$join_wechat_id))
			{
				$data['join_wechat_id'] = Config::$join_wechat_id;
			}

			if(isset(Config::$customer_service))
			{
				$data['customer_service'] = Config::$customer_service;
			}

			if(isset(Config::$more_games))
			{
				$data['more_games'] = Config::$more_games;
			}

			if(isset(Config::$invite_user_reward))
			{
				$data['invite_user_reward'] = Config::$invite_user_reward;
			}

			if (defined("bigcat\\conf\\Config::SERVER_CTR_HIDE")) 
			{
				$data['server_ctr_hide'] = Config::SERVER_CTR_HIDE;
			}

			$data['down_url'] = Config::DOWNLOAD_URL;
			$data['itime'] = $itime;

			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//收集bug
	public function set_log($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);

		do {
			if( empty($params['log'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			BaseFunction::logger($this->log, "set_log:\n".var_export($params['log'], true)."\n".__LINE__."\n");

		} while (false);

		return $response;
	}

	//随机加入房间
	public function join_random_room($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		//验证用户是否登录

		//查看用户信息是否有未完成的房

		//去不同的类型服务器查看tcp服务器房间状态

		//校验用户货币

		//随机socket服务器

		//查看socket服务器的随机房间

		//返回房间号
	}

	//加入房间
	public function join_room($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$score_error_code = 0;
		$currency_error_code = 0;

		do {
			if( empty($params['rid'])
			|| empty($params['uid'])
			|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$params['rid'] = intval($params['rid']);
			$params['uid'] = intval($params['uid']);

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$tcp_s = '';
			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$obj_user_factory); break;
			}
			$obj_user = $obj_user_factory->get();

			//查看用户信息是否有未完成的房
			do
			{
				$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$params['uid'], 1, 1);
			}while (!$tmp_user_game_lock);
			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();

				if(!empty($obj_user_game->room) && $obj_user_game->room != $params['rid'])
				{
					$tcp_s_old = $this->_get_tcp_s($obj_user_game->room, $params['c_version'], $params['platform']);

					//去查看tcp服务器房间状态
					$client = $this->_bind_rid($tcp_s_old[1], $obj_user_game->room);
					if(!$client)
					{
						$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
					}

					$client->send(self::tcp_encode(json_encode(array('act'=>'c_get_room', 'rid'=>$obj_user_game->room, 'uid'=>$params['uid']))));
					$result =self::tcp_decode( $client->recv());
					$result = json_decode($result, true);
					$client->close();

					if(!empty($result['info']) && $result['info'] == 'c_get_room' && ($result['code'] == 4 || $result['code'] == 5))
					{
						//如果tcp游戏正在进行则直接进入
						$response = $this->_debugLog(2,'notice',__LINE__); break;
					}
					else if($result['code'] == 2 || $result['code'] == 1)	//房间是空闲状态
					{
						//更新room数据库
						$this->_set_room($obj_user_game->room, 1);
						//如果tcp服务器游戏已经结束则更新本tcp room 本用户数据
						$obj_user_game->room = 0;
					}
					else if($result['code'] == 3 || $result['code'] == 0)	//房间已满或未满，不包含本用户
					{
						$obj_user_game->room = 0;
					}
				}
            }
			else
			{
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			//验证vip是否过期
			if(empty($obj_user_game->room))
			{
				if (defined("bigcat\\conf\\Config::VIP_START") && Config::VIP_START==1)
				{
					if($itime > $obj_user_game->vip_overtime)
					{
						//vip会员过期  不能开房间
						$response = $this->_debugLog(9,'notice',__LINE__); break;
					}
				}
			}

			//确定tcp服务器地址
			$tcp_s = $this->_get_tcp_s($params['rid'], $params['c_version'], $params['platform']);
			//去tcp确认本rid状态
			$client = $this->_bind_rid($tcp_s[1], $params['rid']);
			if(!$client)
			{
				$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
			}

			//高仿IP
			$client_ip = BaseFunction::get_client_ip();
			if (empty($client_ip) || $client_ip == 'unknown') 
			{
				$ip_last = rand(1,200);
				$client_ip = '123.120.224.'.$ip_last;
			}

			$client->send(self::tcp_encode(json_encode(array('act'=>'c_get_room', 'rid'=>$params['rid'], 'uid'=>$params['uid'],'client_ip'=>$client_ip))));

			$result = self::tcp_decode($client->recv());
			$result = json_decode($result, true);
			$client->close();

            //校验用户货币
            if(empty($result['rule']))
            {
            	$result['rule'] = [];
            } 

			if(!empty($result['info']) && $result['info'] == 'c_get_room' && ($result['code'] == 0 || ($result['code'] == 4 || $result['code'] == 5)))	//有空位或者用户已经在里面
			{
				$score_error_code = $this->_is_score_enough($result['rule'], $obj_user_game);
				$currency_error_code = $this->_is_currency_enough($result['rule'], $obj_user_game);

	            if( $result['code'] != 4 && $currency_error_code == 1)
	            {
					$response = $this->_debugLog(6,'notice',__LINE__); break;
	            }

	            if ($score_error_code == 1) 
                {
					$response = $this->_debugLog(7,'notice',__LINE__); break;
                }

				//先更新user_game数据库
				$obj_user_game->room = $params['rid'];
				$obj_user_game->is_room_owner = 1;
				
				if(!empty($result['room_owner']) && $result['room_owner'] != $params['uid'])
				{
					$obj_user_game->is_room_owner = 0;
					if ($result['rule']['pay_type'] == 3) 
					{
						$obj_room_owner_factory = new UserGameFactory($this->cache_handler, $result['room_owner']);
						if($obj_room_owner_factory->initialize() && $obj_room_owner_factory->get())
						{
							$obj_room_owner = $obj_room_owner_factory->get();
							if ($obj_room_owner->agent_id != $obj_user_game->agent_id) 
							{
								$response = $this->_debugLog(8,'notice',__LINE__); break;
							}
						}
					}
				}

				$obj_user_game->last_game_time = $itime;
				$rawsqls[] = $obj_user_game->getUpdateSql();
			}
			else if($result['code'] == 3)
			{
				$response = $this->_debugLog(4,'notice',__LINE__); break;
			}
			else if ($result['code'] == 2 || $result['code'] == 1)
			{
				$response = $this->_debugLog(5,'notice',__LINE__);
				$this->_set_room($obj_user_game->room, 1);
				break;
			}
			else
			{
				$response = $this->_debugLog(5,'notice',__LINE__); break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$obj_user_game_factory->clear();

			$data['obj_user_game'] = $obj_user_game;
			$data['obj_user'] = $obj_user;
			$data['rid'] = $params['rid'];
			$data['rid_key'] = $this->_get_rid_key($params['rid']);
			$data['tcp_s'] = $tcp_s;
			$data['rule'] = $result['rule'];
			$response['data'] = $data;

			$this->cache_handler->del(self::$user_game_key.$params['uid'], self::$user_game_key.$params['uid']);

		}while(false);

		return $response;
	}
	
	//创建房间
	public function open_room($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$score_error_code = 0;
		$currency_error_code = 0;

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['rule'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$params['uid'] = intval($params['uid']);
			if(empty($params['game_type']))
			{
				$params['game_type'] = Config::GAME_TYPE;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$rid = 0;
			$tcp_s = '';
			$this->_init_cache();

			//查看用户信息是否有未完成的房
			do
			{
				$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$params['uid'], 1, 1);
			}while (!$tmp_user_game_lock);

			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();
				if ($params['rule']['pay_type'] == 3) 
				{
					if (empty($params['opend_status'])) 
					{
						$response = $this->_debugLog(9,'notice',__LINE__); break;
					}

					$obj_user_game_agent_factory = new UserGameFactory($this->cache_handler, $params['opend_status']);
					if($obj_user_game_agent_factory->initialize() && $obj_user_game_agent_factory->get())
					{
						$obj_user_game_agent = $obj_user_game_agent_factory->get();
						if ($obj_user_game_agent->agent_id != $obj_user_game->agent_id) 
						{
							$response = $this->_debugLog(10,'notice',__LINE__); break;
						}
					}
					else
					{
						$response = $this->_debugLog(11,'notice',__LINE__); break;
					}

					$currency_error_code = $this->_is_currency_enough($params['rule'], $obj_user_game_agent, true);
				}
				else
				{
                	$currency_error_code = $this->_is_currency_enough($params['rule'], $obj_user_game, true);
				}
				
                $score_error_code = $this->_is_score_enough($params['rule'], $obj_user_game, true);

                //校验用户货币
                if ($currency_error_code == 1) 
                {
                	if ($params['rule']['pay_type'] == 3) 
                	{
						$response = $this->_debugLog(8,'notice',__LINE__); break;
                	}
                	else
                	{
						$response = $this->_debugLog(4,'notice',__LINE__); break;
                	}
                }

                if ($score_error_code == 1) 
                {
					$response = $this->_debugLog(7,'notice',__LINE__); break;
                }

				if(!empty($obj_user_game->room))
				{
					$tcp_s_old = $this->_get_tcp_s($obj_user_game->room, $params['c_version'], $params['platform']);
					//去查看tcp服务器房间状态
					$client = $this->_bind_rid($tcp_s_old[1], $obj_user_game->room);
					if(!$client)
					{
						$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
					}

					$client->send(self::tcp_encode(json_encode(array('act'=>'c_get_room', 'rid'=>$obj_user_game->room, 'uid'=>$params['uid']))));
					$result = self::tcp_decode($client->recv());
					$result = json_decode($result, true);
					$client->close();

					if(!empty($result['info']) && $result['info'] == 'c_get_room' && ($result['code'] == 4))
					{
						//如果tcp游戏正在进行则不能开房
						$response = $this->_debugLog(2,'notice',__LINE__); break;
					}
					else if($result['code'] == 2 || $result['code'] == 1 || $result['code'] == 5)
					{
						//更新room数据库
						$this->_set_room($obj_user_game->room, 1);
						//如果tcp服务器游戏已经结束则更新本tcp room 本用户数据
						$obj_user_game->room = 0;
					}
					else if($result['code'] == 3 || $result['code'] == 0)
					{
						$obj_user_game->room = 0;
					}
				}
			}
			else
			{
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			//取得房间号之前判断VIP是否过期
			if (defined("bigcat\\conf\\Config::VIP_START") && Config::VIP_START==1)
			{
				if($itime > $obj_user_game->vip_overtime)
				{
					//vip会员过期  不能开房间
					$response = $this->_debugLog(12,'notice',__LINE__); break;
				}
			}

			//取得空闲room号
			$obj_room_list_factory = new RoomListFactory($this->cache_handler, 1);
			if($obj_room_list_factory->initialize() && $obj_room_list_factory->get())
			{
				$obj_room_list = $obj_room_list_factory->get();
				$key = array_rand($obj_room_list);
				$rid = intval($obj_room_list[$key]);
				if($this->cache_handler->setKeep($rid, 1, 2))	//锁这个rid 2秒时间用于开房间
				{
					unset($obj_room_list[$key]);
					$obj_room_list_factory->writeback();
				}
				else
				{
					$response = $this->_debugLog(3,'notice',__LINE__); break;
				}
			}

			if(!$rid)
			{
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			//确定tcp服务器地址
			$tcp_s = $this->_get_tcp_s($rid, $params['c_version'], $params['platform']);
			//去tcp确认本rid是空闲的并开房
			$client = $this->_bind_rid($tcp_s[1], $rid);
			if(!$client)
			{
				$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
			}

			if (!empty($params['opend_status']) && $params['rule']['pay_type'] == 3) 
			{
				$client->send(self::tcp_encode(json_encode(array('act'=>'c_open_room', 'rid'=>$rid, 'uid'=>$params['uid'], 'rule'=>$params['rule'], 'game_type'=>$params['game_type'], 'opend_status' => $params['opend_status']))));
			}
			else
			{
				$client->send(self::tcp_encode(json_encode(array('act'=>'c_open_room', 'rid'=>$rid, 'uid'=>$params['uid'], 'rule'=>$params['rule'], 'game_type'=>$params['game_type']))));
			}
			
			$result = self::tcp_decode($client->recv());
			$result = json_decode($result, true);
			$client->close();
			if(!empty($result['info']) && $result['info'] == 'c_open_room' && $result['code'] == 0)
			{
				//更新user_game数据库
				$obj_user_game->room = $rid;
				$obj_user_game->is_room_owner = 1;
				$obj_user_game->last_game_time = $itime;
				$rawsqls[] = $obj_user_game->getUpdateSql();

				//更新room数据库
				$this->_set_room($rid, 2);
			}
			else
			{
				$response = $this->_debugLog(3,'notice',__LINE__); break;
			}
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$obj_user_game_factory->clear();

			$data['obj_user_game'] = $obj_user_game;
			$data['rid'] = $rid;
			$data['rid_key'] = $this->_get_rid_key($rid);
			$data['tcp_s'] = $tcp_s;
			$response['data'] = $data;

			$this->cache_handler->del(self::$user_game_key.$params['uid'], self::$user_game_key.$params['uid']);

		}while(false);
		return $response;
	}

	//登录游戏
	public function login($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if(!empty($params['code']) )
			{
				if(!defined("bigcat\\conf\\Config::WX_APPID") || !defined("bigcat\\conf\\Config::WX_SECRET"))
				{
					$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
				}
				
				$user_auth = BaseFunction::code_get_wx_user_token(Config::WX_APPID, Config::WX_SECRET, $params['code']);
				if($user_auth && !empty($user_auth['openid']) && !empty($user_auth['access_token']))
				{
					$params['openid'] = $user_auth['openid'];
					$params['access_token'] = $user_auth['access_token'];
				}
			}

			if( empty($params['access_token'])
			|| empty($params['openid'])
			)
			{
				$response['access_token'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			if(empty($params['type']))
			{
				$wx_user_info = BaseFunction::code_get_wx_user($params['access_token'], $params['openid']);
				/*BaseFunction::logger($this->log, "wx_user_info:\n".var_export($wx_user_info, true)."\n".__LINE__."\n");*/
			}
			else
			{
				$wx_user_info['unionid'] = $params['openid'];
				$wx_user_info['openid'] = $params['openid'];
				$wx_user_info['headimgurl'] = "https://wx.qlogo.cn/mmopen/OxUBpiaYgpHg0gjXEIscHkja5LFicxDeRpHILClC86zf9eM73najlswllMkIhSc0y4upOuh2ZgMPWPSS53yRx9b5L0cMNT5DuK/132";
				$nickname_arr = array('墨浩\初', '念景\逸', '愈昆\纬', '兆玉\轩', '傻大\猫', '穆含\之', '植书\白', '侨勇\男', '皇问\凝', '肖逸\云'
				,'邵山\雁', '拱夜\柳', '高初\阳', '柴清\晖', '铁半\雪', '中青\易', '五夏\烟', '鱼锐\翰', '方问\春', '漆秋\阳');
				$nickname_key = array_rand($nickname_arr, 1);
				$wx_user_info['nickname'] = $nickname_arr[$nickname_key];
				$sex_arr = array(1,2);
				//$sex_arr = array(2);
				$sex_key = array_rand($sex_arr, 1);
				$wx_user_info['sex'] = $sex_arr[$sex_key];
				$wx_user_info['city'] = "Changping";
				$wx_user_info['province'] = "Beijing";
			}

			if(!$wx_user_info || empty($wx_user_info['unionid']))
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}
			
			$wx_user_info['headimgurl'] = empty($wx_user_info['headimgurl']) ? Config::ICON_URI : $wx_user_info['headimgurl'];
            $wx_user_info['nickname'] = empty($wx_user_info['nickname']) ? '灵飞棋牌' : $wx_user_info['nickname'];
            $wx_user_info['sex'] = empty($wx_user_info['sex']) ? 1 : $wx_user_info['sex'];
            $wx_user_info['real_name_reg'] = empty($wx_user_info['real_name_reg']) ? '灵飞棋牌' : $wx_user_info['real_name_reg'];
            $wx_user_info['city'] = empty($wx_user_info['city']) ? '北京' : $wx_user_info['city'];
            $wx_user_info['province'] = empty($wx_user_info['province']) ? '北京' : $wx_user_info['province'];

			$wx_user_info['headimgurl'] = str_replace("http://", "https://", $wx_user_info['headimgurl']);
			if (strripos($wx_user_info['headimgurl'],'/')) 
			{
				$wx_user_info['headimgurl'] = substr($wx_user_info['headimgurl'], 0, strripos($wx_user_info['headimgurl'],'/'));
				$wx_user_info['headimgurl'] = $wx_user_info['headimgurl'].'/132';
			}

			$this->_init_cache();
			$key = substr(md5($itime), 0, 6);

			$obj_user_list_factory = new UserListFactory($this->cache_handler, $wx_user_info['unionid']);
			if(!$obj_user_list_factory->initialize() || !$obj_user_list_factory->get())
			{
				$obj_user_list_factory = new UserListFactory($this->cache_handler, $wx_user_info['openid']);
			}
			
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$obj_user_list = $obj_user_list_factory->get();

				$obj_user_factory = new UserFactory($this->cache_handler, current($obj_user_list));
				if($obj_user_factory->initialize() && $obj_user_factory->get())
				{
					$user = $obj_user_factory->get();
					if(!empty($user->status))
					{
						$response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$params); break;
					}

					//新邀请的用户
					if (empty($user->key) && empty($user->login_time)) 
					{
						$obj_user_game_factory = new UserGameFactory($this->cache_handler, $user->uid);
						if ($obj_user_game_factory->initialize() && $obj_user_game_factory->get()) 
						{
							$obj_user_game = $obj_user_game_factory->get();
							$this->add_message(['uid'=>$user->uid, 'type'=>1, 'inviter'=> $obj_user_game->inviter]);
						}
						else
						{
							$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
						}
					}

					if (empty($user->key)) 
					{
						$user->key = $key;
					}
					$user->wx_openid = $wx_user_info['unionid'];
					$user->wx_pic = $wx_user_info['headimgurl'];
					if(empty($params['type']))
					{
						$user->name = $wx_user_info['nickname'];
					}
					$user->sex = $wx_user_info['sex'];
					$user->city = $wx_user_info['city'];
					$user->province = $wx_user_info['province'];
					$user->login_time = $itime;
					$rawsqls[] = $user->getUpdateSql();
				}
				else
				{
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}
			else
			{
				$uid = Uid::get_uid();

				$user = new User();
				$user->uid = $uid;
				$user->key = $key;
				$user->wx_openid = $wx_user_info['unionid'];
				$user->wx_pic = $wx_user_info['headimgurl'];
				$user->name = $wx_user_info['nickname'];
				$user->sex = $wx_user_info['sex'];
				$user->city = $wx_user_info['city'];
				$user->province = $wx_user_info['province'];
				$user->init_time = $itime;
				$user->update_time = $itime;
				$user->login_time = $itime;
				$rawsqls[] = $user->getInsertSql();
				
				$user_game = new UserGame();
				$user_game->uid = $uid;
				$user_game->currency = Config::FIRST_CURRENCY;
				$user_game->update_time = $itime;
				$user_game->agent_id = '8611111111111';
				$rawsqls[] = $user_game->getInsertSql();

				//新增message
				if (defined("bigcat\\conf\\Config::NEW_USER_MESSAGE") && Config::NEW_USER_MESSAGE) 
				{
					$this->add_message(['uid'=>$uid, 'type'=>21, 'inviter'=>'']);
				}

				$obj_user_list_factory->clear();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if(isset($obj_user_factory))
			{
				$obj_user_factory->clear();
			}

			if(defined("bigcat\\conf\\Config::BLACK_LIST") && in_array($user->uid,Config::BLACK_LIST))
			{
				$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
			}
			
            if (!empty($params['code']))
            {
                $user->pay_openid = $params['openid'];
            }

			$data['user'] = $user;
			$response['data'] = $data;

		}while(false);
		return $response;
	}

	//检验用户登录状态（key值）
	public function login_check($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$is_login = 0;
			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			$obj_user = $obj_user_factory->get();
			if(!empty($obj_user->status))
			{
				$response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$params); break;
			}

			if( (isset($obj_user->key) && $obj_user->key == $params['key'] && ($itime - $obj_user->login_time) < $this->login_timeout)
				|| (defined("bigcat\\conf\\Config::PASS_KEY") && Config::PASS_KEY && $params['key'] == Config::PASS_KEY )
				)
			{
				$is_login = 1;
			}
			else
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$data['is_login'] = $is_login;
			$data['user'] = $obj_user;
			$response['data'] = $data;
		}while(false);

		return $response;
	}
	//退出登录状态
	public function logout($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();
			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			$obj_user = $obj_user_factory->get();
			// if( !empty($obj_user->key) && $obj_user->key != $params['key'])
			// {
			// 	$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			// }
			$obj_user->key = '';
			$obj_user->last_game_time = $itime;

			$rawsqls[] = $obj_user->getUpdateSql();
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}
			$obj_user_factory->writeback();
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//获取用户信息
	public function get_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$params['uid'] = intval($params['uid']);
			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			$obj_user = $obj_user_factory->get();

			do
			{
				$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$params['uid'], 1, 1);
			}while (!$tmp_user_game_lock);
			
			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_game_factory->initialize() || !$obj_user_game_factory->get())
			{
				$obj_user_game_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			$obj_user_game = $obj_user_game_factory->get();

			$tcp_s_old = array();
			$is_update = false;

			if(!empty($obj_user_game->room))
			{
				$tcp_s_old = $this->_get_tcp_s($obj_user_game->room, $params['c_version'], $params['platform']);

				//去查看tcp服务器房间状态
				$client = $this->_bind_rid($tcp_s_old[1], $obj_user_game->room);
				if(!$client)
				{
					$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
				}

				$client->send(self::tcp_encode(json_encode(array('act'=>'c_get_room', 'rid'=>$obj_user_game->room, 'uid'=>$params['uid']))));
				$result = self::tcp_decode( $client->recv());
				$result = json_decode($result, true);

				$client->close();
				
				if(!empty($result['info']) && $result['info'] == 'c_get_room' && $result['code'] == 4)
				{
					//如果tcp游戏正在进行则直接进入
					;
				}
				else if($result['code'] == 2 || $result['code'] == 1)	//房间是空闲状态
				{
					//更新room数据库
					$this->_set_room($obj_user_game->room, 1);
					//如果tcp服务器游戏已经结束则更新本tcp room 本用户数据
					$obj_user_game->room = 0;
					$obj_user_game->is_room_owner = 0;
					$is_update = true;
				}
				else if($result['code'] == 3 || $result['code'] == 0|| $result['code'] == 5)	//房间已满或未满，不包含本用户
				{
					$obj_user_game->room = 0;
					$obj_user_game->is_room_owner = 0;
					$is_update = true;
				}
				if($is_update)
				{
					$rawsqls[] = $obj_user_game->getUpdateSql();
				}
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}
			
			//公会房Linfiy码
			$obj_user->linfiy_code = $this->_get_encrypt_uid($obj_user_game->uid, $obj_user_game->agent_id);
			if(isset($obj_user_game->agent_id))
			{
				$obj_user_game->agent_id = 'none';
			}

			//vip信息
			if(isset($obj_user_game->vip_overtime))
			{
				if( $obj_user_game->vip_overtime <= $itime )
				{
					$obj_user_game->vip_overtime ="0天0时";
				}
				else
				{
					$str='';
					$day =  floor( ($obj_user_game->vip_overtime-time())/86400);
					$hour = floor( (($obj_user_game->vip_overtime-time())  %86400)/3600);
					$obj_user_game->vip_overtime = $day."天".$hour."时";
				} 
			}

			if($is_update)
			{
				$obj_user_game_factory->clear();
			}

			$data['obj_user'] = $obj_user;
			$data['obj_user_game'] = $obj_user_game;
			$data['rid_key'] = $this->_get_rid_key($obj_user_game->room);
			$data['tcp_s'] = $tcp_s_old;
			$response['data'] = $data;

			$this->cache_handler->del(self::$user_game_key.$params['uid'], self::$user_game_key.$params['uid']);

		}while(false);

		return $response;
	}

	//获取user_game信息
	public function getUserGame($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if(empty($params['uid']))
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$params['uid'] = intval($params['uid']);

			$this->_init_cache();
			
			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_game_factory->initialize() || !$obj_user_game_factory->get())
			{
				$obj_user_game_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			$obj_user_game = $obj_user_game_factory->get();

			unset($obj_user_game->update_time);
			unset($obj_user_game->last_game_time);
			unset($obj_user_game->sum_money);
			unset($obj_user_game->sum_currency);
			unset($obj_user_game->reward_state);
			unset($obj_user_game->inviter);
			unset($obj_user_game->bind_time);

			$data['obj_user_game'] = $obj_user_game;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//根据房间号获取  用户信息
	public function get_room_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$result = array();
		$data = array();

		do {
			if( empty($params['room'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$params['room'] = intval($params['room']);

			$this->_init_cache();
			// do
			// {
			// 	$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$params['room'], 1, 1);
			// }while (!$tmp_user_game_lock);

			$sql = "select `uid` from `user_game` where room =".$params['room']." and is_room_owner = 1";
			$key = "room".$params['room'];

			$obj_usergame_list_factory = new UserGameListFactory($this->cache_handler,null,null, $sql, $key);
			if($obj_usergame_list_factory->initialize() && $obj_usergame_list_factory->get())
			{
				$obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler, $obj_usergame_list_factory);
				if($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get())
				{
					$obj_user_game_multi = $obj_user_game_multi_factory->get();
					$obj_user_game_multi_item = current($obj_user_game_multi);

					$tmp_owner_uid = $obj_user_game_multi_item->uid;
					$params['uid'] =$tmp_owner_uid;
					$result = $this->get_user($params);
					if(!empty($result['data']))
					{
						$data = $result['data'];
					}
				}
				else
				{
					$obj_user_game_multi_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}
			else
			{
				$obj_usergame_list_factory->clear();
			}

			$response['data'] = $data;
			//$this->cache_handler->del(self::$user_game_key.$params['room'], self::$user_game_key.$params['room']);

		}while(false);

		return $response;
	}

	//实名登记
	public function real_name_reg($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['real_name_reg'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$params['uid'] = intval($params['uid']);

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			$obj_user = $obj_user_factory->get();
			$obj_user->real_name_reg = $params['real_name_reg'];
			$rawsqls[] = $obj_user->getUpdateSql();

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$obj_user_factory->clear();

			$data['obj_user'] = $obj_user;

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//获取 game_log
	public function get_game_log($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response['sub_code'] = 1; $response['desc'] = __LINE__; break;
			}

			if(empty($params['page']))
			{
				$params['page'] = 1;
			}

			$count_per_page = 20;

			$data['all_count'] = 0;
			$data['obj_game_log'] = array();
			$data['count_per_page'] = $count_per_page;

			$this->_init_cache();
			if(!empty($params['log_num']))
			{
				$params['log_num'] = strval($params['log_num']);
			
				$obj_game_log_multi_factory = new GameLogMultiFactory($this->cache_handler, null, $params['log_num']);
				if($obj_game_log_multi_factory->initialize() && $obj_game_log_multi_factory->get())
				{
					$obj_game_log_multi = $obj_game_log_multi_factory->get();
					if(is_array($obj_game_log_multi) && $obj_game_log_multi)
					{
						$data['obj_game_log'] = array_values($obj_game_log_multi);					
					}
				}
				else
				{
					$obj_game_log_multi_factory->clear();
					$response['sub_code'] = 2; $response['desc'] = __LINE__; break;
				}		
			}
			else
			{
				$params['uid'] = intval($params['uid']);
				$obj_game_log_user_list_factory = new GameLogUserListFactory($this->cache_handler, $params['uid']);
				if($obj_game_log_user_list_factory->initialize() && $obj_game_log_user_list_factory->get())
				{
					$obj_game_log_user_list = $obj_game_log_user_list_factory->get();
					$data['all_count'] = count($obj_game_log_user_list);
					$id_page_arr = array_slice($obj_game_log_user_list, ($params['page'] - 1) * $count_per_page, $count_per_page);
					if ($id_page_arr && is_array($id_page_arr))
					{
						$obj_game_log_list_factory = new GameLogListFactory($this->cache_handler, null, implode(',', $id_page_arr));
						if($obj_game_log_list_factory->initialize() && $obj_game_log_list_factory->get())
						{
							$obj_game_log_multi_factory = new GameLogMultiFactory($this->cache_handler, $obj_game_log_list_factory);
							if($obj_game_log_multi_factory->initialize() && $obj_game_log_multi_factory->get())
							{
								$obj_game_log_multi = $obj_game_log_multi_factory->get();
								$obj_game_log_multi = array_values($obj_game_log_multi);
	                			usort($obj_game_log_multi,array('bigcat\controller\Business','cmp_log_id'));
								if(is_array($obj_game_log_multi) && $obj_game_log_multi)
								{
									$data['obj_game_log'] = array_values($obj_game_log_multi);
								}
							}
							else
							{
								$obj_game_log_multi_factory->clear();
							}
						}
						else
						{
							$obj_game_log_list_factory->clear();
						}
					}
				}
				else
				{
					$obj_game_log_user_list_factory->clear();
				}
			}

			if($data['obj_game_log'] && is_array($data['obj_game_log'] ))
			{
				foreach ($data['obj_game_log']  as $key => $value)
				{
					if(defined("bigcat\\conf\\Config::NO_LOG_GAME_TYPE") && in_array($value->game_type, Config::NO_LOG_GAME_TYPE))
					{
						unset($data['obj_game_log'][$key]);
						continue;
					}
					$data['obj_game_log'][$key]->game_info = json_decode($data['obj_game_log'][$key]->game_info);
				}
				$data['obj_game_log'] = array_values($data['obj_game_log']);
			}
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function share_currency($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$sum = 0;

		do {
			if( empty($params['uid']))
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();
			$params['uid'] = intval($params['uid']);
			$type = 3;	//分享奖励
			$time_today = strtotime(date('Y-m-d', $itime));
			$obj_user_log_list_factory = new UserLogListFactory($this->cache_handler, $params['uid'], $type, $time_today);
			if($obj_user_log_list_factory->initialize() && $obj_user_log_list_factory->get())
			{
				$obj_user_log_list = $obj_user_log_list_factory->get();
				$sum = count($obj_user_log_list);
				if(defined("bigcat\\conf\\Config::SHARE_CURRENCY_TIMES") && $sum >= Config::SHARE_CURRENCY_TIMES)
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}
				else
				{
					if(defined("bigcat\\conf\\Config::SHARE_CURRENCY") && Config::SHARE_CURRENCY > 0)
					{
						$tmp_params = array('uid'=>$params['uid'], 'type'=>$type, 'currency'=>Config::SHARE_CURRENCY);
						$this->checkout_open_room($tmp_params);
					}
					else
					{
						$response = $this->_debugLog(3,'notice',__LINE__); break;
					}
				}
			}
			else
			{
				if(defined("bigcat\\conf\\Config::SHARE_CURRENCY") && Config::SHARE_CURRENCY > 0)
				{
					$tmp_params = array('uid'=>$params['uid'], 'type'=>$type, 'currency'=>Config::SHARE_CURRENCY);
					$this->checkout_open_room($tmp_params);
				}
				else
				{
					$response = $this->_debugLog(3,'notice',__LINE__); break;
				}
			}
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//收藏战绩或取消收藏
	public function get_game_log_save($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['log_num'])
			|| empty($params['save_type'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$data['obj_game_log'] = array();

			$this->_init_cache();
			$params['uid'] = intval($params['uid']);
			$obj_game_log_user_list_factory = new GameLogUserListFactory($this->cache_handler, $params['uid']);
			if($obj_game_log_user_list_factory->initialize() && $obj_game_log_user_list_factory->get())
			{
				$obj_game_log_user_list = $obj_game_log_user_list_factory->get();
				if(in_array($params['log_num'], $obj_game_log_user_list))
				{
					$obj_game_log_multi_factory = new GameLogMultiFactory($this->cache_handler, null, $params['log_num']);
					if($obj_game_log_multi_factory->initialize() && $obj_game_log_multi_factory->get())
					{
						$obj_game_log_multi = $obj_game_log_multi_factory->get();
						if(is_array($obj_game_log_multi) && $obj_game_log_multi)
						{
							$obj = current($obj_game_log_multi);
							$tmp_arr = explode(',', $obj->save);
							if($params['save_type'] == 1)
							{
								if(array_search($params['uid'], $tmp_arr) === false)
								{
									$tmp_arr[] = $params['uid'];
								}
							}
							elseif($params['save_type'] == 2)
							{
								if(array_search($params['uid'], $tmp_arr) !== false)
								{
									array_splice($tmp_arr, array_search($params['uid'], $tmp_arr), 1);
								}
							}
							else
							{
								$response = $this->_debugLog(CatConstant::ERROR_ARGUMENT,'error',__LINE__,$params); break;
							}
							$obj->save = implode(',', $tmp_arr);
							$rawsqls[] = $obj->getUpdateSql();
							if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
							{
								$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$params); break;
							}
							$data['obj_game_log'][] = $obj;
						}
					}
					else
					{
						$obj_game_log_multi_factory->clear();
					}
					$obj_game_log_multi_factory->clear();
				}
			}
			else
			{
				$obj_game_log_user_list_factory->clear();
			}
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	///////////////////////////////////////////////
	//写数据库表log
	public function set_game_log($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['rid'])
			|| empty($params['uid_arr'])
			|| empty($params['game_info'])
			|| empty($params['type'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$params['uid_arr_arr'] = explode(',', $params['uid_arr']);
			
			//处理每局记录录像
			if($params['game_info'] != 255)
			{
				$obj_game_log = new GameLog();
				$obj_game_log->rid = $params['rid'];
				$obj_game_log->uid = $params['uid_arr_arr'][0];
				$obj_game_log->type = $params['type'];
				$obj_game_log->game_info = $params['game_info'];
				$obj_game_log->time = $itime;
				$obj_game_log->game_type = empty($params['game_type']) ? 0 : $params['game_type'];
				$obj_game_log->play_time = empty($params['play_time']) ? 0 : $params['play_time'];
				$insert_sql = array();
				$insert_sql[] = $obj_game_log->getInsertSql();

				$insert_result = BaseFunction::execute_sql_backend($insert_sql);

				if(empty($insert_result[0]['insert_id']))
				{
					$response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$params); break;
				}

				$this->_init_cache();

				foreach ($params['uid_arr_arr'] as $uid_item)
				{
					if($uid_item == 0)
					{
						continue;
					}

					$obj_game_log_user = new GameLogUser();
					$obj_game_log_user->game_log_id = $insert_result[0]['insert_id'];
					$obj_game_log_user->uid = $uid_item;
					$rawsqls[] = $obj_game_log_user->getInsertSql();
				}

				if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
				{
					$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
				}
			}

			//处理房间解散
			if (!empty($params['is_room_over'])) 
			{
				$rawsqls = array();
				$this->_set_room($params['rid'], 1);

				//更新user信息
				foreach ($params['uid_arr_arr'] as $uid_item)
				{
					do
					{
						$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$uid_item, 1, 1);
					}while (!$tmp_user_game_lock);
				}

				$obj_user_game_list_factory = new UserGameListFactory($this->cache_handler, null, $params['uid_arr']);
				if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
				{
					$obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler, $obj_user_game_list_factory);
					if ($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get()) 
					{
						$obj_user_game_multi = $obj_user_game_multi_factory->get();
						foreach ($obj_user_game_multi as $obj_user_game_multi_item) 
						{
							if (in_array($obj_user_game_multi_item->uid, $params['uid_arr_arr'])) 
							{
								$obj_user_game_multi_item->last_game_time = $itime;
								$obj_user_game_multi_item->room = 0;
								$obj_user_game_multi_item->is_room_owner = 0;
								if ($params['is_room_over'] == 1 && !empty($obj_user_game_multi_item->reward_state) && !empty($obj_user_game_multi_item->inviter)) 
								{
									$obj_user_game_multi_item->reward_state = json_decode($obj_user_game_multi_item->reward_state);
									if ($obj_user_game_multi_item->reward_state->complete_game < 10) 
									{
										$obj_user_game_multi_item->reward_state->complete_game += 1;
										if ($obj_user_game_multi_item->reward_state->complete_game == 10) 
										{
											$this->add_message(['uid'=>$obj_user_game_multi_item->uid, 'type'=>3, 'inviter'=> $obj_user_game_multi_item->inviter]);
										}
									}

									$obj_user_game_multi_item->reward_state = json_encode($obj_user_game_multi_item->reward_state);
								}
								$rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
							}
						}
					}
					else
					{
						$obj_user_game_multi_factory->clear();
						$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
					}
				}
				else
				{
					$obj_user_game_list_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}

				//更新战绩信息
				if(1 == $params['is_room_over'] && !empty($params['game_table_info']))
				{
					foreach ($params['uid_arr_arr'] as $uid_item)
					{
						if($uid_item == 0)
						{
							continue;
						}
		
						$obj_gametablelog = new GameTableLog();
						$obj_gametablelog->rid = $params['rid'];
						$obj_gametablelog->uid = $uid_item;
						$obj_gametablelog->game_table_info = $params['game_table_info'];
						$obj_gametablelog->time = $itime;
						$rawsqls[] = $obj_gametablelog->getInsertSql();
					}

					if (!empty($params['agent_uid'])) 
					{	
						$obj_user_game_agent_factory = new UserGameFactory($this->cache_handler, $params['agent_uid']);
						if($obj_user_game_agent_factory->initialize() && $obj_user_game_agent_factory->get())
						{
							$obj_user_game_agent = $obj_user_game_agent_factory->get();
						}
						else
						{
							$obj_user_game_agent_factory->clear();
							$response = $this->_debugLog(1,'notice',__LINE__); break;
						}
						$obj_gametablelog = new GameTableLog();
						$obj_gametablelog->rid = $params['rid'];
						$obj_gametablelog->uid = $obj_user_game_agent->agent_id;
						$obj_gametablelog->game_table_info = $params['game_table_info'];
						$obj_gametablelog->time = $itime;
						$rawsqls[] = $obj_gametablelog->getInsertSql();
					}
				}

				if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
				{
					$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
				}

				if(isset($obj_user_game_multi_factory))
				{
					$obj_user_game_multi_factory->clear();
				}

				foreach ($params['uid_arr_arr'] as $uid_item)
				{
					if($uid_item == 0)
					{
						continue;
					}
					$this->cache_handler->del(self::$user_game_key.$uid_item, self::$user_game_key.$uid_item);
				}
			}

			if (rand(1,100) == 1) 
			{
				$this->deleteExpiredLog($params['uid_arr']);
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//充钻或者扣钻
	public function checkout_open_room($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['currency'])
			|| empty($params['type'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();

			if (isset($params['currency_change_group'])) 
			{
				$response = $this->record_currency_changes($params);
				if (!empty($response['code']) || !empty($response['sub_code'])) 
				{
					break;
				}
			}
			else
			{
				do
				{
					$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$params['uid'], 1, 1);
				}while (!$tmp_user_game_lock);

				$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
				if(!$obj_user_game_factory->initialize() || !$obj_user_game_factory->get())
				{
					$obj_user_game_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
				$obj_user_game = $obj_user_game_factory->get();
				$obj_user_log = new UserLog();

				$params['money'] = isset($params['money']) ? $params['money'] : 0;
				$params['aid'] = $obj_user_game->agent_id;

				$old_currency = $this->getUserGameUpdateSql($obj_user_game, $params);
				$rawsqls[] = $this->getUserLogSql($obj_user_log, $old_currency, $params);
				$rawsqls[] = $obj_user_game->getUpdateSql();

				if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
				{
					$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
				}

				$obj_user_game_factory->clear();
				$response['data'] = $data;

				$this->cache_handler->del(self::$user_game_key.$params['uid'], self::$user_game_key.$params['uid']);
			}

		}while(false);

		return $response;
	}

	//绑定推广员
	public function bind_agent_id($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['agent_id'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$params['uid'] = intval($params['uid']);
			$this->_init_cache();

			//判断agent_id是否为一个代理
			$data_request = array(
			'mod' => 'Business'
			, 'act' => 'agent_info_test'
			, 'platform' => 'gfplay'
			, 'aid' => $params['agent_id']
			);
			$randkey = BaseFunction::encryptMD5($data_request);
			$url = Config::FAIR_AGENT_PATH . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));
			if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0)) {
				BaseFunction::logger($this->log, "【data_request】:\n" . var_export($data_request, true) . "\n" . __LINE__ . "\n");
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			//绑定
			$obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler,'',$params['uid']);
			if($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get())
			{
				$obj_user_game_multi = $obj_user_game_multi_factory->get();
				$obj_user_game_multi_item = current($obj_user_game_multi);
				if(!empty($obj_user_game_multi_item->agent_id ))
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}

				$obj_user_game_multi_item->agent_id = $params['agent_id'];
				$obj_user_game_multi_item->bind_time = $itime;

				$rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$obj_user_game_multi_factory->writeback();

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	public function agent_info($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if(empty($params['agent_id']))
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			if ($params['agent_id'] == 'none') 
			{
				$response['data'] = ['agent_info' => ['aid'=>'8611111111111','wx_id'=>'test','opend_status' => 999999]];break;
			}

			$this->_init_cache();

			$obj_agent_factory = new AgentFactory($this->cache_handler, $params['agent_id']);
			if(!$obj_agent_factory->initialize() || !$obj_agent_factory->get())
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}
			$obj_agent = $obj_agent_factory->get();
			$response['data'] = $obj_agent->agent;

		}while(false);

		return $response;
	}

	//支付宝  微信  处理回调,充值
	public function remote_call_back($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$data = array();
		$params_tmp = array();

		do{
			if(empty($params['out_trade_no'])
			||empty($params['total_fee'])
			||empty($params['call_back_param'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$params_arr =json_decode($params['call_back_param'],true);
			$total_fee = $params['total_fee'];
			//vip会员
			if( !empty($params_arr['currency_type']) && $params_arr['currency_type'] >=100 )
			{
				$params_tmp = array(
					'uid' => $params_arr['aid']
					, 'vip_type' => $params_arr['currency_type']
					, 'money' => $total_fee					
				);	
				
				$result = $this->buy_vip($params_tmp);
				
			}
			else//钻石购买
			{
				if(!empty($params_arr['currency_type']) && $params_arr['currency_type'] == 2 )
				{
					$type = 22;
				}
				else
				{
					$type = 4;
				}
	
				$params_tmp = array(
					'uid' => $params_arr['aid']
					, 'type' => $type
					, 'currency' => $params_arr['amount']
					, 'money' => $total_fee
				);

				$result = $this->checkout_open_room($params_tmp);
			}



			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0))
			{
				$response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$result); break;
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//获取每桌分数log
	public function get_table_log($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$data = array();
		$page_count = CatConstant::CNT_PER_PAGE;
		$data_count = 0;
		$tmp = array();

		do {
			if(empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['page'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$this->_init_cache();
			$page = isset($params['page']) ? intval($params['page']) : 1;

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$obj_gametablelog_list_factory = new GameTableLogListFactory($this->cache_handler,$params['uid']);
			if($obj_gametablelog_list_factory->initialize() && $obj_gametablelog_list_factory->get())
			{
				$obj_gametablelog_list = $obj_gametablelog_list_factory->get();
				$data_count = count($obj_gametablelog_list);
				$obj_gametablelog_list_page_arr = array_slice($obj_gametablelog_list, ($page - 1) * $page_count, $page_count);

				$obj_gametablelog_list_factory = new GameTableLogListFactory($this->cache_handler,null,implode(',',$obj_gametablelog_list_page_arr));
				if($obj_gametablelog_list_factory->initialize() && $obj_gametablelog_list_factory->get())
				{
					$obj_gametablelog_multi_factory =  new GameTableLogMultiFactory($this->cache_handler,$obj_gametablelog_list_factory);
					if($obj_gametablelog_multi_factory->initialize() && $obj_gametablelog_multi_factory->get())
					{
						$obj_gametablelog_multi = array_values($obj_gametablelog_multi_factory->get());
						usort($obj_gametablelog_multi,array('bigcat\controller\Business','cmp_list'));
						if(is_array($obj_gametablelog_multi))
						{
							foreach($obj_gametablelog_multi as $obj_gametablelog_multi_item)
							{
								$obj_gametablelog_multi_item->game_table_info = json_decode($obj_gametablelog_multi_item->game_table_info);

								$obj_gametablelog_multi_item->display = $obj_gametablelog_multi_item->game_table_info->display;
								$obj_gametablelog_multi_item->player_count = $obj_gametablelog_multi_item->game_table_info->player_count;
								$obj_gametablelog_multi_item->set_num = $obj_gametablelog_multi_item->game_table_info->set_num;
								$obj_gametablelog_multi_item->pay_type = $obj_gametablelog_multi_item->game_table_info->pay_type;
								$obj_gametablelog_multi_item->play = $obj_gametablelog_multi_item->game_table_info->play;
								
								unset($obj_gametablelog_multi_item->game_table_info);

								$obj_gametablelog_multi_item->time = date("Y-m-d H:i:s",$obj_gametablelog_multi_item->time);
								$tmp[] = $obj_gametablelog_multi_item;
							}
						}
					}
					else
					{
						$obj_gametablelog_multi_factory->clear();
					}
				}
				else
				{
					$obj_gametablelog_list_factory->clear();
				}
			}
			else
			{
				$obj_gametablelog_list_factory->clear();
			}

			$data['data_count'] = $data_count;
            $data['page_count'] = $page_count;
			$data['list'] = $tmp;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	/////////////////////////////////////////////////////

	private function _set_room($rid, $state)
	{
		$return = false;

		if($rid && in_array($state, [1, 2, 3]))
		{

			$this->_init_cache();
			$obj_room_factory = new RoomFactory($this->cache_handler, $rid);
			if($obj_room_factory->initialize() && $obj_room_factory->get())
			{
				$obj_room = $obj_room_factory->get();
				$obj_room->state = $state;
				$rawsqls[] = $obj_room->getUpdateSql();
				if($rawsqls && BaseFunction::execute_sql_backend($rawsqls))
				{
					$obj_room_factory->writeback();
					$return = true;
				}
			}
		}

		return $return;
	}

	private function _init_cache()
	{
		if( empty($this->cache_handler) )
		{
			$tmp = CatConstant::CACHE_TYPE;
			$this->cache_handler = $tmp::get_instance();
		}
	}

	//红钻换积分
	public function get_score($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if(empty($params['uid'])
			|| empty($params['key'])
			|| empty($params['currency2'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();
				$old_currency2 = $obj_user_game->currency2;
				$old_score = $obj_user_game->score;

				if ($obj_user_game->currency2 < $params['currency2']) 
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}
				$obj_user_game->currency2 -= $params['currency2'];
				$obj_user_game->score += $params['currency2'] * Config::CURRENCY_EXCHANGE_SCORE;
				$rawsqls[] = $obj_user_game->getUpdateSql();

				//红钻消耗记录
				$obj_user_log = new UserLog();
				$params['type'] = 21;
				$params['currency'] = - $params['currency2'];
				$rawsqls[] = $this->getUserLogSql($obj_user_log, $old_currency2, $params);

				//积分增加记录
				$params['type'] = 34;
				$params['currency'] = $params['currency2'] * Config::CURRENCY_EXCHANGE_SCORE;
				$rawsqls[] = $this->getUserLogSql($obj_user_log, $old_score, $params);
			}
			else
			{ 
				$obj_user_game_factory->clear();
				$response = $this->_debugLog(3,'notice',__LINE__); break;
			}
			
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if(isset($obj_user_game_factory))
			{
				$obj_user_game_factory->writeback();
  			}
  			
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//积分奖杯兑换礼物
	public function get_gift($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$exchange_gift_info = array();

			do {
				if( empty($params['uid'])
				|| empty($params['key'])
				|| empty($params['gid'])
				|| !isset($params['receiver_name'])
				|| !isset($params['receiver_cellphone'])
				|| !isset($params['receiver_address'])
				)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();

				//需要的积分
				$need_score = Config::$gift_group[$params['gid']]['need_score'];
				$old_score = $obj_user_game->score;
				if ($obj_user_game->score < $need_score) 
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}
				//需要的奖杯
				$need_cup = Config::$gift_group[$params['gid']]['need_cup'];
				$old_cup = $obj_user_game->cup;
				
				if ($obj_user_game->cup < $need_cup) 
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}
				$obj_user_game->score -= $need_score;
				$obj_user_game->cup -= $need_cup;

				if (in_array($params['gid'], [
					Config::CURRENCY_5,
					Config::CURRENCY_20,
					Config::CURRENCY_50,
					Config::CURRENCY_100,
				])) 
				{
					$old_currency = $obj_user_game->currency;
					$obj_user_game->currency += Config::$gift_group[$params['gid']]['currency'];
				}

				$rawsqls[] = $obj_user_game->getUpdateSql();

				//新增礼物兑换记录
				$obj_gift_exchange_log = new GiftExchangeLog();
				$obj_gift_exchange_log->name = Config::$gift_group[$params['gid']]['name'];
				$obj_gift_exchange_log->picture = Config::$gift_group[$params['gid']]['picture'];
				$obj_gift_exchange_log->uid = $params['uid'];
				$obj_gift_exchange_log->receiver_name = $params['receiver_name'];
				$obj_gift_exchange_log->receiver_cellphone = $params['receiver_cellphone'];
				$obj_gift_exchange_log->receiver_address = $params['receiver_address'];
				$obj_gift_exchange_log->time = $itime;
				$obj_gift_exchange_log->update_time = $itime;
				$obj_gift_exchange_log->state = 1;
				
				if (in_array($params['gid'], [
					Config::CURRENCY_5,
					Config::CURRENCY_20,
					Config::CURRENCY_50,
					Config::CURRENCY_100,
				])) 
				{
					$remark_array = [
						'delivery_address' => $params['receiver_address'],
						'delivery_information' => '已发货'
					];
					$obj_gift_exchange_log->remark = json_encode($remark_array,JSON_UNESCAPED_UNICODE);
					$obj_gift_exchange_log->state = 3;
				}

				$rawsqls[] = $obj_gift_exchange_log->getInsertSql();

				//积分记录
				$obj_user_log = new UserLog();
				$obj_user_log->old_currency = $old_score;
				$obj_user_log->currency = -$need_score;
				$obj_user_log->uid = $params['uid'];
				$obj_user_log->type = 33;
				$obj_user_log->time = $itime;
				$rawsqls[] = $obj_user_log->getInsertSql();

				//奖杯消耗记录
				$obj_user_log->old_currency = $old_cup;
				$obj_user_log->currency = -$need_cup;
				$obj_user_log->type = 42;
				$rawsqls[] = $obj_user_log->getInsertSql();

				//积分兑蓝钻
				if (in_array($params['gid'], [
					Config::CURRENCY_5,
					Config::CURRENCY_20,
					Config::CURRENCY_50,
					Config::CURRENCY_100,
				])) 
				{
					$obj_user_log->old_currency = $old_currency;
					$obj_user_log->currency = Config::$gift_group[$params['gid']]['currency'];
					$obj_user_log->type = 5;
					$rawsqls[] = $obj_user_log->getInsertSql();
				}
			}
			else
			{
				$obj_user_game_factory->clear();
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if(isset($obj_user_game_factory))
			{
				$obj_user_game_factory->writeback();
  			}

  			if(isset($obj_rechargeable_card_factory))
			{
				$obj_rechargeable_card_factory->writeback();
  			}
  			
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//礼物兑换记录
	public function get_gift_log($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if(empty($params['uid'])
			||empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_gift_exchange_log_list_factory = new GiftExchangeLogListFactory($this->cache_handler, $params['uid']);
			if($obj_gift_exchange_log_list_factory->initialize() && $obj_gift_exchange_log_list_factory->get())
			{
				$obj_gift_exchange_log_list = $obj_gift_exchange_log_list_factory->get();
				$obj_gift_exchange_log_multi_factory = new GiftExchangeLogMultiFactory($this->cache_handler, $obj_gift_exchange_log_list_factory);
				if($obj_gift_exchange_log_multi_factory->initialize() && $obj_gift_exchange_log_multi_factory->get())
				{
					$obj_gift_exchange_log_multi = array_values($obj_gift_exchange_log_multi_factory->get());
					usort($obj_gift_exchange_log_multi,array('bigcat\controller\Business','cmp_log_id'));
					foreach ($obj_gift_exchange_log_multi as $obj_gift_exchange_log_multi_item) 
					{
						$obj_gift_exchange_log_multi_item->state = intval($obj_gift_exchange_log_multi_item->state);
						$data[] = $obj_gift_exchange_log_multi_item;
					}
				}
				else
				{
					$obj_gift_exchange_log_multi_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}
			
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	private function record_currency_changes($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$uid_group = array();

		do
		{
			$currency_change_group = json_decode($params['currency_change_group'],true);
			foreach ($currency_change_group as $uid => $value) 
			{
				do
				{
					$tmp_user_game_lock = $this->cache_handler->setKeep(self::$user_game_key.$uid, 1, 1);
				}while (!$tmp_user_game_lock);

				$uid_group[] = $uid; 
			}

			$obj_user_log = new UserLog();
			$obj_user_game_list_factory = new UserGameListFactory($this->cache_handler, null, implode(',', $uid_group));
			if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
			{
				$obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler, $obj_user_game_list_factory);
				if ($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get()) 
				{
					$obj_user_game_multi = $obj_user_game_multi_factory->get();

					foreach ($obj_user_game_multi as $obj_user_game_multi_item) 
					{
						if (isset($currency_change_group[$obj_user_game_multi_item->uid])) 
						{
							$single_user_change = $currency_change_group[$obj_user_game_multi_item->uid];
							foreach ($single_user_change as $single_change) 
							{	
								$single_change['uid'] = $obj_user_game_multi_item->uid;
								$single_change['aid'] = $obj_user_game_multi_item->agent_id;
								$old_currency = $this->getUserGameUpdateSql($obj_user_game_multi_item, $single_change);
								$rawsqls[] = $this->getUserLogSql($obj_user_log, $old_currency, $single_change);

							}

							$rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
						}
					}
				}
				else
				{
					$obj_user_game_multi_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}
			else
			{
				$obj_user_game_list_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if (isset($obj_user_game_multi_factory))
	        {
	            $obj_user_game_multi_factory->clear();
	        }

			foreach ($currency_change_group as $uid => $value) 
			{
				$this->cache_handler->del(self::$user_game_key.$uid, self::$user_game_key.$uid);
			}

		}while (false); 

		return $response;
	}

	private function getUserGameUpdateSql($obj_user_game, $params)
	{	
		if ($params['type'] < 10 || $params['type'] > 50) 
		{
			$old_currency = $obj_user_game->currency;
			$obj_user_game->currency += $params['currency'];
			if($obj_user_game->currency < 0)
			{
				$obj_user_game->currency = 0;
			}
			if(isset($obj_user_game->sum_money) && isset($obj_user_game->sum_currency))
			{
				if(4 == $params['type'] && !empty($params['money']))
				{
					$obj_user_game->sum_money += $params['money'];
					if (!empty($obj_user_game->reward_state) && !empty($obj_user_game->inviter)) 
					{
						$obj_user_game->reward_state = json_decode($obj_user_game->reward_state);
						if ($obj_user_game->reward_state->first_recharge == 0) 
						{
							$obj_user_game->reward_state->first_recharge = 1;
							$this->add_message(['uid'=>$obj_user_game->uid, 'type'=>2, 'inviter'=> $obj_user_game->inviter]);
						}

						$obj_user_game->reward_state = json_encode($obj_user_game->reward_state);
					}
				}

				if(1 == $params['type'])
				{
					$obj_user_game->sum_currency += abs($params['currency']);
				}
			}
		}
		elseif ($params['type'] > 20 && $params['type'] < 30) 
		{
			$old_currency = $obj_user_game->currency2;
			$obj_user_game->currency2 += $params['currency'];
			if($obj_user_game->currency2 < 0)
			{
				$obj_user_game->currency2 = 0;
			}
		}
		elseif ($params['type'] > 30 && $params['type'] < 40) 
		{
			$old_currency = $obj_user_game->score;
			$obj_user_game->score += $params['currency'];
			if($obj_user_game->score < 0)
			{
				$obj_user_game->score = 0;
			}
		}
		elseif ($params['type'] > 40 && $params['type'] < 50) 
		{
			$old_currency = $obj_user_game->cup;
			$obj_user_game->cup += $params['currency'];
			if($obj_user_game->cup < 0)
			{
				$obj_user_game->cup = 0;
			}
		}
		
		return $old_currency;
	}

	private function getUserLogSql($obj_user_log, $old_currency, $params)
	{
		$obj_user_log->old_currency = $old_currency;
		$obj_user_log->currency = $params['currency'];
		$obj_user_log->uid = $params['uid'];
		$obj_user_log->type = $params['type'];
		$obj_user_log->time = time();
		if(isset($params['money']))
		{
			$obj_user_log->money = $params['money'];
		}

		if (isset($params['aid']) && ($params['type'] == 4 || $params['type'] == 1)) 
		{
			$obj_user_log->aid = $params['aid'];
		}

		return $obj_user_log->getInsertSql();
	}

    //公会房开房记录
    public function agent_open_room_log($params)
    {
    	$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();
		$data_count = 0;
		$page_count = CatConstant::CNT_PER_PAGE;
		$tmp = array();

		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
				|| empty($params['agent_id'])
				|| empty($params['page'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$this->_init_cache();

			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$page = isset($params['page']) ? intval($params['page']) : 1;

			$agentInfo = $this->agent_info($params);
			if (!empty($agentInfo['code']) || !empty($agentInfo['sub_code'])) 
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}
			$agentInfo = $agentInfo['data']->agent_info;
			
			if (!empty($agentInfo->opend_status) && $params['uid'] == $agentInfo->opend_status) 
			{
				$obj_gametablelog_list_factory = new GameTableLogListFactory($this->cache_handler,$params['agent_id']);
				if($obj_gametablelog_list_factory->initialize() && $obj_gametablelog_list_factory->get())
				{
					$obj_gametablelog_list = $obj_gametablelog_list_factory->get();
					$data_count = count($obj_gametablelog_list);
					$obj_gametablelog_list_page_arr = array_slice($obj_gametablelog_list, ($page - 1) * $page_count, $page_count);

					$obj_gametablelog_list_factory = new GameTableLogListFactory($this->cache_handler,null,implode(',',$obj_gametablelog_list_page_arr));
					if($obj_gametablelog_list_factory->initialize() && $obj_gametablelog_list_factory->get())
					{
						$obj_gametablelog_multi_factory =  new GameTableLogMultiFactory($this->cache_handler,$obj_gametablelog_list_factory);
						if($obj_gametablelog_multi_factory->initialize() && $obj_gametablelog_multi_factory->get())
						{
							$obj_gametablelog_multi = array_values($obj_gametablelog_multi_factory->get());
							usort($obj_gametablelog_multi,array('bigcat\controller\Business','cmp_list'));
							if(is_array($obj_gametablelog_multi))
							{
								foreach($obj_gametablelog_multi as $obj_gametablelog_multi_item)
								{
									$obj_gametablelog_multi_item->game_table_info = json_decode($obj_gametablelog_multi_item->game_table_info);

									$obj_gametablelog_multi_item->display = $obj_gametablelog_multi_item->game_table_info->display;
									$obj_gametablelog_multi_item->player_count = $obj_gametablelog_multi_item->game_table_info->player_count;
									$obj_gametablelog_multi_item->set_num = $obj_gametablelog_multi_item->game_table_info->set_num;
									$obj_gametablelog_multi_item->pay_type = $obj_gametablelog_multi_item->game_table_info->pay_type;
									$obj_gametablelog_multi_item->play = $obj_gametablelog_multi_item->game_table_info->play;
									$obj_gametablelog_multi_item->aid = $obj_gametablelog_multi_item->uid;
									$obj_gametablelog_multi_item->is_circle = isset($obj_gametablelog_multi_item->game_table_info->is_circle) ? $obj_gametablelog_multi_item->game_table_info->is_circle : 0;
									
									unset($obj_gametablelog_multi_item->game_table_info);
									unset($obj_gametablelog_multi_item->uid);

									$obj_gametablelog_multi_item->time = date("Y-m-d H:i:s",$obj_gametablelog_multi_item->time);
									
									$tmp[] = $obj_gametablelog_multi_item;
								}
							}
						}
						else
						{
							$obj_gametablelog_multi_factory->clear();
						}
					}
					else
					{
						$obj_gametablelog_list_factory->clear();
					}
				}
				else
				{
					$obj_gametablelog_list_factory->clear();
				}
			}
			else
			{
				$tmp = array();
			}

		}while(false);

		$data['data_count'] = $data_count;
		$data['page_count'] = $page_count;
		$data['list'] = $tmp;
		$response['data'] = $data;
		return $response;
    }

    private function _get_encrypt_uid($uid, $aid)
	{
		$data_request = array(
			'uid' => $uid,
			'aid' => $aid,
			'from_db' => Config::DB_DBNAME
			);
		$randkey = BaseFunction::encryptMD5($data_request);
		return $randkey."$".$uid;
	}

	//更改公会房已读未读状态
	public function change_read_state($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();
		$rawsqls = array();

		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
				|| empty($params['log_id'])
				|| empty($params['state'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();
			
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$obj_gametablelog_factory = new GameTableLogFactory($this->cache_handler, $params['log_id']);
			if($obj_gametablelog_factory->initialize() && $obj_gametablelog_factory->get())
			{
				$obj_gametablelog = $obj_gametablelog_factory->get();
				if (empty($obj_gametablelog->state)) 
				{
					$obj_gametablelog->state = $params['state'];
					$rawsqls[] = $obj_gametablelog->getUpdateSql();
				}
				else
				{
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if(isset($obj_gametablelog_factory))
			{
				$obj_gametablelog_factory->writeback();
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//删除过期数据
	private function deleteExpiredLog($uid_group)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();
		$rawsqls = array();

		do {
			if(empty($uid_group))
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$uid_group); break;
			}

			$this->_init_cache();
			$start_time = strtotime(date('Y-m-d', $itime)) - 86400 * 4;
			$uid_group = '('.$uid_group.')';

			//找到录像ID
			$game_log_id_sql[] = "select game_log.id from game_log join game_log_user on game_log.id = game_log_user.game_log_id where game_log.time <= ".$start_time." and game_log_user.uid in ".$uid_group;

			$select_result = BaseFunction::execute_sql_backend($game_log_id_sql);
			if ($select_result) 
			{
				$start_time = strtotime(date('Y-m-d', $itime)) - 86400 * 2;
				$row = $select_result[0]['result']->fetch_all();
				if (!empty($row)) 
				{
					$rows = [];
					foreach ($row as $key => $value) 
					{
						$rows[] = $value[0];
					}
					
					$game_log_id = '('.implode(',', $rows).')';

					//删除过期战绩
					$rawsqls[] = "delete from `game_table_log` where uid in ".$uid_group." and time <= ".$start_time;
					//删除过期录像
					$rawsqls[] = "delete `game_log`, `game_log_user` from game_log join game_log_user on game_log.id = game_log_user.game_log_id where game_log.time <= ".$start_time." and game_log.id in ".$game_log_id;
					//删除过期user_log
					// $rawsqls[] = "delete from `user_log`where uid in ".$uid_group." and time <= ".$start_time;

					if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
					{
						$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
					}
				}
			}
			
			$response['data'] = $data;
		}while(false);

		return $response;
	}
	
	//微信分享 判断 uid是否存在
	public function judge_uid($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$isset_uid = 1;
		$invite_user_reward = Config::$invite_user_reward;
		$shared_currency = $invite_user_reward['new_user'];

		do {
			if (empty($params['code']) || empty($params['agent_id'])|| empty($params['uid']))
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			if(!defined("bigcat\\conf\\Config::WX_APPID") || !defined("bigcat\\conf\\Config::WX_SECRET"))
			{
				$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
			}
			
			$user_auth = BaseFunction::code_get_wx_user_token(Config::WX_APPID, Config::WX_SECRET, $params['code']);
			if($user_auth && !empty($user_auth['openid']) && !empty($user_auth['access_token']))
			{
				$params['openid'] = $user_auth['openid'];
				$params['access_token'] = $user_auth['access_token'];
			}

			if(empty($params['access_token']) || empty($params['openid']))
			{
				$response['access_token'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$wx_user_info = BaseFunction::code_get_wx_user($params['access_token'], $params['openid']);

			if(!$wx_user_info || empty($wx_user_info['unionid']))
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}
            
			$this->_init_cache();

			//判断agent是否存在
			$agent_info = $this->agent_info($params);
			if (!empty($agent_info['code']) || !empty($params['sub_code'])) 
			{
				$response = $this->_debugLog(2,'notice',__LINE__); break;
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, $wx_user_info['unionid']);
			if(!$obj_user_list_factory->initialize() || !$obj_user_list_factory->get())
			{
				$obj_user_list_factory = new UserListFactory($this->cache_handler, $wx_user_info['openid']);
				if(!$obj_user_list_factory->initialize() || !$obj_user_list_factory->get())
				{
					$isset_uid = 0;
				}
			}

			if (empty($isset_uid)) 
			{
				$wx_user_info['headimgurl'] = str_replace("http://", "https://", $wx_user_info['headimgurl']);
				if (strripos($wx_user_info['headimgurl'],'/')) 
				{
					$wx_user_info['headimgurl'] = substr($wx_user_info['headimgurl'], 0, strripos($wx_user_info['headimgurl'],'/'));
					$wx_user_info['headimgurl'] = $wx_user_info['headimgurl'].'/132';
				}

				$uid = Uid::get_uid();

				$user = new User();
				$user->uid = $uid;
				$user->wx_openid = $wx_user_info['unionid'];
				$user->wx_pic = $wx_user_info['headimgurl'];
				$user->name = $wx_user_info['nickname'];
				$user->sex = $wx_user_info['sex'];
				$user->city = $wx_user_info['city'];
				$user->province = $wx_user_info['province'];
				$user->init_time = $itime;
				$user->update_time = $itime;
				$user->login_time = 0;
				$user->key = 0;
				$rawsqls[] = $user->getInsertSql();
				
				$user_game = new UserGame();
				$user_game->uid = $uid;
				$user_game->currency = Config::FIRST_CURRENCY + $shared_currency;
				$user_game->agent_id = $params['agent_id'] == 'none'? '8611111111111' : $params['agent_id'];
				$user_game->bind_time = $itime;
				$user_game->update_time = $itime;
				$user_game->inviter = $params['uid'];
				$reward_state = [
        			'first_recharge' => 0,
        			'complete_game' => 0
				];
				$user_game->reward_state = json_encode($reward_state);
				$rawsqls[] = $user_game->getInsertSql();
				//新增message
				$this->add_message(['uid'=>$user->uid, 'type'=>21, 'inviter'=> $user_game->inviter]);
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$data['agent_id'] = (string)$params['agent_id'];
			$data['shared_currency'] = $shared_currency;
			$data['isset_uid'] = $isset_uid;

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//消息记录列表
	public function message_list($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array('list'=>[]);
		$data_count = 0;
		$page_count = CatConstant::CNT_PER_PAGE;

		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
				|| empty($params['page'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$page = isset($params['page']) ? intval($params['page']) : 1;

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_message_list_factory = new RewardMessageListFactory($this->cache_handler, $params['uid'], '', 2);
			if($obj_message_list_factory->initialize() && $obj_message_list_factory->get())
			{
				$obj_message_list = $obj_message_list_factory->get();
				$data_count = count($obj_message_list);
				$obj_message_list_page_arr = array_slice($obj_message_list, ($page - 1) * $page_count, $page_count);
				$obj_message_list_factory = new RewardMessageListFactory($this->cache_handler,null,implode(',',$obj_message_list_page_arr));
				if ($obj_message_list_factory->initialize() && $obj_message_list_factory->get()) 
				{
					$obj_message_multi_factory = new RewardMessageMultiFactory($this->cache_handler, $obj_message_list_factory);
					if($obj_message_multi_factory->initialize() && $obj_message_multi_factory->get())
					{
						$obj_message_multi = $obj_message_multi_factory->get();

						foreach ($obj_message_multi as $obj_message_multi_item) 
						{
							$obj_message_multi_item->invitee_name = mb_substr($obj_message_multi_item->invitee_name, 0, 4).'...';
							if ($obj_message_multi_item->type == 1) 
							{
								$obj_message_multi_item->message = "您邀请的好友".$obj_message_multi_item->invitee_name."绑定了公会,您获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 2) 
							{
								$obj_message_multi_item->message = "您邀请的好友".$obj_message_multi_item->invitee_name."进行了第一次充值,您获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 3) 
							{
								$obj_message_multi_item->message = "您邀请的好友".$obj_message_multi_item->invitee_name."已经进行了10桌游戏,您获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 11) 
							{
								$obj_message_multi_item->message = "由于您绑定了公会,您的好友".$obj_message_multi_item->invitee_name."获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 12) 
							{
								$obj_message_multi_item->message = "由于您进行了第一次充值,您的好友".$obj_message_multi_item->invitee_name."获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 13) 
							{
								$obj_message_multi_item->message = "由于您已经进行了10桌游戏,您的好友".$obj_message_multi_item->invitee_name."获得了".$obj_message_multi_item->currency."颗钻石的奖励";
							}

							if ($obj_message_multi_item->type == 21) 
							{
								switch (Config::DB_DBNAME) 
								{
									case 'game_mahjong_cangzhou':
										$address = '沧州本地棋牌';
										break;

									case 'game_mahjong_baoding_newnew':
										$address = '保定本地棋牌';
										break;

									default:
										$address = '灵飞棋牌';
										break;
								}
								$obj_message_multi_item->message = "欢迎来到".$address."，为了感谢您对游戏的支持，免费送您".$obj_message_multi_item->currency."颗钻石";
							}

							unset($obj_message_multi_item->uid);
							unset($obj_message_multi_item->type);
							unset($obj_message_multi_item->invitee);
							unset($obj_message_multi_item->currency);
							unset($obj_message_multi_item->invitee_name);
							unset($obj_message_multi_item->update_time);
							$data['list'][] = $obj_message_multi_item;
						}
					}
					else
					{
						$obj_message_multi_factory->clear();
						$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
					}
				}
				else
				{
					$obj_message_list_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}
			
			$data['all_count'] = $data_count;
			$data['page_count'] =$page_count;
			$data['time'] = $itime;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//更改消息状态
	public function update_message_state($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$data_count = 0;
		$page_count = CatConstant::CNT_PER_PAGE;
		$have_reward = 0;
		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
				|| empty($params['state'])
				|| empty($params['mid'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}
			$page = isset($params['page']) ? intval($params['page']) : 1;

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$this->_init_cache();
		
			$obj_message_factory = new RewardMessageFactory($this->cache_handler, $params['mid']);
			if($obj_message_factory->initialize() && $obj_message_factory->get())
			{
				$obj_message = $obj_message_factory->get();
				if ($params['state'] == 1) 
				{
					if ($obj_message->state == 0)
					{
						$obj_message->state = 2;
						$rawsqls[] = $obj_message->getUpdateSql();
						$have_reward = 1;
					}
					else
					{
						$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
					}
				}
				elseif ($params['state'] == 2) 
				{
					if ($obj_message->state == 1)
					{
						$obj_message->state = 2;
						$rawsqls[] = $obj_message->getUpdateSql();
					}
					else
					{
						$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;

					}
				}
				else
				{
					$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
				}
			}
			else
			{
				$obj_message_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if ($have_reward == 1) 
			{
				$type = 50 + $obj_message->type;
				$this->checkout_open_room(['uid'=>$params['uid'], 'type'=>$type, 'currency'=>$obj_message->currency]);
			}

			if (isset($obj_message_factory)) 
			{
				$obj_message_factory->writeback();
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//添加消息
	private function add_message($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		$invite_user_reward = isset(Config::$invite_user_reward) ? Config::$invite_user_reward : ['new_user' => 3,'first_recharge' => 6,'complete_game' =>8];

		do {
			if(
				empty($params['uid'])
				|| empty($params['type'])
				|| !isset($params['inviter'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();

			//新用户奖励8颗钻石
			if ($params['type'] == 21) 
			{
				//针对邀请人产生一条有奖励的消息
				$obj_reward_message = new RewardMessage();
				$obj_reward_message->uid = $params['uid'];
				$obj_reward_message->type = $params['type'];
				$obj_reward_message->state = 1;
				$obj_reward_message->invitee = $params['inviter'];
				$obj_reward_message->create_time = $itime;
				$obj_reward_message->update_time = $itime;
				$obj_reward_message->currency = Config::FIRST_CURRENCY;
				if (!empty($params['inviter'])) 
				{
					$obj_user_factory = new UserFactory($this->cache_handler, $params['inviter']);
					if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
					{
						$obj_user_factory->clear();
						$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$obj_user_factory); break;
					}
					$obj_user = $obj_user_factory->get();
					$obj_reward_message->invitee_name = $obj_user->name;
				}
				
				$rawsqls[] = $obj_reward_message->getInsertSql();
			}
			else
			{
				//针对邀请人产生一条有奖励的消息
				$obj_reward_message = new RewardMessage();
				$obj_reward_message->uid = $params['inviter'];
				$obj_reward_message->type = $params['type'];
				$obj_reward_message->state = 0;
				$obj_reward_message->invitee = $params['uid'];
				$obj_reward_message->create_time = $itime;
				$obj_reward_message->update_time = $itime;

				$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
				if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
				{
					$obj_user_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$obj_user_factory); break;
				}
				$obj_user = $obj_user_factory->get();
				$obj_reward_message->invitee_name = $obj_user->name;

				switch ($params['type']) 
				{
					case '1':
						$obj_reward_message->currency = $invite_user_reward['new_user'];
						break;
					case '2':
						$obj_reward_message->currency = $invite_user_reward['first_recharge'];
						break;
					case '3':
						$obj_reward_message->currency = $invite_user_reward['complete_game'];
						break;
					default:
						$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params);break 2;
				}

				$rawsqls[] = $obj_reward_message->getInsertSql();
				
				//针对被邀请者产生一条消息
				$obj_reward_message->uid = $params['uid'];
				$obj_reward_message->type = 10 + $params['type'];
				$obj_reward_message->state = 1;
				$obj_reward_message->invitee = $params['inviter'];

				$obj_user_factory = new UserFactory($this->cache_handler, $params['inviter']);
				if(!$obj_user_factory->initialize() || !$obj_user_factory->get())
				{
					$obj_user_factory->clear();
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$obj_user_factory); break;
				}
				$obj_user = $obj_user_factory->get();
				$obj_reward_message->invitee_name = $obj_user->name;

				$rawsqls[] = $obj_reward_message->getInsertSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//邀请玩家界面数据
	public function getTotalInvitee($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array('inviter_name'=>'','total_inviter'=> 0, 'total_reward'=>0);
		$invite_user_reward = Config::$invite_user_reward;
		$total_inviter = array();

		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(CatConstant::ERROR,'notice',__LINE__); break;
			}

			$this->_init_cache();
			$obj_reward_message_list_factory = new RewardMessageListFactory($this->cache_handler, $params['uid']);
			if ($obj_reward_message_list_factory->initialize() && $obj_reward_message_list_factory->get()) 
			{
				$obj_reward_message_multi_factory = new RewardMessageMultiFactory($this->cache_handler, $obj_reward_message_list_factory);
				if ($obj_reward_message_multi_factory->initialize() && $obj_reward_message_multi_factory->get()) 
				{
					$obj_reward_message_multi = $obj_reward_message_multi_factory->get();
					foreach ($obj_reward_message_multi as $obj_reward_message_multi_item) 
					{
						if (!in_array($obj_reward_message_multi_item->invitee, $total_inviter) && !empty($obj_reward_message_multi_item->invitee) && $obj_reward_message_multi_item->type < 10) 
						{
							$total_inviter[] = $obj_reward_message_multi_item->invitee;
						}
						$data['total_inviter'] = count($total_inviter);

						if ($obj_reward_message_multi_item->state != 0 && $obj_reward_message_multi_item->type < 10) 
						{
							$data['total_reward'] += $obj_reward_message_multi_item->currency;
						}
					}
				}
				else
				{
					$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
				}
			}

			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();
				if (!empty($obj_user_game->inviter)) 
				{
					$obj_inviter_factory = new UserFactory($this->cache_handler, $obj_user_game->inviter);
					if($obj_inviter_factory->initialize() && $obj_inviter_factory->get())
					{
						$obj_inviter = $obj_inviter_factory->get();
						$data['inviter_name'] = $obj_inviter->name;
					}
					else
					{
						$obj_inviter_factory->clear();
						$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
					}
				}
			}
			else
			{
				$obj_user_game_factory->clear();
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//获取总消息数量
	public function getAddedMessageAmount($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();

		do {
			if(
				empty($params['uid'])
				|| empty($params['key'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//验证用户是否登录
			$result = $this->login_check($params);
			if ($result['code'] != 0 || $result['sub_code'] != 0)
			{
				$response = $this->_debugLog(1,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_message_list_factory = new RewardMessageListFactory($this->cache_handler, $params['uid'], '', 2);
			if($obj_message_list_factory->initialize() && $obj_message_list_factory->get())
			{
				$obj_message_list = $obj_message_list_factory->get();
				$data_count = count($obj_message_list);
			}

			$data['amount'] = empty($data_count) ? 0 : $data_count;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//微信分享获取头像
	public function getWechatHead($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$invite_user_reward = Config::$invite_user_reward;

		do {
			if (empty($params['uid'])) 
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if($obj_user_factory->initialize() && $obj_user_factory->get())
			{
				$obj_user = $obj_user_factory->get();
				$obj_user->day = ceil(($itime - $obj_user->init_time)/86400);
				unset($obj_user->uid);
				unset($obj_user->key);
				unset($obj_user->wx_openid);
				unset($obj_user->sex);
				unset($obj_user->province);
				unset($obj_user->city);
				unset($obj_user->init_time);
				unset($obj_user->update_time);
				unset($obj_user->login_time);
				unset($obj_user->real_name_reg);
				unset($obj_user->status);
				$data['list'][] = $obj_user;
			}

			$data['currency'] = $invite_user_reward['new_user'];
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//微信公众号关注送钻石
	public function wechatSubscibe($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array('currency' => 0);

		do {
			if (empty($params['openid']) || empty($params['uid'])) 
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			//获取基础access_toekn
			$access_token = BaseFunction::get_wx_access_token(Config::WX_APPID_PUBLIC, Config::WX_SECRET_PUBLIC);			
			if(!$access_token || empty($access_token['access_token']))
			{
				$response['access_token'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			else
			{
				$params['access_token'] = $access_token['access_token'];
			}

			//获取订阅信息
			$wx_user_info = BaseFunction::get_wx_user_info($params['access_token'], $params['openid']);
			if(!$wx_user_info || empty($wx_user_info['unionid']))
			{
				$response = $this->_debugLog(2,'notice',__LINE__); break;
			}

			if (empty($wx_user_info['subscribe'])) 
			{
				$response = $this->_debugLog(2,'notice',__LINE__); break;
			}

			$this->_init_cache();

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if($obj_user_factory->initialize() && $obj_user_factory->get())
			{	
				$obj_user = $obj_user_factory->get();
				if ($obj_user->uid != $params['uid']) 
				{
					$response = $this->_debugLog(3,'notice',__LINE__); break;
				}
				
				$obj_user_active_factory = new UserActiveFactory($this->cache_handler, $params['uid']);
				if($obj_user_active_factory->initialize() && $obj_user_active_factory->get())
				{
					$obj_user_active = $obj_user_active_factory->get();
					if (empty($obj_user_active->subscribe_reward)) 
					{
						$currency = defined("bigcat\\conf\\Config::SUBSCRIBE_CURRENCY") ? Config::SUBSCRIBE_CURRENCY : 1;
						$data['currency'] = $currency;
						$obj_user_active->subscribe_reward = 1;
						$obj_user_active->update_time = $itime;
						$rawsqls[] = $obj_user_active->getUpdateSql();
					}
					else
					{
						$obj_user_active_factory->clear();
						$response = $this->_debugLog(5,'notice',__LINE__); break;
					}
				}
				else
				{
					$obj_user_active = new UserActive();
					$obj_user_active->uid = $params['uid'];
					$obj_user_active->subscribe_reward = 1;
					$obj_user_active->create_time = $itime;
					$obj_user_active->update_time = $itime;
					$rawsqls[] = $obj_user_active->getInsertSql();
					$currency = defined("bigcat\\conf\\Config::SUBSCRIBE_CURRENCY") ? Config::SUBSCRIBE_CURRENCY : 1;
					$data['currency'] = $currency;
				}
			}
			else
			{
				$obj_user_factory->clear();
				$response = $this->_debugLog(3,'notice',__LINE__); break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}

			if (!empty($data['currency'])) 
			{
				$this->checkout_open_room(['uid'=>$obj_user_active->uid, 'currency'=>$currency, 'type'=> 61]);
			}

			if(isset($obj_user_active_factory))
			{
				$obj_user_active_factory->clear();
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function getWechatId($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$sum = 0;

		do {

			$this->_init_cache();

			if (empty($params['code'])) 
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			if(!defined("bigcat\\conf\\Config::WX_APPID_PUBLIC") || !defined("bigcat\\conf\\Config::WX_SECRET_PUBLIC"))
			{
				$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
			}
			
			$user_auth = BaseFunction::code_get_wx_user_token(Config::WX_APPID_PUBLIC, Config::WX_SECRET_PUBLIC, $params['code']);
			if(!$user_auth || empty($user_auth['openid']) || empty($user_auth['access_token']))
			{
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'error',__LINE__,$params); break;
			}
			else
			{
				$data['openid'] = $user_auth['openid'];
				$data['unionid'] = isset($user_auth['unionid']) ? $user_auth['unionid'] : '';
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//微信公众号分享送钻石
	public function wechatPublicShare($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$sum = 0;

		do {

			$this->_init_cache();

			if (empty($params['openid']) || empty($params['unionid'])) 
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, $params['unionid']);
			if(!$obj_user_list_factory->initialize() || !$obj_user_list_factory->get())
			{
				$obj_user_list_factory = new UserListFactory($this->cache_handler, $params['openid']);
			}
			
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$obj_user_list = $obj_user_list_factory->get();
				$obj_user_factory = new UserFactory($this->cache_handler, current($obj_user_list));
				if($obj_user_factory->initialize() && $obj_user_factory->get())
				{
					$user = $obj_user_factory->get();
					$result = $this->share_currency(['uid'=>$user->uid]);
					if (!empty($result['code']) || !empty($result['sub_code'])) 
					{
						$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'notice',__LINE__); break;
					}
				}
			}
			else
			{
				$response = $this->_debugLog(CatConstant::ERROR_SEARCH,'notice',__LINE__); break;
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//卡券
	public function get_jsapi_ticket($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {

			$this->_init_cache();

			if (!empty($params['wx_name']) && $params['wx_name'] = 'lingfeiqipai') 
			{
				if(!defined("bigcat\\conf\\Config::WX_APPID_PUBLIC") || !defined("bigcat\\conf\\Config::WX_SECRET_PUBLIC"))
				{
					$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
				}
				$appid = Config::WX_APPID_PUBLIC;
				$appsecret = Config::WX_SECRET_PUBLIC;
			}
			else
			{
				if(!defined("bigcat\\conf\\Config::WX_APPID") || !defined("bigcat\\conf\\Config::WX_SECRET"))
				{
					$response = $this->_debugLog(CatConstant::ERROR_CONFIG,'error',__LINE__,$params); break;
				}
				$appid = Config::WX_APPID;
				$appsecret = Config::WX_SECRET;
			}

			//获取access_token
			$obj_wxtoken_factory = new WXTokenFactory($this->cache_handler,$appid,$appsecret);
			if($obj_wxtoken_factory->initialize() && $obj_wxtoken_factory->get())
			{
				$obj_wxtoken = $obj_wxtoken_factory->get();
				//取得ticket
				$obj_wxticket_factory = new WXTicketFactory($this->cache_handler,$appid,$obj_wxtoken->access_token);
				if($obj_wxticket_factory->initialize() && $obj_wxticket_factory->get())
				{
					$obj_wxticket_tmp = $obj_wxticket_factory->get();
				}
				else
				{
					$obj_wxticket_factory->clear();
					$response = $this->_debugLog(2,'notice',__LINE__); break;
				}

				//构造一个sign
				$obj_wxticket = new WXTicket($this->cache_handler);
				$result = $obj_wxticket->get_sign($obj_wxticket_tmp->js_ticket, $appid);
				if(!$result)
				{
					$response = $this->_debugLog(3,'notice',__LINE__); break;
				}

				$data['sign_obj'] = $result;
			}
			else
			{
				$obj_wxtoken_factory->clear();
				$response = $this->_debugLog(2,'notice',__LINE__); break;
			}	

			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//做牌
	// public function make_card($params)
    // {
    //     $response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);

    //     do {

    //         if( empty($params['rid'])
    //         ||empty($params['all_card'])
    //         ||empty($params['c_version'])
    //         )
    //         {
	// 			$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
    //         }
    //         //确定tcp服务器地址
    //         $tcp_s = $this->_get_tcp_s($params['rid'], $params['c_version'], 'gfplay');
    //         $client = $this->_bind_rid($tcp_s[1], $params['rid']);
    //         if(!$client)
    //         {
	// 			$response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
    //         }
    //         //$response['tcp_s'] = $tcp_s;
    //         //去tcp确认本rid状态

    //         $client->send(self::tcp_encode(json_encode(array('act'=>'c_make_card', 'rid'=>$params['rid'],'all_card'=>$params['all_card']))));
    //         $re_str = self::tcp_decode($client->recv());
    //         $re = json_decode($re_str,true);

    //         if($re['act'] == 's_result' && $re['info'] == 'c_make_card' && $re['code'] == 0)
    //         {

    //         }
    //         else
    //         {
	// 			$response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$params); break;
    //         }
            
    //     } while (false);

    //     return $response;
    // }

    // //做牌
    // public function add_robot($params)
    // {
    //     $response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);

    //     do {

    //         if( empty($params['rid'])
    //             ||empty($params['c_version'])
    //             || empty($params['uid'])
    //             || !isset($params['is_room_owner'])
    //             || empty($params['game_type'])
    //             || empty($params['ip'])
    //             || !isset($params['uname'])
    //             || !isset($params['head_pic'])
    //         )
    //         {
    //             $response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
    //         }
    //         //确定tcp服务器地址
    //         $tcp_s = $this->_get_tcp_s($params['rid'], $params['c_version'], 'gfplay');
    //         $client = $this->_bind_rid($tcp_s[1], $params['rid']);
    //         if(!$client)
    //         {
    //             $response = $this->_debugLog(CatConstant::ERROR_NETWORK,'error',__LINE__,$params); break;
    //         }
    //         //$response['tcp_s'] = $tcp_s;
    //         //去tcp确认本rid状态

    //         $client->send(self::tcp_encode(json_encode(array('act'=>'c_join_room',
    //             'rid'=>$params['rid'],
    //             'uid'=>$params['uid'],
    //             'is_room_owner'=>$params['is_room_owner'],
    //             'game_type'=>$params['game_type'],
    //             'ip'=>$params['ip'],
    //             'uname'=>$params['uname'],
    //             'head_pic'=>$params['head_pic'],
    //             'robot'=>1
    //         ))));
    //         $re_str = self::tcp_decode($client->recv());
    //         $re = json_decode($re_str,true);

    //         if($re['act'] == 's_result' && $re['info'] == 'c_join_room' && $re['code'] == 0)
    //         {

    //         }
    //         else
    //         {
    //             $response = $this->_debugLog(CatConstant::ERROR,'error',__LINE__,$params); break;
    //         }

    //     } while (false);

    //     return $response;
    // }

    //错误记录
    private function _debugLog($error,$type,$line,$params = null)
    {
    	$response = array('code' => 0, 'desc' => $line, 'sub_code' => 0);

    	if ($type == 'error') 
    	{
    		$response['code'] = $error;
    		if (!empty(CatConstant::CODE_DESC['code_'.$error])) 
    		{
    			$response['code_desc'] = CatConstant::CODE_DESC['code_'.$error];
    		}
			BaseFunction::logger($this->log, "【error】:\n".var_export($error, true)."\n".$line."\n");
			BaseFunction::logger($this->log, "【error_params】:\n".var_export($params, true)."\n".$line."\n");
    	}
    	elseif ($type == 'notice') 
    	{
    		$response['sub_code'] = $error;
    	}

    	return $response;
    }

    public function _deleteExpiredLog($uid_group)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();
		$rawsqls = array();

		do {

			$this->_init_cache();
			$start_time = strtotime(date('Y-m-d', $itime)) - 86400 * 2;

			//删除过期战绩
			$rawsqls[] = "delete from `game_table_log` where time <= ".$start_time;
			//删除过期录像
			$rawsqls[] = "delete `game_log`, `game_log_user` from game_log join game_log_user on game_log.id = game_log_user.game_log_id where game_log.time <= ".$start_time;

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls); break;
			}
			
			$response['data'] = $data;
		}while(false);

		return $response;
	}

	public function mysqlTruncate()
	{
		$rawsqls[] = 'truncate table game_log';
		$rawsqls[] = 'truncate table game_log_user';
		$rawsqls[] = 'truncate table game_table_log';
		if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
		{
			$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls);
		}
	}

	public function buy_vip($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		do{

			if( empty($params['uid'])
			|| empty($params['vip_type'])
			|| empty($params['money'])
			)
			{
				$response = $this->_debugLog(CatConstant::ERROR_VERIFY,'error',__LINE__,$params); break;
			}

			$this->_init_cache();

			$obj_user_game_factory = new UserGameFactory($this->cache_handler, $params['uid']);
			if($obj_user_game_factory->initialize() && $obj_user_game_factory->get())
			{
				$obj_user_game = $obj_user_game_factory->get();
				$obj_user_game->vip_type = $params['vip_type'];
				$overtime = $obj_user_game->vip_overtime;
				$obj_user_game->vip_overtime = $this->_vip_change_time($overtime,$params['vip_type']);
				$rawsqls[] = $obj_user_game->getUpdateSql();

			}

			$obj_user_log = new UserLog();
			$obj_user_log->uid = $params['uid'];
			$obj_user_log->type = $params['vip_type'];
			$obj_user_log->time = time();	
			$obj_user_log->money = $params['money'];
			$rawsqls[] = $obj_user_log->getInsertSql();
			
	
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				$response = $this->_debugLog(CatConstant::ERROR_UPDATE,'error',__LINE__,$rawsqls);
			}

			if(isset($obj_user_game_factory))
			{
				$obj_user_game_factory->clear();
			}

		}while(false);
		return $response;
	}

	private function _vip_change_time($overtime,$vip_type)
	{
		$vip_overtime = 0;
		$itime = time();
		if($overtime <= $itime)
		{
			$overtime = 0;
		}
		switch ($vip_type)
		{
		case 101:
			$vip_overtime = $overtime + 86400;
			break;
		case 102:
			$vip_overtime = $overtime + 86400*7;
			break;
		case 103:
			$vip_overtime = $overtime + 86400*30;
			break;
		case 104:
			$vip_overtime = $overtime + 86400*90;
			break;
		case 105:
			$vip_overtime = $overtime + 86400*180;
			break;
		case 106:
			$vip_overtime = $overtime + 86400*365;
			break;
		default:
			$vip_overtime = 0;
		}

		return $vip_overtime;
		


	}
}
