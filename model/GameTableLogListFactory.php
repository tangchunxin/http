<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class GameTableLogListFactory extends ListFactory
{
    public $key = 'game_table_log_game_table_log_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='') 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid) 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `id` from `game_table_log` where uid=".intval($uid)." order by id desc";
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

