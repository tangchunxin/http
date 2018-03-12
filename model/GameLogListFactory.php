<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class GameLogListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_game_log_list_';
    public function __construct($dbobj, $rid = null, $id_multi_str='')
    {
        //$id_multi_str 是用,分隔的字符串
        if($rid)
        {
            $this->key = $this->key.$rid;
            $this->sql = "select `id` from `game_log` where rid=".intval($rid)."";
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

