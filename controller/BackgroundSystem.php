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

use bigcat\model\GiftExchangeLog;
use bigcat\model\GiftExchangeLogFactory;
use bigcat\model\GiftExchangeLogListFactory;
use bigcat\model\GiftExchangeLogMultiFactory;

class BackgroundSystem
{
	public static $user_game_key = 'user_game_lock_key_';

	private $log = CatConstant::LOG_FILE;
	private $login_timeout = 604800;	//3600 * 24 * 7
	//private $login_timeout = 60480000;	//3600 * 24 * 7	 * 100
	public $cache_handler = null;

	private $tcp_arr = Config::TCP_ARR;
	private $tcp_arr_ios = Config::TCP_ARR_IOS;
	private $tcp_arr_pre = Config::TCP_ARR_PRE;

	public function __construct()
	{
		;
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
	
	//组装kpi表
    public function kpi_crontab()
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $rawsqls = array();
        $itime = time();
        $data = array();

        do {
            $this->_init_cache();

            //前一天零点
            $id_time = strtotime(date('Y-m-d', $itime));
            $id_time_now = $id_time + 86400;
            $id_time_hour = strtotime(date('Y-m-d H:0:0', $itime));
            $id_time_hour_now = $id_time_hour + 3600;
            $hour_name = date('H', $id_time_hour);

            $obj_kpi_multi_factory = new KpiMultiFactory($this->cache_handler, null, $id_time);
            $obj_kpi_multi_factory->clear();	//清理缓存，只从数据库取
            if($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
            {
                $obj_kpi_multi = $obj_kpi_multi_factory->get();
                $obj_kpi = current($obj_kpi_multi);
            }
            else
            {
                $obj_kpi = new Kpi();
            }

            $kpi_sql = " select (select count(*)  from `user`)
						 , (select count(*) from `user` where  init_time>$id_time  and init_time<$id_time_now)
						 , (select count(*) from `user_game` where  last_game_time>$id_time  and last_game_time<$id_time_now)
						 , (select count(*) from `game_log` where  time>$id_time  and time<$id_time_now)
						 , (select count(*) from `user_game` where  last_game_time>$id_time_hour and last_game_time<$id_time_hour_now)
						 , (select sum(currency) from `user_log` where time>$id_time and time<$id_time_now and type=1)
						 , (select sum(play_time) from `game_log` where  time>$id_time  and time<$id_time_now)
						  ";
            $kpi_records = BaseFunction::query_sql_backend($kpi_sql);
            if(!$kpi_records)
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }
            while ( ($row = $kpi_records->fetch_row()) != false )
            {
                $kpi_arr = $row;
            }

            $obj_kpi->id_time = $id_time;
            $obj_kpi->all_user = $kpi_arr[0];
            $obj_kpi->new_user = $kpi_arr[1];
            $obj_kpi->active_user = $kpi_arr[2];
            $obj_kpi->game_num = $kpi_arr[3];

            $tmp_hour_arr = json_decode($obj_kpi->hour_user, true);

            if(!is_array($tmp_hour_arr))
            {
                $tmp_hour_arr = array();
            }
            $tmp_hour_arr[$hour_name] = $kpi_arr[4];
            $obj_kpi->hour_user = json_encode($tmp_hour_arr);
            $obj_kpi->currency = $kpi_arr[5];
            $obj_kpi->play_time = intval($kpi_arr[6]/$obj_kpi->game_num);

            if(!empty($obj_kpi_multi))
            {
                $rawsqls[] = $obj_kpi->getUpdateSql();
            }
            else
            {
                $rawsqls[] = $obj_kpi->getInsertSql();
            }

            if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
            {
                BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
                $response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
            }

            $obj_kpi_multi_factory->writeback();

            $response['data'] = $data;

        }while(false);

        return $response;
    }

	public function kpi_get($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			//清理log
			$this->del_log();

			if(!isset($params['page']))
			{
				$params['page'] = 1;
			}

			$this->_init_cache();
			$data['all_count'] = 0;
			$data['kpi_list'] = array();
			$count_per_page = 20;
			$data['count_per_page'] = $count_per_page;

			$obj_kpi_list_factory = new KpiListFactory($this->cache_handler, 1);
			if($obj_kpi_list_factory->initialize() && $obj_kpi_list_factory->get())
			{
				$obj_kpi_list = $obj_kpi_list_factory->get();
				$data['all_count'] = count($obj_kpi_list);
				$id_page_arr = array_slice($obj_kpi_list, ($params['page'] - 1) * $count_per_page, $count_per_page);
				if ($id_page_arr && is_array($id_page_arr)) {
					$obj_page_kpi_list_factory = new KpiListFactory($this->cache_handler, null, implode(',', $id_page_arr));
					if ($obj_page_kpi_list_factory->initialize() && $obj_page_kpi_list_factory->get())
					{
						$obj_kpi_multi_factory = new KpiMultiFactory($this->cache_handler, $obj_page_kpi_list_factory);
						if ($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
						{
							$obj_kpi_multi = $obj_kpi_multi_factory->get();
							krsort($obj_kpi_multi);
							$obj_kpi_multi_tmp = array();

							foreach ($obj_kpi_multi as $value)
							{
								$value->hour_user = json_decode($value->hour_user, true);
								$value->id_time = date('Y-m-d', $value->id_time);
								$obj_kpi_multi_tmp[] = $value;
							}
							$data['kpi_list'] = $obj_kpi_multi_tmp;
						}
					}
				}
			}
			$response['data'] = $data;

			//整理房间状态
			$this->cron_room();

		}while(false);

		return $response;
	}

    private function cron_room()
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			$obj_room_list_factory = new RoomListFactory($this->cache_handler, 2);
			if($obj_room_list_factory->initialize() && $obj_room_list_factory->get())
			{
				$obj_room_list = $obj_room_list_factory->get();
				$num = 0;
				foreach ($obj_room_list as $obj_room_id)
				{
					//限次
					$num ++;
					if($num >= 10)
					{
						break;
					}

					$tcp_s = $this->_get_tcp_s($obj_room_id, Config::C_VERSION, Config::PLATFORM);
					//去查看tcp服务器房间状态
					$client = $this->_bind_rid($tcp_s[1], $obj_room_id);
					if(!$client)
					{
						continue;
					}
					$client->send(self::tcp_encode(json_encode(array('act'=>'c_get_room', 'rid'=>$obj_room_id, 'uid'=>0))));
					$result =self::tcp_decode( $client->recv());
					$result = json_decode($result, true);
					$client->close();
					if(!empty($result['info']) && $result['info'] == 'c_get_room' && ($result['code'] == 1 || $result['code'] == 2))
					{
						$this->_set_room($obj_room_id, 1);
					}
				}
			}
			else
			{
				$obj_room_list_factory->clear();
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	private function del_log()
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			//每天只能过一次del
			$this->_init_cache();
			$log_del_time_key = 'log_del_time_key_'.Config::DB_DBNAME;
			if(!($this->cache_handler->setKeep($log_del_time_key, 1, 86400)))
			{
				$response['code'] = CatConstant::ERROR; break;
			}

			$id_time_del = $itime - 864000;
			$rawsqls[] = "delete game_log.*, game_log_user.* from game_log, game_log_user where game_log.id=game_log_user.game_log_id and game_log.time<$id_time_del and game_log.save='' ";

			BaseFunction::execute_sql_backend($rawsqls);

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//解除绑定推广员
	public function bind_out_agent_id($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( empty($params['uid'])
			|| empty($params['agent_id'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			$params['uid'] = intval($params['uid']);

			$this->_init_cache();

			//解除绑定
			$obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler,'',$params['uid']);
			if($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get())
			{
				$obj_user_game_multi = $obj_user_game_multi_factory->get();
				$obj_user_game_multi_item = current($obj_user_game_multi);

				if(!empty($obj_user_game_multi_item->status))
				{
					$response['sub_code'] = 1; $response['desc'] = __line__; break;
				}
				if(empty($obj_user_game_multi_item->agent_id ))
				{
					$response['sub_code'] = 2; $response['desc'] = __line__; break;
				}
				if($obj_user_game_multi_item->agent_id != $params['agent_id'])
				{
					$response['sub_code'] = 3; $response['desc'] = __line__; break;
				}

				$obj_user_game_multi_item->agent_id = 0;
				$obj_user_game_multi_item->bind_time = $itime;

				$rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
			}

			$obj_user_game_multi_factory->writeback();

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//玩家列表
	public function play_list($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$tmp_name =array();
		$data_count = 0;
		$page_count = CatConstant::CNT_PER_PAGE;

		do {
			if(
				!isset($params['agent_id'])
				|| !isset($params['page'])
				|| !isset($params['all'])
				|| !isset($params['play_id'])
				|| !isset($params['start_time'])
				|| !isset($params['end_time'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			$page = isset($params['page']) ? intval($params['page']) : 1;

			$this->_init_cache();

			//玩家usergame
			if(!empty($params['play_id']))
			{
				$obj_user_multi_factory = new UserMultiFactory($this->cache_handler,null, $params['play_id']);
				if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
				{
					$obj_user_multi = $obj_user_multi_factory->get();
					$obj_user_multi_item = current($obj_user_multi);
					$tmp_name[$obj_user_multi_item->uid] = $obj_user_multi_item;
				}

				$obj_usergame_multi_factory =  new UserGameMultiFactory($this->cache_handler,null,$params['play_id']);
				if($obj_usergame_multi_factory->initialize() && $obj_usergame_multi_factory->get())
				{
					$obj_usergame_multi = $obj_usergame_multi_factory->get();
              		$obj_usergame_multi_item = current($obj_usergame_multi);
              		$data_count = 1;

					if(isset($tmp_name[$obj_usergame_multi_item->uid]))
					{
						$obj_usergame_multi_item->name = $tmp_name[$obj_usergame_multi_item->uid]->name;
						$obj_usergame_multi_item->wx_pic = $tmp_name[$obj_usergame_multi_item->uid]->wx_pic;
					}
					$obj_usergame_multi_item->update_time = date("Y-m-d H:i:s",$obj_usergame_multi_item->update_time);
					if(!empty($obj_usergame_multi_item->bind_time))
					{
						$obj_usergame_multi_item->bind_time = date("Y-m-d H:i:s",$obj_usergame_multi_item->bind_time);
					}
					$data['list'][] = $obj_usergame_multi_item;

				}
			}
			else
			{
				if(!empty($params['all']) && $params['all'])
				{
					$sql = "select `uid` from `user_game`";
					$key = "ces";
					if (!empty($params['start_time']) || !empty($params['end_time'])) 
					{
						if(!empty($params['start_time']))
						{
							$sql =$sql." where update_time >=".$params['start_time'];
							$key.= $params['start_time'];

							if(!empty($params['end_time']))
							{
								$sql = $sql." and update_time <=".$params['end_time'];
								$key.= $params['end_time'];
							}
						}
						else
						{
							if(!empty($params['end_time']))
							{
								$sql = $sql." where update_time <=".$params['end_time'];
								$key.= $params['end_time'];
							}
						}
					}
					
					$sql .= " order by update_time desc";
					$obj_usergame_list_factory = new UserGameListFactory($this->cache_handler,null ,null, $sql,$key);

				}
				else
				{
					$sql = "select `uid` from `user_game` where agent_id =". intval($params['agent_id'])."";
					$key = "cesadf";
					if(!empty($params['start_time']))
					{
						$sql =$sql." and update_time >=".$params['start_time'];
						$key.= $params['start_time'];
					}
					if(!empty($params['end_time']))
					{
						$sql = $sql." and update_time <=".$params['end_time'];
						$key.= $params['end_time'];
					}
					$sql .= " order by update_time desc";

					$obj_usergame_list_factory = new UserGameListFactory($this->cache_handler, null, null, $sql, $key);
				}

				if($obj_usergame_list_factory->initialize() && $obj_usergame_list_factory->get())
				{
					$obj_usergame_list = $obj_usergame_list_factory->get();//玩家uid列表

					//读取玩家名字
					if(!empty($params['all']) && $params['all'])
					{
						$obj_user_list_factory = new UserListFactory($this->cache_handler);
					}
					else
					{
						$obj_user_list_factory = new UserListFactory($this->cache_handler, null,implode(',',$obj_usergame_list));
					}
					if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
					{
						$obj_user_multi_factory = new UserMultiFactory($this->cache_handler,$obj_user_list_factory);
						if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
						{
							$obj_user_multi = $obj_user_multi_factory->get();
							if(is_array($obj_user_multi))
							{
								foreach ($obj_user_multi as $obj_user_multi_item)
								{
									$tmp_name[$obj_user_multi_item->uid] = $obj_user_multi_item;
								}
							}
						}
					}
					else
					{
						$obj_user_list_factory->clear();
						$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
					}

					$data_count = count($obj_usergame_list);
					$obj_usergame_list_page_arr = array_slice($obj_usergame_list, ($page - 1) * $page_count, $page_count);

					$obj_usergame_list_factory = new UserGameListFactory($this->cache_handler,null, implode(',',$obj_usergame_list_page_arr));
					if($obj_usergame_list_factory->initialize() && $obj_usergame_list_factory->get())
					{
						$obj_usergame_multi_factory =  new UserGameMultiFactory($this->cache_handler,$obj_usergame_list_factory);
						if($obj_usergame_multi_factory->initialize() && $obj_usergame_multi_factory->get())
						{
							$obj_usergame_multi = array_values($obj_usergame_multi_factory->get());
		                	usort($obj_usergame_multi,array('bigcat\controller\Business','cmp_list_update_time'));
							if(is_array($obj_usergame_multi))
							{
								foreach ($obj_usergame_multi as $obj_usergame_multi_item)
								{
									if(isset($tmp_name[$obj_usergame_multi_item->uid]))
									{
										$obj_usergame_multi_item->name = $tmp_name[$obj_usergame_multi_item->uid]->name;
										$obj_usergame_multi_item->wx_pic = $tmp_name[$obj_usergame_multi_item->uid]->wx_pic;
									}
									$obj_usergame_multi_item->update_time = date("Y-m-d H:i:s",$obj_usergame_multi_item->update_time);
									if(!empty($obj_usergame_multi_item->bind_time))
									{
										$obj_usergame_multi_item->bind_time = date("Y-m-d H:i:s",$obj_usergame_multi_item->bind_time);
									}
									$data['list'][] = $obj_usergame_multi_item;
								}
							}
						}
					}
				}
				else
				{
					$obj_usergame_list_factory->clear();
				}
			}

			$data['data_count'] = $data_count;
			$data['page_count'] = $page_count;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

    //将玩家拉入黑名单
    public function pull_blacklist($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $rawsqls = array();
        $itime = time();
        $data = array();

        do {
            if(empty($params['uid']) || !isset($params['status']))
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

            $this->_init_cache();

            $obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler,'',$params['uid']);
			if($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get())
            {
                $obj_user_game_multi = $obj_user_game_multi_factory->get();
                $obj_user_game_multi_item = current($obj_user_game_multi);

                if (in_array($params['status'], [0, 1]) && $obj_user_game_multi_item->status != $params['status'])
                {
                    $obj_user_game_multi_item->status = $params['status'];
				}
                else
                {
                    $response['sub_code'] = 1; $response['desc'] = __LINE__; break;
                }

                $rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
            }
            else
            {
                $obj_user_game_multi_factory->clear();
                $response['sub_code'] = 2; $response['desc'] = __LINE__; break;
            }

            if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
            {
                $response['sub_code'] = 3; $response['desc'] = __LINE__; break;
            }

            $obj_user_game_multi_factory->writeback();

            $data['list'] = [];
            $response['data'] = $data;

        }while(false);

        return $response;
    }

    //黑名单列表
    public function blacklist($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $data = array();
        $tmp_name =array();
        $data_count = 0;
        $page_count = CatConstant::CNT_PER_PAGE;

        do {
            if(
                !isset($params['aidArray'])
                || !isset($params['page'])
				|| !isset($params['keywords'])
			)
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

            $page = isset($params['page']) ? intval($params['page']) : 1;

            $this->_init_cache();

            //根据不同aid查询uid
			if(!empty($params['keywords']))
			{
				$sql = "select `uid` from `user_game` where status = 1 AND (agent_id = ".$params['keywords']." OR uid = ".$params['keywords'].")";
			}
			else
			{
				if (empty($params['aidArray']))
				{
					$sql = "select `uid` from `user_game` where status = 1";
				}
				else
				{
					$aidArray = '('.$params['aidArray'].')';
					$sql = "select `uid` from `user_game` where agent_id in ". $aidArray . "and status = 1";
				}
			}

			$obj_user_game_list_factory = new UserGameListFactory($this->cache_handler,null,'',$sql,md5($params['aidArray']));

            if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
            {
                $obj_user_game_list = $obj_user_game_list_factory->get();

                //获取总数
                $data_count = count($obj_user_game_list);
                $obj_user_game_list_page_arr = array_slice($obj_user_game_list, ($page - 1) * $page_count, $page_count);

                //组合数据
                $obj_user_list_factory = new UserListFactory($this->cache_handler, null,implode(',',$obj_user_game_list_page_arr));

                if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
                {
                    $obj_user_multi_factory = new UserMultiFactory($this->cache_handler,$obj_user_list_factory);
                    if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
                    {
                        $obj_user_multi = $obj_user_multi_factory->get();
                        if(is_array($obj_user_multi))
                        {
                            foreach ($obj_user_multi as $obj_user_multi_item)
                            {
                                $tmp_name[$obj_user_multi_item->uid] = $obj_user_multi_item;
                            }
                        }
                    }
                }

                $obj_user_game_list_factory = new UserGameListFactory($this->cache_handler,null, implode(',',$obj_user_game_list_page_arr));

                if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
                {
                    $obj_usergame_multi_factory =  new UserGameMultiFactory($this->cache_handler,$obj_user_game_list_factory);
                    if($obj_usergame_multi_factory->initialize() && $obj_usergame_multi_factory->get())
                    {
                        $obj_usergame_multi = array_values($obj_usergame_multi_factory->get());
                        usort($obj_usergame_multi,array('bigcat\controller\Business','cmp_list_update_time'));
                        if(is_array($obj_usergame_multi))
                        {
                            foreach ($obj_usergame_multi as $obj_usergame_multi_item)
                            {
                                if(isset($tmp_name[$obj_usergame_multi_item->uid]))
                                {
                                    $obj_usergame_multi_item->name = $tmp_name[$obj_usergame_multi_item->uid]->name;
                                    $obj_usergame_multi_item->wx_pic = $tmp_name[$obj_usergame_multi_item->uid]->wx_pic;
                                }
                                $obj_usergame_multi_item->update_time = date("Y-m-d H:i:s",$obj_usergame_multi_item->update_time);
                                $data['list'][] = $obj_usergame_multi_item;
                            }
                        }
                    }
                }
            }
            else
            {
                $data['list'] = [];
                $obj_user_game_list_factory->clear();
            }

            $data['data_count'] = $data_count;
            $data['page_count'] = $page_count;
            $response['data'] = $data;

        }while(false);

        return $response;
    }

    //获取kpi
    public function kpi_get_new($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $itime = time();
        $data = array();
        do {
            if(
                !isset($params['aid'])
                ||!isset($params['aidArray'])
                ||!isset($params['type'])
            )
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

            $this->_init_cache();

            $id_time_now = strtotime(date('Y-m-d', $itime));
            $id_time_two_week_ago = $id_time_now - 13 * 86400;

            //第一种方案
            if ($params['type'] == 9)
            {
                $agent_id = '8600000000000';
                $kpi_sql = "select id from `kpi_new` WHERE agent_id = ".$agent_id." and id_time >= ".$id_time_two_week_ago." and id_time<= $id_time_now";
            }
            else
            {
                $kpi_sql = "select id from `kpi_new` WHERE agent_id = ".$params['aid']." and id_time >= ".$id_time_two_week_ago." and id_time <= $id_time_now";
            }

			$obj_kpi_list_factory = new KpiNewListFactory($this->cache_handler, null,'',null,null,null,$kpi_sql);

            if($obj_kpi_list_factory->initialize() && $obj_kpi_list_factory->get())
            {
                $obj_kpi_multi_factory = new KpiNewMultiFactory($this->cache_handler, $obj_kpi_list_factory);
                if ($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
                {
                    $obj_kpi_multi = $obj_kpi_multi_factory->get();
                    foreach ($obj_kpi_multi as $obj_kpi_multi_item)
                    {
                        $data['list'][] = $obj_kpi_multi_item;
                    }
                }
                else
                {
                	$obj_kpi_multi_factory->clear();
                    $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
                }
            }

            $response['data'] = $data;
        }while(false);

        return $response;
    }

    //代理收益统计
	public function income_statistics($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $rawsqls = array();
        $itime = time();
        $data = array('list'=>[],'operator'=>[]);
        $tmp_name =array();
        $data_count = 0;
		$page_count = CatConstant::CNT_PER_PAGE;
		$aids_exist = array();

        do {
            if(
                empty($params['type'])
                || !isset($params['aidArray'])
                || empty($params['page'])
                || empty($params['aid'])
                || !isset($params['start_time'])
                || !isset($params['end_time'])
            )
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

            $page = isset($params['page']) ? intval($params['page']) : 1;
            $this->_init_cache();
            //前一天零点
            $id_time = strtotime(date('Y-m-d', $itime));

            $sql = "select id from `kpi_new`";
            if (!empty($params['start_time']) || !empty($params['end_time']))
            {
                if(!empty($params['start_time']))
                {
                    $sql .= " where id_time >= ".$params['start_time'];

                    if (!empty($params['end_time'])) 
                    {
                    	$sql .= " and id_time < ".$params['end_time'];
                    }
                }
                else
                {
                	if(!empty($params['end_time']))
	                {
	                    $sql .= " where id_time < ".$params['end_time'];
	                }
                }
            }
            else
            {
            	$sql .= " where id_time = $id_time";
            }

            if ($params['type'] != 9) 
            {
				if(!empty($params['aidArray']))
				{
					$aid_group = explode(',', $params['aidArray']);
					$data_count = count($aid_group);
					$data_count_page_arr = array_slice($aid_group, ($page - 1) * $page_count, $page_count);
					array_unshift($data_count_page_arr, $params['aid']);
				}
				else
				{
					$data_count_page_arr = [$params['aid']];
				}

            	$aidArray = '('.implode(',', $data_count_page_arr).')';
            	$sql .= " and agent_id IN ".$aidArray;
            }
            else
            {
            	if (!empty($params['keywords'])) 
            	{
            		$sql .= " and agent_id =".$params['keywords'];
            	}
            }

            $obj_kpi_new_list_factory = new KpiNewListFactory($this->cache_handler, null,'',null,null,null,$sql);
            if($obj_kpi_new_list_factory->initialize() && $obj_kpi_new_list_factory->get())
            {
        		$obj_kpi_new_multi_factory = new KpiNewMultiFactory($this->cache_handler, $obj_kpi_new_list_factory);
                if ($obj_kpi_new_multi_factory->initialize() && $obj_kpi_new_multi_factory->get())
                {
                    $obj_kpi_new_multi = $obj_kpi_new_multi_factory->get();
                    foreach ($obj_kpi_new_multi as $obj_kpi_new_multi_item)
                    {
                    	$aids_exist[] = $obj_kpi_new_multi_item->agent_id;
                    	unset($obj_kpi_new_multi_item->all_user);
                		unset($obj_kpi_new_multi_item->new_user);
                		unset($obj_kpi_new_multi_item->active_user);
                		unset($obj_kpi_new_multi_item->game_num);
                		unset($obj_kpi_new_multi_item->hour_user);
                		unset($obj_kpi_new_multi_item->currency_direct);
                		unset($obj_kpi_new_multi_item->currency_subordinate);
                		unset($obj_kpi_new_multi_item->play_time);
                    	if (($params['type'] == 9 && $obj_kpi_new_multi_item->agent_id == "8600000000000") || ($obj_kpi_new_multi_item->agent_id == $params['aid']) && $params['type'] != 9) 
                    	{

                    		$data['operator'][] = $obj_kpi_new_multi_item;
                    	}
                    	else
                    	{
	                    	$data['list'][] = $obj_kpi_new_multi_item;
                    	}
                    }
                }
                else
                {
                	$obj_kpi_new_multi_factory->clear();
                    $response['code'] = 3; $response['desc'] = __LINE__; break;
                }
			}

			if ($params['type'] != 9) 
         	{
         		$aids_not_exist = array_diff($data_count_page_arr, $aids_exist);
                foreach ($aids_not_exist as $key => $aid) 
                {
                	$data['list'][] = [
                		'id' => 0,
                		'id_time' => 0,
                		'recharge_direct' => 0,
                		'recharge_subordinate' => 0,
                		'recharge_direct_shared' => 0,
                		'recharge_subordinate_shared' => 0,
                		'recharge_under_subordinate' => 0,
                		'recharge_under_subordinate_shared' => 0,
                		'pay_status' => 0,
                		'agent_id' => $aid,
                	];
                }
         	}

			if (isset($obj_kpi_new_list_factory)) 
			{
				$obj_kpi_new_list_factory->clear();
			}

			if (isset($obj_kpi_new_multi_factory)) 
			{
				$obj_kpi_new_multi_factory->clear();
			}

            $data['data_count'] = $data_count;
            $data['page_count'] = $page_count;
			$response['data'] = $data;

        }while(false);

        return $response;
    }

    //改变用户agent_id
    public function change_user_agent($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $rawsqls = array();
        $itime = time();
        $data = array();
        $sql = '';

        do {
            if(empty($params['aid_before_change']) || empty($params['aid_after_change']))
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

            $this->_init_cache();

            $sql = "select `uid` from `user_game` where agent_id=".intval($params['aid_before_change']);
            $obj_user_game_list_factory = new UserGameListFactory($this->cache_handler,'',null,$sql, md5($sql));
            if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
            {
                $obj_user_game_multi_factory = new UserGameMultiFactory($this->cache_handler, $obj_user_game_list_factory);
                if($obj_user_game_multi_factory->initialize() && $obj_user_game_multi_factory->get())
                {
                    $obj_user_game_multi = $obj_user_game_multi_factory->get();
                    foreach ($obj_user_game_multi as $obj_user_game_multi_item)
                    {
                        $obj_user_game_multi_item->agent_id = $params['aid_after_change'];
                        $rawsqls[] = $obj_user_game_multi_item->getUpdateSql();
                    }
                }
                else
                {
                    $obj_user_game_multi_factory->clear();
                    $response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
                }
            }
            else
            {
                $obj_user_game_list_factory->clear();
            }

            if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
            {
                $response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
            }

            if (isset($obj_user_game_multi_factory))
            {
                $obj_user_game_multi_factory->writeback();
            }

            $data['list'] = [];
            $response['data'] = $data;

        }while(false);

        return $response;
	}

	//查询制定玩家消耗记录
	public function find_play_recharge($params)
    {
        $response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
        $data = array();
        $tmp = array();
		$sql = '';
		$page_count = CatConstant::CNT_PER_PAGE;
		$data_count = 0;

        do {
			if(empty($params['uid'])
			|| empty($params['page'])
			)
            {
                $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
            }

			$this->_init_cache();
			$page = isset($params['page']) ? intval($params['page']) : 1;

			$sql = "select `id` from `user_log` where uid = ".intval($params['uid']) ." order by `id` desc";

			$obj_user_log_list_factory = new UserLogListFactory($this->cache_handler,null,null,null,null,$sql,md5($sql));
			if($obj_user_log_list_factory->initialize() && $obj_user_log_list_factory->get())
			{
				$obj_user_log_list = $obj_user_log_list_factory->get();
				$data_count = count($obj_user_log_list);
				$obj_user_log_list_page_arr = array_slice($obj_user_log_list, ($page - 1) * $page_count, $page_count);
				$obj_user_log_list_factory = new UserLogListFactory($this->cache_handler,null,null,null,implode(',',$obj_user_log_list_page_arr));
				if($obj_user_log_list_factory->initialize() && $obj_user_log_list_factory->get())
				{
					$obj_user_log_mulit_factory = new UserLogMultiFactory($this->cache_handler,$obj_user_log_list_factory);
					if($obj_user_log_mulit_factory->initialize() && $obj_user_log_mulit_factory->get())
					{
						$obj_user_log_mulit = array_values($obj_user_log_mulit_factory->get());
						usort($obj_user_log_mulit,array('bigcat\controller\Business','cmp_list'));
						if(!empty($obj_user_log_mulit))
						{
							foreach($obj_user_log_mulit as $obj_user_log_muliti_item)
							{
								
								switch ($obj_user_log_muliti_item->type)
								{
								case 1:
									$obj_user_log_muliti_item->type = "开房消耗";
									break;
								case 2:
									$obj_user_log_muliti_item->type = "公司补钻";
									break;
								case 3:
									$obj_user_log_muliti_item->type = "微信分享";
									break;
								case 4:
									$obj_user_log_muliti_item->type = "微信支付";
									break;
								case 21:
									$obj_user_log_muliti_item->type = "红钻消耗(红钻兑换积分)";
									break;
								case 22:
									$obj_user_log_muliti_item->type = "红钻充值";
									break;
								case 23:
									$obj_user_log_muliti_item->type = "后台红钻充值";
									break;
								case 31:
									$obj_user_log_muliti_item->type = "游戏积分增减";
									break;
								case 32:
									$obj_user_log_muliti_item->type = "开房积分赠送";
									break;
								case 33:
									$obj_user_log_muliti_item->type = "积分换礼物";
									break;
								case 34:
									$obj_user_log_muliti_item->type = "积分增加(红钻兑换积分)";
									break;
								case 35:
									$obj_user_log_muliti_item->type = "后台充值积分";
									break;
								case 41:
									$obj_user_log_muliti_item->type = "游戏赠送奖杯";
									break;
								case 42:
									$obj_user_log_muliti_item->type = "奖杯换礼物";
									break;
								case 43:
									$obj_user_log_muliti_item->type = "后台充值奖杯";
									break;
								case 51:
									$obj_user_log_muliti_item->type = "邀请新玩家";
									break;
								case 52:
									$obj_user_log_muliti_item->type = "邀请的玩家完成充值";
									break;
								case 53:
									$obj_user_log_muliti_item->type = "邀请的玩家完成10桌游戏";
									break;
								case 61:
									$obj_user_log_muliti_item->type = "关注领钻";
									break;
								default:
									;
								}
						
								$obj_user_log_muliti_item->time = date("Y-m-d H:i:s",$obj_user_log_muliti_item->time);

								$tmp[] = $obj_user_log_muliti_item;
							}
						}
					}
				}
				else
				{
					$obj_user_log_list_factory->clear();
				}

			}
			else
			{
				$obj_user_log_list_factory->clear();
			}

			$data['data_count'] = $data_count;
            $data['page_count'] = $page_count;
			$data['list'] = $tmp;
            $response['data'] = $data;

        }while(false);

        return $response;
	}

	//查询制定玩家消耗记录
	public function find_play_video($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$data = array();
		$page_count = CatConstant::CNT_PER_PAGE;
		$data_count = 0;
		$tmp = array();

		do {
			if(empty($params['uid'])
			|| empty($params['page'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$this->_init_cache();
			$page = isset($params['page']) ? intval($params['page']) : 1;

			$obj_game_log_user_list_factory = new GameLogUserListFactory($this->cache_handler,$params['uid']);
			if($obj_game_log_user_list_factory->initialize() && $obj_game_log_user_list_factory->get())
			{
				$obj_game_log_user_list = $obj_game_log_user_list_factory->get();
				$data_count = count($obj_game_log_user_list);
				$obj_user_log_list_page_arr = array_slice($obj_game_log_user_list, ($page - 1) * $page_count, $page_count);

				$obj_game_log_list_factory = new GameLogListFactory($this->cache_handler,null,implode(',',$obj_user_log_list_page_arr));
				if($obj_game_log_list_factory->initialize() && $obj_game_log_list_factory->get())
				{
					$obj_game_log_multi_factory =  new GameLogMultiFactory($this->cache_handler,$obj_game_log_list_factory);
					if($obj_game_log_multi_factory->initialize() && $obj_game_log_multi_factory->get())
					{
						$obj_game_log_multi = array_values($obj_game_log_multi_factory->get());
						usort($obj_game_log_multi,array('bigcat\controller\Business','cmp_list'));
						if(is_array($obj_game_log_multi))
						{
							foreach($obj_game_log_multi as $obj_game_log_multi_item)
							{
								unset($obj_game_log_multi_item->game_info);
								unset($obj_game_log_multi_item->type);
								unset($obj_game_log_multi_item->save);
								$obj_game_log_multi_item->time = date("Y-m-d H:i:s",$obj_game_log_multi_item->time);
								$tmp[] = $obj_game_log_multi_item;
							}
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
			else
			{
				$obj_game_log_user_list_factory->clear();
			}

			$data['data_count'] = $data_count;
            $data['page_count'] = $page_count;
			$data['list'] = $tmp;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//代理收益统计
	public function update_income_pay_status($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if(  empty($params['aid'])
				|| !isset($params['pay_status'])
				|| empty($params['start_time'])
				|| empty($params['end_time'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$this->_init_cache();
			$sql = "select id from `kpi_new` WHERE agent_id = ".$params['aid']." and id_time >= ".$params['start_time'] . " and id_time < ".$params['end_time'];
            
			$obj_kpi_list_factory = new KpiNewListFactory($this->cache_handler, null,'',null,null,null,$sql);
            if($obj_kpi_list_factory->initialize() && $obj_kpi_list_factory->get())
            {
                $obj_kpi_multi_factory = new KpiNewMultiFactory($this->cache_handler, $obj_kpi_list_factory);
                if ($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
                {
                    $obj_kpi_multi = $obj_kpi_multi_factory->get();
                    foreach ($obj_kpi_multi as $obj_kpi_multi_item)
                    {
						$obj_kpi_multi_item->pay_status = $params['pay_status'];
						$rawsqls[] = $obj_kpi_multi_item->getUpdateSql();						
                    }
                }
                else
                {
                	$obj_kpi_multi_factory->clear();
                    $response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
                }
			}
			else
			{
				$obj_kpi_list_factory->clear();
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
            {
                $response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
            }

            if (isset($obj_kpi_multi_factory))
            {
                $obj_kpi_multi_factory->writeback();
            }

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//查看礼物兑换信息
	public function show_gift_exchange_log($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			
			$this->_init_cache();

			$data['all_count'] = 0;
			$data['list'] = [];
			$count_per_page = 20;
			$data['count_per_page'] = $count_per_page;

			$params['page'] = isset($params['page']) ? $params['page'] : 1;
			$params['uid'] = !empty($params['uid']) ? $params['uid'] : null;
			$params['state'] = !empty($params['state']) ? $params['state'] : null;
			
			$obj_gift_exchange_log_list_factory = new GiftExchangeLogListFactory($this->cache_handler, $params['uid'], '', $params['state']);
			if($obj_gift_exchange_log_list_factory->initialize() && $obj_gift_exchange_log_list_factory->get())
			{	
				$obj_gift_exchange_log_list = $obj_gift_exchange_log_list_factory->get();
				$data['all_count'] = count($obj_gift_exchange_log_list);
				$id_page_arr = array_slice($obj_gift_exchange_log_list, ($params['page'] - 1) * $count_per_page, $count_per_page);
				
				if ($id_page_arr && is_array($id_page_arr)) 
				{
					$obj_page_gift_exchange_log_list_factory = new GiftExchangeLogListFactory($this->cache_handler, null, implode(',', $id_page_arr));
					if ($obj_page_gift_exchange_log_list_factory->initialize() && $obj_page_gift_exchange_log_list_factory->get()) 
					{
						$obj_gift_exchange_log_multi_factory = new GiftExchangeLogMultiFactory($this->cache_handler, $obj_page_gift_exchange_log_list_factory);
						if ($obj_gift_exchange_log_multi_factory->initialize() && $obj_gift_exchange_log_multi_factory->get()) 
						{
							$obj_gift_exchange_log_multi = array_values($obj_gift_exchange_log_multi_factory->get());
							usort($obj_gift_exchange_log_multi,array('bigcat\controller\Business','cmp_list_update_time'));

							foreach ($obj_gift_exchange_log_multi as $obj_gift_exchange_log_multi_item) 
							{
								$data['list'][] = $obj_gift_exchange_log_multi_item;
							}
						}
						else
						{
							$obj_gift_exchange_log_multi_factory->clear();
							$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
						}
					}
				}
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

    //玩家充值记录列表
    public function player_recharge_list_new($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$itime = time();
		$data = array();
		$tmp_name =array();
		$data_count = 0;
		$sql='';
		$page_count = CatConstant::CNT_PER_PAGE;
		$uid_group = [];

		do {
			if(
				empty($params['page'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			$page = isset($params['page']) ? intval($params['page']) : 1;
			$this->_init_cache();

			//玩家充值记录
			if (empty($params['agent_id']) && empty($params['play_id'])) 
			{
				$sql = "select `id` from `user_log` where type = 4 ";
				$key = "JUITYJ";
			}
			else
			{
				if (!empty($params['play_id'])) 
				{
					$sql = "select `id` from `user_log` where uid = ".intval($params['play_id'])." and type = 4";
					$key = "cesf";
				}

				if (!empty($params['agent_id'])) 
				{
					$sql = "select `id` from `user_log` where aid = ".$params['agent_id']." and type = 4";
					$key = md5($sql);
				}
			}
			
			if(!empty($params['start_time']))
			{
				$sql =$sql." and time >= ".$params['start_time']."";
				$key.= $params['start_time'];
			}
			if(!empty($params['end_time']))
			{
				$sql = $sql." and time <= ".$params['end_time']."";
				$key.= $params['end_time'];
			}
			$sql .= " order by time desc";

			$obj_userlog_list_factory = new UserLogListFactory($this->cache_handler, null, null ,null ,null ,$sql, $key);
			if($obj_userlog_list_factory->initialize() && $obj_userlog_list_factory->get())
			{
				$obj_userlog_list = $obj_userlog_list_factory->get();
				$data_count = count($obj_userlog_list);
				$obj_userlog_list_page_arr = array_slice($obj_userlog_list, ($page - 1) * $page_count, $page_count);

				$obj_userlog_list_factory = new UserLogListFactory($this->cache_handler, null, null, null, implode(',', $obj_userlog_list_page_arr) );
				if($obj_userlog_list_factory->initialize() && $obj_userlog_list_factory->get())
				{
					$obj_userlog_multi_factory = new UserLogMultiFactory($this->cache_handler,$obj_userlog_list_factory);
					if($obj_userlog_multi_factory->initialize() && $obj_userlog_multi_factory->get())
					{
						$obj_userlog_multi = array_values($obj_userlog_multi_factory->get());
	                	usort($obj_userlog_multi,array('bigcat\controller\Business','cmp_list'));
                		foreach($obj_userlog_multi as $obj_userlog_multi_item)
                		{
                			if (!isset($uid_group[$obj_userlog_multi_item->uid])) 
                			{
                				$uid_group[] = $obj_userlog_multi_item->uid;
                			}
                		}

                		$obj_user_list_factory = new UserListFactory($this->cache_handler, null, implode(',', $uid_group));
                		if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
						{
			                $obj_user_multi_factory = new UserMultiFactory($this->cache_handler,$obj_user_list_factory);
							if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
							{
								$obj_user_multi = $obj_user_multi_factory->get();
								foreach ($obj_user_multi as $obj_user_multi_item)
								{
									$tmp_name[$obj_user_multi_item->uid] = $obj_user_multi_item->name;
								}
							}
						}

						foreach($obj_userlog_multi as $obj_userlog_multi_item)
                		{
                			if (isset($tmp_name[$obj_userlog_multi_item->uid])) 
                			{
                				$obj_userlog_multi_item->name = $tmp_name[$obj_userlog_multi_item->uid];
                			}
                			$obj_userlog_multi_item->time = date("Y-m-d H:i:s",$obj_userlog_multi_item->time);
                			$data['list'][] = $obj_userlog_multi_item;
                		}
					}
				}
			}
			else
			{
				$data['list'] = []; break;
			}

		}while(false);

		$data['data_count'] = $data_count;
		$data['page_count'] = $page_count;
		$response['data'] = $data;
		return $response;
	}

	//统计三级
	public function recordKpiThreeLevels()
	{
		//每天按照每个代理统计一下
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$all_user=0;
		$new_user=0;
		$active_user=0;
		$game_num=0;
		$money=0;
		$tmp_sum_hour_arr=array();
		$aid_group = array();

		do {

			$this->_init_cache();

			//前一天零点
			$id_time = strtotime(date('Y-m-d', $itime));
			$id_time_now = $id_time + 86400;
			$id_time_hour_now = strtotime(date('Y-m-d H:0:0', $itime));
			$id_time_hour = $id_time_hour_now - 3600;
			$hour_name = date('H', $id_time_hour);
			
			if ($hour_name == 23) 
			{
				$id_time_now = strtotime(date('Y-m-d', $itime));
				$id_time = $id_time_now - 86400;
			}

			$obj_kpi_list_factory = new KpiNewListFactory($this->cache_handler, null, '', null,null, $id_time);
			if ($obj_kpi_list_factory->initialize() && $obj_kpi_list_factory->get())
			{
				$obj_kpi_multi_factory = new KpiNewMultiFactory($this->cache_handler,$obj_kpi_list_factory);
				if($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
				{
					$obj_kpi_multi = $obj_kpi_multi_factory->get();
					foreach ($obj_kpi_multi as $obj_kpi_multi_item)
					{
						$obj_kpi_multi[$obj_kpi_multi_item->agent_id] = $obj_kpi_multi_item;
					}
				}
			}

			//代理直属玩家数
			$all_user_sql = "select count(*),agent_id from `user_game` where update_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理新增直属玩家数
			$new_user_sql = "select count(*),agent_id from `user_game` WHERE update_time >= $id_time and update_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理直属活跃用户数
			$active_user_sql = "select count(*), agent_id from `user_game` WHERE last_game_time >= $id_time and last_game_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理每小时直属活跃用户
			$hour_user_sql = "select count(*), agent_id from `user_game` WHERE last_game_time >= $id_time_hour and last_game_time < $id_time_hour_now GROUP BY agent_id HAVING agent_id != 0";
			//代理直属用户充值记录
			$recharge_direct_sql = "select sum(money),aid from `user_log` where time >= $id_time and time < $id_time_now and type = 4 GROUP BY aid HAVING aid != 0";
			//代理直属用户耗钻记录
			$currency_direct_sql = "select sum(currency),aid from `user_log` where time >= $id_time and time < $id_time_now and type=1 GROUP BY aid HAVING aid != 0";

			$sql_group = [
				$all_user_sql, 
				$new_user_sql, 
				$active_user_sql, 
				$recharge_direct_sql, 
				$currency_direct_sql, 
				$hour_user_sql
			];

			$sql_name = [
					'all_user',
					'new_user',
					'active_user',
					'recharge_direct',
					'currency_direct',
					'hour_user_sql'
				];

			$result_group = BaseFunction::execute_sql_backend($sql_group);
			if ($result_group && count($result_group) == count($sql_name)) 
			{
				foreach ($result_group as $name_key => $result) 
				{
					$rows = [];
					$row = $result['result']->fetch_all();

					foreach ($row as $key => $value)
					{
						if (!in_array($value[1], $aid_group)) 
						{
							$aid_group[] = $value[1];
						}
						$rows[$value[1]] = $value[0];
					}
					
					$kpi_arr[$sql_name[$name_key]] = $rows;
				}
			}
			else
			{
				BaseFunction::logger($this->log, "【data_request】:\n" . var_export($result_group, true) . "\n" . __LINE__ . "\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
			}

			//获取所有代理信息
			$agentGroup = $this->_get_all_agents();

			//$aid_group 表示所有当天有活动的用户
			foreach ($aid_group as $aid) 
			{
				if (isset($agentGroup['detail'][$aid])) 
				{
					$agent_detail = $agentGroup['detail'][$aid];
					$select_condition_group = ['all_user','new_user','active_user','recharge_direct','recharge_subordinate','currency_direct','currency_subordinate','hour_user_sql','recharge_under_subordinate'];
					foreach ($select_condition_group as $select_condition)
					{
						if (!isset($kpi_arr[$select_condition][$aid]))
						{
							$kpi_arr[$select_condition][$aid] = 0;
						}
						$select_data[$aid][$select_condition] = $kpi_arr[$select_condition][$aid];
					}

					$select_data[$aid]['recharge_direct_shared'] = $agent_detail->shared;
					$select_data[$aid]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
					$select_data[$aid]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
				}
			}

			//处理会长数据
			$type1Group = $agentGroup['aid']['type1'];
			foreach ($type1Group as $aidOfType1=> $agentArrayofType2)
			{
				if (isset($agentGroup['detail'][$aidOfType1])) 
				{
					if (!empty($agentArrayofType2))
					{
						if (!isset($select_data[$aidOfType1]))
						{
							$select_data[$aidOfType1] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}

						foreach ($agentArrayofType2 as $aidOfType2)
						{
							if (isset($select_data[$aidOfType2]))
							{
								$select_data[$aidOfType1]['all_user'] += $select_data[$aidOfType2]['all_user'];
								$select_data[$aidOfType1]['new_user'] += $select_data[$aidOfType2]['new_user'];
								$select_data[$aidOfType1]['active_user'] += $select_data[$aidOfType2]['active_user'];
								$select_data[$aidOfType1]['recharge_subordinate'] += $select_data[$aidOfType2]['recharge_direct'];
								$select_data[$aidOfType1]['currency_subordinate'] += $select_data[$aidOfType2]['currency_direct'];
								$select_data[$aidOfType1]['hour_user_sql'] += $select_data[$aidOfType2]['hour_user_sql'];
							}
						}

						if (array_sum($select_data[$aidOfType1]) == 0) 
						{
							unset($select_data[$aidOfType1]);
						}
					}
					else
					{
						if (!isset($select_data[$aidOfType1]) && !empty($obj_kpi_multi) && isset($obj_kpi_multi[$aidOfType1])) 
						{
							$select_data[$aidOfType1] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}
					}

					$agent_detail = $agentGroup['detail'][$aidOfType1];
					if (isset($select_data[$aidOfType1]))
					{
						$select_data[$aidOfType1]['recharge_direct_shared'] = $agent_detail->shared;
						$select_data[$aidOfType1]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
						$select_data[$aidOfType1]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
					}
				}
			}

			$type8Group = $agentGroup['aid']['type8'];
			$select_data['8600000000000'] = [
				'all_user' => 0,
				'new_user' => 0,
				'active_user' => 0,
				'recharge_direct' => 0,
				'recharge_subordinate' => 0,
				'recharge_under_subordinate' => 0,
				'currency_direct' => 0,
				'currency_subordinate' => 0,
				'hour_user_sql'=>0
			];

			//处理城市合伙人数据
			foreach ($type8Group as $aidOfType8=> $agentArrayofType1)
			{
				if (isset($agentGroup['detail'][$aidOfType8])) 
				{
					if (!empty($agentArrayofType1))
					{
						if (!isset($select_data[$aidOfType8]))
						{
							$select_data[$aidOfType8] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}

						foreach ($agentArrayofType1 as $aidOfType1)
						{
							if (isset($select_data[$aidOfType1]))
							{
								$select_data[$aidOfType8]['all_user'] += $select_data[$aidOfType1]['all_user'];
								$select_data[$aidOfType8]['new_user'] += $select_data[$aidOfType1]['new_user'];
								$select_data[$aidOfType8]['active_user'] += $select_data[$aidOfType1]['active_user'];
								$select_data[$aidOfType8]['recharge_subordinate'] += $select_data[$aidOfType1]['recharge_direct'];
								$select_data[$aidOfType8]['recharge_under_subordinate'] += $select_data[$aidOfType1]['recharge_subordinate'];
								$select_data[$aidOfType8]['currency_subordinate'] += ($select_data[$aidOfType1]['currency_direct'] + $select_data[$aidOfType1]['currency_subordinate']);
								$select_data[$aidOfType8]['hour_user_sql'] += $select_data[$aidOfType1]['hour_user_sql'];
							}
						}

						if (array_sum($select_data[$aidOfType8]) == 0) 
						{
							unset($select_data[$aidOfType8]);
						}
					}
					else
					{
						if (!isset($select_data[$aidOfType8]) && !empty($obj_kpi_multi) && isset($obj_kpi_multi[$aidOfType8])) 
						{
							$select_data[$aidOfType8] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}
					}

					$agent_detail = $agentGroup['detail'][$aidOfType8];
					if (isset($select_data[$aidOfType8]))
					{
						$select_data[$aidOfType8]['recharge_direct_shared'] = $agent_detail->shared;
						$select_data[$aidOfType8]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
						$select_data[$aidOfType8]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
					}

					//总公司数据
					if (isset($select_data[$aidOfType8]))
					{
						$select_data['8600000000000']['all_user'] += $select_data[$aidOfType8]['all_user'];
						$select_data['8600000000000']['new_user'] += $select_data[$aidOfType8]['new_user'];
						$select_data['8600000000000']['active_user'] += $select_data[$aidOfType8]['active_user'];
						$select_data['8600000000000']['recharge_subordinate'] += ($select_data[$aidOfType8]['recharge_direct'] + $select_data[$aidOfType8]['recharge_subordinate']+ $select_data[$aidOfType8]['recharge_under_subordinate']);
						$select_data['8600000000000']['currency_subordinate'] += ($select_data[$aidOfType8]['currency_direct'] + $select_data[$aidOfType8]['currency_subordinate']);
						$select_data['8600000000000']['hour_user_sql'] += $select_data[$aidOfType8]['hour_user_sql'];
						$select_data['8600000000000']['recharge_direct_shared'] = 1;
						$select_data['8600000000000']['recharge_subordinate_shared'] = 1;
						$select_data['8600000000000']['recharge_under_subordinate_shared'] = 1;
					}
				}
			}

			foreach ($select_data as $aid => $agentInfo) 
			{
				if (!empty($obj_kpi_multi) && isset($obj_kpi_multi[$aid])) 
				{
					$obj_kpi = $obj_kpi_multi[$aid];
					$obj_kpi->all_user = $select_data[$aid]['all_user'];
					$obj_kpi->new_user = $select_data[$aid]['new_user'];
					$obj_kpi->active_user = $select_data[$aid]['active_user'];
					$obj_kpi->recharge_direct = $select_data[$aid]['recharge_direct'];
					$obj_kpi->recharge_subordinate = $select_data[$aid]['recharge_subordinate'];
					$obj_kpi->currency_direct = $select_data[$aid]['currency_direct'];
					$obj_kpi->currency_subordinate = $select_data[$aid]['currency_subordinate'];
					$obj_kpi->agent_id = $aid;
					$obj_kpi->recharge_direct_shared = $select_data[$aid]['recharge_direct_shared'];
					$obj_kpi->recharge_subordinate_shared = $select_data[$aid]['recharge_subordinate_shared'];
					$obj_kpi->recharge_under_subordinate = $select_data[$aid]['recharge_under_subordinate'];
					$obj_kpi->recharge_under_subordinate_shared = $select_data[$aid]['recharge_under_subordinate_shared'];
					$tmp_hour_arr = json_decode($obj_kpi->hour_user, true);
					if(!is_array($tmp_hour_arr))
					{
						$tmp_hour_arr = array();
					}

					$tmp_hour_arr[$hour_name] = $select_data[$aid]['hour_user_sql'];
					$obj_kpi->hour_user = json_encode($tmp_hour_arr);

					$obj_kpi->game_num = 0;
					$obj_kpi->play_time = 0;

					$rawsqls[] = $obj_kpi->getUpdateSql();
				}
				else
				{
					$obj_kpi = new KpiNew();
					$obj_kpi->id_time = $id_time;
					$obj_kpi->agent_id = $aid;
					$obj_kpi->all_user = $agentInfo['all_user'];

					$obj_kpi->new_user = $agentInfo['new_user'];
					$obj_kpi->active_user = $agentInfo['active_user'];
					$obj_kpi->recharge_direct = $agentInfo['recharge_direct'];
					$obj_kpi->currency_direct = $agentInfo['currency_direct'];
					$obj_kpi->recharge_subordinate = $agentInfo['recharge_subordinate'];
					$obj_kpi->currency_subordinate = $agentInfo['currency_subordinate'];
					$obj_kpi->recharge_direct_shared = $select_data[$aid]['recharge_direct_shared'];
					$obj_kpi->recharge_subordinate_shared = $select_data[$aid]['recharge_subordinate_shared'];
					$obj_kpi->recharge_under_subordinate = $select_data[$aid]['recharge_under_subordinate'];
					$obj_kpi->recharge_under_subordinate_shared = $select_data[$aid]['recharge_under_subordinate_shared'];
					
					$tmp_hour_arr = json_decode($obj_kpi->hour_user, true);
					if(!is_array($tmp_hour_arr))
					{
						$tmp_hour_arr = array();
					}
					$tmp_hour_arr[$hour_name] = $agentInfo['hour_user_sql'];

					$obj_kpi->hour_user = json_encode($tmp_hour_arr);
					$obj_kpi->game_num = 0;
					$obj_kpi->play_time = 0;

					$rawsqls[] = $obj_kpi->getInsertSql();
				}
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【data_request】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
			}

			if(isset($obj_kpi_multi_factory))
			{
				$obj_kpi_multi_factory->writeback();
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	//跑某天kpi
    public function update_someday_kpi($params)
    {
        //每天按照每个代理统计一下
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$all_user=0;
		$new_user=0;
		$active_user=0;
		$game_num=0;
		$money=0;
		$tmp_sum_hour_arr=array();
		$aid_group = array();
		$total_recharge = 0;
		
		do {
			if (empty($params['day']) )
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
            $this->_init_cache();

            //前一天零点
            $itime = $itime + $params['day'] * 86400;
			$id_time = strtotime(date('Y-m-d', $itime));
			$id_time_now = $id_time + 86400;
			$id_time_hour_now = strtotime(date('Y-m-d H:0:0', $itime));
			$id_time_hour = $id_time_hour_now - 3600;
			$hour_name = date('H', $id_time_hour);
			
			if ($hour_name == 23) 
			{
				$id_time_now -= 86400;
				$id_time -= 86400;
			}

			$obj_kpi_list_factory = new KpiNewListFactory($this->cache_handler, null, '', null,null, $id_time);
			if ($obj_kpi_list_factory->initialize() && $obj_kpi_list_factory->get())
			{
				$obj_kpi_multi_factory = new KpiNewMultiFactory($this->cache_handler,$obj_kpi_list_factory);
				if($obj_kpi_multi_factory->initialize() && $obj_kpi_multi_factory->get())
				{
					$obj_kpi_multi = $obj_kpi_multi_factory->get();
					foreach ($obj_kpi_multi as $obj_kpi_multi_item)
					{
						$obj_kpi_multi[$obj_kpi_multi_item->agent_id] = $obj_kpi_multi_item;
					}
				}
			}

			//代理直属玩家数
			$all_user_sql = "select count(*),agent_id from `user_game` where update_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理新增直属玩家数
			$new_user_sql = "select count(*),agent_id from `user_game` WHERE update_time >= $id_time and update_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理直属活跃用户数
			$active_user_sql = "select count(*), agent_id from `user_game` WHERE last_game_time >= $id_time and last_game_time < $id_time_now GROUP BY agent_id HAVING agent_id != 0";
			//代理每小时直属活跃用户
			$hour_user_sql = "select count(*), agent_id from `user_game` WHERE last_game_time >= $id_time_hour and last_game_time < $id_time_hour_now GROUP BY agent_id HAVING agent_id != 0";
			//代理直属用户充值记录
			$recharge_direct_sql = "select sum(money),aid from `user_log` where time >= $id_time and time < $id_time_now and type = 4 GROUP BY aid HAVING aid != 0";
			//代理直属用户耗钻记录
			$currency_direct_sql = "select sum(currency),aid from `user_log` where time >= $id_time and time < $id_time_now and type=1 GROUP BY aid HAVING aid != 0";

			$sql_group = [
				$all_user_sql, 
				$new_user_sql, 
				$active_user_sql, 
				$recharge_direct_sql, 
				$currency_direct_sql, 
				$hour_user_sql
			];

			$sql_name = [
					'all_user',
					'new_user',
					'active_user',
					'recharge_direct',
					'currency_direct',
					'hour_user_sql'
				];

			$result_group = BaseFunction::execute_sql_backend($sql_group);
			if ($result_group && count($result_group) == count($sql_name)) 
			{
				foreach ($result_group as $name_key => $result) 
				{
					$rows = [];
					$row = $result['result']->fetch_all();

					foreach ($row as $key => $value)
					{
						if (!in_array($value[1], $aid_group)) 
						{
							$aid_group[] = $value[1];
						}
						$rows[$value[1]] = $value[0];
					}
					
					$kpi_arr[$sql_name[$name_key]] = $rows;
				}
			}
			else
			{
				BaseFunction::logger($this->log, "【data_request】:\n" . var_export($result_group, true) . "\n" . __LINE__ . "\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
			}

			//获取所有代理信息
			$agentGroup = $this->_get_all_agents();

			//$aid_group 表示所有当天有活动的用户
			foreach ($aid_group as $aid) 
			{
				if (isset($agentGroup['detail'][$aid])) 
				{
					$agent_detail = $agentGroup['detail'][$aid];
					$select_condition_group = ['all_user','new_user','active_user','recharge_direct','recharge_subordinate','currency_direct','currency_subordinate','hour_user_sql','recharge_under_subordinate'];
					foreach ($select_condition_group as $select_condition)
					{
						if (!isset($kpi_arr[$select_condition][$aid]))
						{
							$kpi_arr[$select_condition][$aid] = 0;
						}
						$select_data[$aid][$select_condition] = $kpi_arr[$select_condition][$aid];
					}

					$select_data[$aid]['recharge_direct_shared'] = $agent_detail->shared;
					$select_data[$aid]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
					$select_data[$aid]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
				}
			}

			//处理会长数据
			$type1Group = $agentGroup['aid']['type1'];
			foreach ($type1Group as $aidOfType1=> $agentArrayofType2)
			{
				if (isset($agentGroup['detail'][$aidOfType1])) 
				{
					if (!empty($agentArrayofType2))
					{
						if (!isset($select_data[$aidOfType1]))
						{
							$select_data[$aidOfType1] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}

						foreach ($agentArrayofType2 as $aidOfType2)
						{
							if (isset($select_data[$aidOfType2]))
							{
								$select_data[$aidOfType1]['all_user'] += $select_data[$aidOfType2]['all_user'];
								$select_data[$aidOfType1]['new_user'] += $select_data[$aidOfType2]['new_user'];
								$select_data[$aidOfType1]['active_user'] += $select_data[$aidOfType2]['active_user'];
								$select_data[$aidOfType1]['recharge_subordinate'] += $select_data[$aidOfType2]['recharge_direct'];
								$select_data[$aidOfType1]['currency_subordinate'] += $select_data[$aidOfType2]['currency_direct'];
								$select_data[$aidOfType1]['hour_user_sql'] += $select_data[$aidOfType2]['hour_user_sql'];
							}
						}

						if (array_sum($select_data[$aidOfType1]) == 0) 
						{
							unset($select_data[$aidOfType1]);
						}
					}
					else
					{
						if (!isset($select_data[$aidOfType1]) && !empty($obj_kpi_multi) && isset($obj_kpi_multi[$aidOfType1])) 
						{
							$select_data[$aidOfType1] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}
					}

					$agent_detail = $agentGroup['detail'][$aidOfType1];
					if (isset($select_data[$aidOfType1]))
					{
						$select_data[$aidOfType1]['recharge_direct_shared'] = $agent_detail->shared;
						$select_data[$aidOfType1]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
						$select_data[$aidOfType1]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
					}
				}
			}

			$type8Group = $agentGroup['aid']['type8'];
			$select_data['8600000000000'] = [
				'all_user' => 0,
				'new_user' => 0,
				'active_user' => 0,
				'recharge_direct' => 0,
				'recharge_subordinate' => 0,
				'recharge_under_subordinate' => 0,
				'currency_direct' => 0,
				'currency_subordinate' => 0,
				'hour_user_sql'=>0
			];

			//处理城市合伙人数据
			foreach ($type8Group as $aidOfType8=> $agentArrayofType1)
			{
				if (isset($agentGroup['detail'][$aidOfType8])) 
				{
					if (!empty($agentArrayofType1))
					{
						if (!isset($select_data[$aidOfType8]))
						{
							$select_data[$aidOfType8] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}

						foreach ($agentArrayofType1 as $aidOfType1)
						{
							if (isset($select_data[$aidOfType1]))
							{
								$select_data[$aidOfType8]['all_user'] += $select_data[$aidOfType1]['all_user'];
								$select_data[$aidOfType8]['new_user'] += $select_data[$aidOfType1]['new_user'];
								$select_data[$aidOfType8]['active_user'] += $select_data[$aidOfType1]['active_user'];
								$select_data[$aidOfType8]['recharge_subordinate'] += $select_data[$aidOfType1]['recharge_direct'];
								$select_data[$aidOfType8]['recharge_under_subordinate'] += $select_data[$aidOfType1]['recharge_subordinate'];
								$select_data[$aidOfType8]['currency_subordinate'] += ($select_data[$aidOfType1]['currency_direct'] + $select_data[$aidOfType1]['currency_subordinate']);
								$select_data[$aidOfType8]['hour_user_sql'] += $select_data[$aidOfType1]['hour_user_sql'];
							}
						}

						if (array_sum($select_data[$aidOfType8]) == 0) 
						{
							unset($select_data[$aidOfType8]);
						}
					}
					else
					{
						if (!isset($select_data[$aidOfType8]) && !empty($obj_kpi_multi) && isset($obj_kpi_multi[$aidOfType8])) 
						{
							$select_data[$aidOfType8] = [
								'all_user' => 0,
								'new_user' => 0,
								'active_user' => 0,
								'recharge_direct' => 0,
								'recharge_subordinate' => 0,
								'recharge_under_subordinate' => 0,
								'currency_direct' => 0,
								'currency_subordinate' => 0,
								'hour_user_sql'=>0,
							];
						}
					}

					$agent_detail = $agentGroup['detail'][$aidOfType8];
					if (isset($select_data[$aidOfType8]))
					{
						$select_data[$aidOfType8]['recharge_direct_shared'] = $agent_detail->shared;
						$select_data[$aidOfType8]['recharge_subordinate_shared'] = $agent_detail->sub_shared;
						$select_data[$aidOfType8]['recharge_under_subordinate_shared'] = $agent_detail->third_shared;
					}

					//总公司数据
					if (isset($select_data[$aidOfType8]))
					{
						$select_data['8600000000000']['all_user'] += $select_data[$aidOfType8]['all_user'];
						$select_data['8600000000000']['new_user'] += $select_data[$aidOfType8]['new_user'];
						$select_data['8600000000000']['active_user'] += $select_data[$aidOfType8]['active_user'];
						$select_data['8600000000000']['recharge_subordinate'] += ($select_data[$aidOfType8]['recharge_direct'] + $select_data[$aidOfType8]['recharge_subordinate']+ $select_data[$aidOfType8]['recharge_under_subordinate']);
						$select_data['8600000000000']['currency_subordinate'] += ($select_data[$aidOfType8]['currency_direct'] + $select_data[$aidOfType8]['currency_subordinate']);
						$select_data['8600000000000']['hour_user_sql'] += $select_data[$aidOfType8]['hour_user_sql'];
						$select_data['8600000000000']['recharge_direct_shared'] = 1;
						$select_data['8600000000000']['recharge_subordinate_shared'] = 1;
						$select_data['8600000000000']['recharge_under_subordinate_shared'] = 1;
					}
				}
			}

			foreach ($select_data as $aid => $agentInfo) 
			{
				if (!empty($obj_kpi_multi) && isset($obj_kpi_multi[$aid])) 
				{
					$obj_kpi = $obj_kpi_multi[$aid];
					$obj_kpi->all_user = $select_data[$aid]['all_user'];
					$obj_kpi->new_user = $select_data[$aid]['new_user'];
					$obj_kpi->active_user = $select_data[$aid]['active_user'];
					$obj_kpi->recharge_direct = $select_data[$aid]['recharge_direct'];
					$obj_kpi->recharge_subordinate = $select_data[$aid]['recharge_subordinate'];
					$obj_kpi->currency_direct = $select_data[$aid]['currency_direct'];
					$obj_kpi->currency_subordinate = $select_data[$aid]['currency_subordinate'];
					$obj_kpi->agent_id = $aid;
					$obj_kpi->recharge_direct_shared = $select_data[$aid]['recharge_direct_shared'];
					$obj_kpi->recharge_subordinate_shared = $select_data[$aid]['recharge_subordinate_shared'];
					$obj_kpi->recharge_under_subordinate = $select_data[$aid]['recharge_under_subordinate'];
					$obj_kpi->recharge_under_subordinate_shared = $select_data[$aid]['recharge_under_subordinate_shared'];
					$tmp_hour_arr = json_decode($obj_kpi->hour_user, true);
					if(!is_array($tmp_hour_arr))
					{
						$tmp_hour_arr = array();
					}

					$tmp_hour_arr[$hour_name] = $select_data[$aid]['hour_user_sql'];
					$obj_kpi->hour_user = json_encode($tmp_hour_arr);

					$obj_kpi->game_num = 0;
					$obj_kpi->play_time = 0;

					$rawsqls[] = $obj_kpi->getUpdateSql();
				}
				else
				{
					$obj_kpi = new KpiNew();
					$obj_kpi->id_time = $id_time;
					$obj_kpi->agent_id = $aid;
					$obj_kpi->all_user = $agentInfo['all_user'];

					$obj_kpi->new_user = $agentInfo['new_user'];
					$obj_kpi->active_user = $agentInfo['active_user'];
					$obj_kpi->recharge_direct = $agentInfo['recharge_direct'];
					$obj_kpi->currency_direct = $agentInfo['currency_direct'];
					$obj_kpi->recharge_subordinate = $agentInfo['recharge_subordinate'];
					$obj_kpi->currency_subordinate = $agentInfo['currency_subordinate'];
					$obj_kpi->recharge_direct_shared = $select_data[$aid]['recharge_direct_shared'];
					$obj_kpi->recharge_subordinate_shared = $select_data[$aid]['recharge_subordinate_shared'];
					$obj_kpi->recharge_under_subordinate = $select_data[$aid]['recharge_under_subordinate'];
					$obj_kpi->recharge_under_subordinate_shared = $select_data[$aid]['recharge_under_subordinate_shared'];
					
					$tmp_hour_arr = json_decode($obj_kpi->hour_user, true);
					if(!is_array($tmp_hour_arr))
					{
						$tmp_hour_arr = array();
					}
					$tmp_hour_arr[$hour_name] = $agentInfo['hour_user_sql'];

					$obj_kpi->hour_user = json_encode($tmp_hour_arr);
					$obj_kpi->game_num = 0;
					$obj_kpi->play_time = 0;

					$rawsqls[] = $obj_kpi->getInsertSql();
				}
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【data_request】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __LINE__; break;
			}

			if(isset($obj_kpi_multi_factory))
			{
				$obj_kpi_multi_factory->writeback();
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}

	private function _get_all_agents()
    {
        //远程获取代理id
        $data_request = array(
            'mod' => 'Business',
            'act' => 'get_all_agents',
            'platform' => 'tocar',
        );

        $randkey = BaseFunction::encryptMD5($data_request);
        $url = Config::FAIR_AGENT_PATH. "?randkey=" . $randkey . "&c_version=0.0.1";
        $result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));

        if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0)) {
            BaseFunction::logger($this->log, "【data_request】:\n" . var_export($data_request, true) . "\n" . __LINE__ . "\n");
            return false;
        }

        $obj_agentinfo_multi = $result->data->list;
        $agentArrayOfType8 = [];
        $agentArrayOfType1 = [];
        foreach ($obj_agentinfo_multi as $obj_agentinfo_multi_item)
        {
			if ($obj_agentinfo_multi_item->type == 8)
            {
                if (!isset($agentArrayOfType8[$obj_agentinfo_multi_item->aid]))
                {
                    $agentArrayOfType8[$obj_agentinfo_multi_item->aid] = [];
                }
            }
            if ($obj_agentinfo_multi_item->type == 1)
            {
                if (!isset($agentArrayOfType8[$obj_agentinfo_multi_item->p_num]) && $obj_agentinfo_multi_item->p_num != 0)
                {
                    $agentArrayOfType8[$obj_agentinfo_multi_item->p_num] = [];
                }

                $agentArrayOfType8[$obj_agentinfo_multi_item->p_num][] = $obj_agentinfo_multi_item->aid;
            }
            if ($obj_agentinfo_multi_item->type == 2)
            {
                if (!isset($agentArrayOfType1[$obj_agentinfo_multi_item->p_aid]) && $obj_agentinfo_multi_item->p_aid != 0)
                {
                    $agentArrayOfType1[$obj_agentinfo_multi_item->p_aid] = [];
                }
                $agentArrayOfType1[$obj_agentinfo_multi_item->p_aid][]= $obj_agentinfo_multi_item->aid;
            }

            $agent_arr['detail'][$obj_agentinfo_multi_item->aid] = $obj_agentinfo_multi_item;
        }
        $agent_arr['aid'] = ['type8'=>$agentArrayOfType8,'type1'=>$agentArrayOfType1];

        return $agent_arr;
    }

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

	//获取某个代理的玩家数
	public function getUserBelongsAnAgent($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$data = array('amount'=>0);

		do {
			$this->_init_cache();

			$obj_user_game_list_factory = new UserGameListFactory($this->cache_handler, null, '', null, null, $params['agent_id']);
			if($obj_user_game_list_factory->initialize() && $obj_user_game_list_factory->get())
			{	
				$obj_user_game_list = $obj_user_game_list_factory->get();
				$amount = count($obj_user_game_list);
				$data['amount'] = $amount;
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}
}
