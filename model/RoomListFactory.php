<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class RoomListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_room_list_';
    public function __construct($dbobj, $state = null, $id_multi_str='')
    {
        //$id_multi_str 是用,分隔的字符串
        if($state) 
        {
        	$rand = 1000 * mt_rand(0, 999);
            $this->key = $this->key.$state.$rand;
            $this->sql = "select `rid` from `room` where rid>".$rand." and rid<=".($rand+1000)." and state=".intval($state)."";
            parent::__construct($dbobj, $this->key, 600);
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

