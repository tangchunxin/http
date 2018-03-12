<?php  

namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;
use bigcat\inc\Factory;
use bigcat\conf\CatConstant;

class WXTokenFactory extends Factory 
{
	const objkey = 'wx_token_key_';
	private $wx_appid;
	private $wx_appsecret;
	public function __construct($dbobj,$appid, $appsecret)
	{
		$time_key = date('YmdH', time()); 
		$objkey = self::objkey.$appid.$time_key;
		$this->wx_appid = $appid;
		$this->wx_appsecret = $appsecret;

		parent::__construct($dbobj, $objkey, $objkey, 3600);
		return true;
	}

	public function retrive()
	{
		$wx_result = json_decode(BaseFunction::https_request("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->wx_appid."&secret=".$this->wx_appsecret.""));

		if( !$wx_result || !isset($wx_result->access_token) ) {
			return null;
		}

		$obj = new WXToken();
		$obj->access_token = $wx_result->access_token;

		return $obj;
	}
}