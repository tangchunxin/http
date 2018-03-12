<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class RewardMessageListFactory extends ListFactory
{
    public $key = 'game_txc_reward_message_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='', $state = null) 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid && $state == null) 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `id` from `reward_message` where uid=".intval($uid);
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($state && $uid) 
        {
            $this->key = $this->key.$state.$uid;
            $this->sql = "select `id` from `reward_message` where uid=".intval($uid)." and state != ".$state;
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif ($id_multi_str) 
        {
            $this->key = $this->key.md5($id_multi_str);
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        return false;
    }
}

