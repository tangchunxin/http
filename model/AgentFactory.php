<?php
namespace bigcat\model;

use bigcat\conf\Config;
use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;
class AgentFactory extends Factory
{
    const objkey = 'agent_info_game_';
    private $id;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;

        $this->id = $id;

        parent::__construct($dbobj, $serverkey, $objkey, 3600);
        return true;
    }

    public function retrive() 
    {
        $data_request = array(
        'mod' => 'Business'
        , 'act' => 'agent_info_game'
        , 'platform' => 'gfplay'
        , 'aid' => $this->id
        );
        $randkey = BaseFunction::encryptMD5($data_request);
        $url = Config::FAIR_AGENT_PATH . "?randkey=" . $randkey . "&c_version=0.0.1";
        $result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));

        if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0))
        {
            BaseFunction::logger(CatConstant::LOG_FILE, "【data_request】:\n" . var_export($data_request, true) . "\n" . __LINE__ . "\n");
            BaseFunction::logger(CatConstant::LOG_FILE, "【login_check】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
            return null;
        }

        $obj = new Agent;

        $obj->id = $this->id;
        $obj->agent = $result->data;
        $obj->before_writeback();

        return $obj;
    }
}

