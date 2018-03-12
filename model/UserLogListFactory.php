<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class UserLogListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_user_log_list_';
    public function __construct($dbobj, $uid = null, $type = null, $time = null, $id_multi_str='', $sql = null , $key_str =null)
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid) 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `id` from `user_log` where uid=".intval($uid)."";
            if($type !== null)
            {
                $this->sql .= " and type=".intval($type)."";
            }
            if($time !== null)
            {
                $this->sql .= " and time>".intval($time)."";
            }
            parent::__construct($dbobj, $this->key);
            return true;
        }
        else if($sql != null)
        {
            $this->key = $this->key.$key_str;
            $this->sql = $sql;
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

