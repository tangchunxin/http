<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

use bigcat\conf\Config;
use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;

class BaseFunction
{
	static $db_instance = null;
	static $tcp_instance = array();

	public static function getTCP($host_port)
	{
		//单例
		if( empty(self::$tcp_instance[$host_port]) )
		{
			self::$tcp_instance[$host_port] = new \swoole_client(SWOOLE_SOCK_TCP);

			self::$tcp_instance[$host_port]->set(array(
			'open_length_check'     => true,
			'package_length_type'   => 'N',
			'package_length_offset' => 0,       //第N个字节是包长度的值
			'package_body_offset'   => 4,       //第几个字节开始计算长度
			'package_max_length'    => 81920,  //协议最大长度
			));
		}
		if(!self::$tcp_instance[$host_port]->isConnected())
		{
			$host_port_arr = explode(':', $host_port);
			if (!self::$tcp_instance[$host_port]->connect($host_port_arr[0], $host_port_arr[1], -1))
			{
				unset(self::$tcp_instance[$host_port]);
				return false;
			}
		}

		return  self::$tcp_instance[$host_port];
	}

	//通过前端授权码token  & openid 获得用户的微信
	public static function code_get_wx_user($access_token, $openid)
	{
		//获取openid
		$wx_user_info = [];
		//$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
		$url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
		$result = self::https_request($url);
		//self::logger("./log/log.log", "【rawsqls】:\n".var_export($result, true)."\n".__LINE__."\n");

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["unionid"]) && isset($jsoninfo["openid"]) && isset($jsoninfo["nickname"]) && isset($jsoninfo["sex"]) && isset($jsoninfo["headimgurl"]) && isset($jsoninfo["city"]) && isset($jsoninfo["province"]))
		{
			$wx_user_info = $jsoninfo;
		}
		
		return $wx_user_info;
	}

	//发短信函数 阿里大鱼
	public static function sms_cz_alidayu($templateCode, $sms_param, $phone, $signname = "功夫游戏")
	{
		$gearmanjson = array
		(
		'template_code'=>$templateCode
		, 'sms_param'=>$sms_param
		, 'phone'=>$phone
		, 'signname'=>$signname
		);

		try
		{
			$client= new \GearmanClient();
			$client->addServer('127.0.0.1', 4730);
			$client->doBackground('sms_cz', json_encode($gearmanjson));
		}catch(Exception $e)
		{
			self::logger('./log/sms.log', "【Exception】:\n" . var_export($e, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		return true;
	}

	public static function time2str($itime)
	{
		if($itime)
		{
			return date('Y-m-d H:i:s', $itime);
		}
		return false;
	}

	public static function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public static function output($response)
	{
		self::closeDB();
		
		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/plain; charset=utf-8");

		if(isset($_REQUEST['callback']) && $_REQUEST['callback'])
		{
			echo $_REQUEST['callback'].'('.json_encode($response).')';
		}
		else
		{
			echo json_encode($response);
		}
	}

	public static function output_html($html)
	{

		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/html; charset=utf-8");

		echo ($html);
	}

	public static function encryptMD5($data)
	{
		$content = '';
		if(!$data || !is_array($data))
		{
			return $content;
		}
		ksort($data);
		foreach ($data as $key => $value)
		{
			$content = $content.$key.$value;
		}
		if(!$content)
		{
			return $content;
		}

		return self::sub_encryptMD5($content);
	}

	public static function sub_encryptMD5($content)
	{
		$content = $content.Config::RPC_KEY;
		$content = md5($content);
		if( strlen($content) > 10 )
		{
			$content = substr($content, 0, 10);
		}
		return $content;
	}

	public static function https_request($url, $data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	public static function logger($file,$word)
	{
		$fp = fopen($file,"a");
		//flock($fp, LOCK_EX) ;
		fwrite($fp,"执行日期：".strftime("%Y-%m-%d %H:%M:%S",time())."\n".$word."\n\n");
		//flock($fp, LOCK_UN);
		fclose($fp);
	}

	public static function get_client_ip()
	{
		$s_client_ip = '';

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$s_client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_X_REAL_IP']))
		{
			$s_client_ip = $_SERVER['HTTP_X_REAL_IP'];
		}
		elseif ($_SERVER['REMOTE_ADDR'])
		{
			$s_client_ip = $_SERVER['REMOTE_ADDR'];
		}
		elseif (getenv('REMOTE_ADDR'))
		{
			$s_client_ip = getenv('REMOTE_ADDR');
		}
		elseif (getenv('HTTP_CLIENT_IP'))
		{
			$s_client_ip = getenv('HTTP_CLIENT_IP');
		}
		else
		{
			$s_client_ip = 'unknown';
		}
		
		return $s_client_ip;
	}


	public static function getDB()
	{
		//单例
		if( empty(self::$db_instance) || !self::$db_instance->ping())
		{
			@mysqli_close(self::$db_instance);
			self::$db_instance = mysqli_init();
			if (!self::$db_instance->real_connect(Config::DB_HOST, Config::DB_USERNAME, Config::DB_PASSWD, Config::DB_DBNAME, Config::DB_PORT))
			{
				return false;
			}
			self::$db_instance->query("set names 'utf8'");
			mb_internal_encoding('utf-8');			
		}
		//self::logger('./log/db.log', "【Exception】:\n" . var_export(self::$db_instance, true) . "\n" . __LINE__ . "\n");
		return  self::$db_instance;
	}
	
	public static function closeDB()
	{
		//单例
		if( !empty(self::$db_instance))
		{
			@mysqli_close(self::$db_instance);
		}
	}

	public static function execute_sql_backend($rawsqls)
	{
		$result_arr = null;
		$is_rollback = false;

		if(!$rawsqls || !is_array($rawsqls))
		{
			return $result_arr;
		}

		$db_connect = self::getDB();
		$db_connect->autocommit(false);
		foreach ($rawsqls as $item_sql)
		{
			$result = null;
			$result = $db_connect->query($item_sql);
			if(!$result)
			{
				if($db_connect->rollback())
				{
					$is_rollback = true;
				}
				else
				{
					$db_connect->rollback();
					$is_rollback = true;
				}
				$result_arr = null;
				break;
			}

			if($db_connect->insert_id)
			{
				$result_arr[] = array('result'=>$result, 'insert_id'=>$db_connect->insert_id);
			}
			else
			{
				$result_arr[] = array('result'=>$result);
			}
		}

		if(!$is_rollback)
		{
			$db_connect->commit();
		}
		$db_connect->autocommit(true);
		return $result_arr;
	}

	public static function query_sql_backend($rawsql)
	{
		$db_connect = self::getDB();

		$result = $db_connect->query($rawsql);

		return $result;
	}


	/*
	* @inout $weights : array(1=>20, 2=>50, 3=>100);
	* @putput array
	*/
	public static function w_rand($weights)
	{

		$r = mt_rand(1, array_sum($weights));

		$offset = 0;
		foreach ( $weights as $k => $w )
		{
			$offset += $w;
			if ($r <= $offset)
			{
				return $k;
			}
		}

		return null;
	}

	public static function my_addslashes($str)
	{
		$str = str_replace(array("\r\n", "\r", "\n"), '', $str);
		return addslashes($str);
	}

	/////////////////////////////////////////////////////

    public static function decryptRandAuth($authKey, $data)
    {
        $content = self::handleDecrypt(base64_decode($data), $authKey);
        return $content;
    }

    public static function encryptRandAuth($authKey, $data)
    {
        $content = base64_encode(self::handleDecrypt($data, $authKey));
        return $content;
    }

    public static function handleDecrypt($data, $key)
    {
        $encrypt_key = substr(md5($key), 6, 8);
        $ctr = 0;
        $content = '';
        $len_key = strlen($encrypt_key);
        $len_data = strlen($data);
        for( $i = 0; $i < $len_data; $i++ )
        {
            $ctr = ($ctr == $len_key) ? 0 : $ctr;
            $content .= $data[$i] ^ $encrypt_key[$ctr++];
        }
        return $content;
    }

    //生成随机串
	public static function create_nonce_str($length = 16)
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		$chars_lenth = strlen($chars) - 1;
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, $chars_lenth), 1);
		}
		return $str;
	}
	
	public static function getMC()
	{
	     //单例
		global $gCache;
        $gCache = array();

		if( !isset($gCache['mcobj']) )
		{
			$mcobj = new CatMemcache(Config::MC_SERVERS);
			$gCache['mcobj'] = $mcobj;
		}

		return  $gCache['mcobj'];
	}

	//通过前端授权码code获得用户的微信
	public static function code_get_wx_user_token($appid, $secret, $code)
	{
		//获取openid
		$wx_user_info = [];
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code";
		$result = self::https_request($url);
		//self::logger("./log/log.log", "【rawsqls】:\n".var_export($result, true)."\n".__LINE__."\n");

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["openid"]) && isset($jsoninfo["access_token"]))
		{
			$wx_user_info = $jsoninfo;
		}
		
		return $wx_user_info;
	}

	//微信公众号获取订阅信息
	public static function get_wx_user_info($access_token, $openid)
	{
		//获取openid
		$wx_user_info = [];
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
		$result = self::https_request($url);

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["unionid"]) && isset($jsoninfo["openid"]) && isset($jsoninfo["nickname"]) && isset($jsoninfo["sex"]) && isset($jsoninfo["headimgurl"]) && isset($jsoninfo["city"]) && isset($jsoninfo["province"]))
		{
			$wx_user_info = $jsoninfo;
		}
		
		return $wx_user_info;
	}

	//获取微信基础access_token
	public static function get_wx_access_token($appid, $secret)
	{
		//获取access_token
		$wx_user_info = [];
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
		$result = self::https_request($url);

		$jsoninfo = json_decode($result, true);
		if(isset($jsoninfo["access_token"]))
		{
			$wx_user_info = $jsoninfo;
		}
		
		return $wx_user_info;
	}
}

