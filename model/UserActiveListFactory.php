<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class UserActiveListFactory extends ListFactory
{
    public $key = 'game_txc_user_active_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='') 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid) 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `uid` from `user_active` where uid=".intval($uid)."";
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

