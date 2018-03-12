<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class UserGameListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_user_game_list_';

    public function __construct($dbobj, $uid = null, $id_multi_str='', $sql = null , $key_str =null, $aid = null)
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid)
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `uid` from `user_game` where uid=".intval($uid)."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        else if($sql)
        {
            $this->key = $this->key.$key_str;
            $this->sql = $sql;
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif ($aid && $uid == null && $sql == null && $id_multi_str == '') 
        {
            $this->key = $this->key.$aid;
            $this->sql = "select `uid` from `user_game` where agent_id=".$aid;
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

