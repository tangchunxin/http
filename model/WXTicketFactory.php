<?php  

namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\Config;
use bigcat\inc\Factory;
use bigcat\model\WXTicket;
use bigcat\conf\CatConstant;


class WXTicketFactory extends Factory 
{
	const objkey = 'wx_js_ticket_key_';
	private $access_token;

	public function __construct($dbobj,$appid, $access_token) 
	{
		$time_key = date('YmdH', time()); 
		$objkey = self::objkey.$appid.$time_key;
		$this->access_token = $access_token;

		parent::__construct($dbobj, $objkey, $objkey, 3600);
		return true;
	}

	public function retrive() 
	{
		$obj = new WXTicket();
		
		//取得ticket
		$wx_result = json_decode(BaseFunction::https_request("https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$this->access_token.""));

		if( !$wx_result || !isset($wx_result->ticket) ) {
			return $obj;
		}
		
		$obj->js_ticket = $wx_result->ticket;	
		return $obj;
	}
}

