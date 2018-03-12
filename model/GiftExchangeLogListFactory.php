<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class GiftExchangeLogListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_gift_exchange_log_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='', $state = null) 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid && $state == null && $id_multi_str == '') 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `id` from `gift_exchange_log` where `uid`=".intval($uid)." order by `update_time` desc";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($uid && $state && $id_multi_str == '') 
        {
            $this->key = $this->key.$uid.$state;
            $this->sql = "select `id` from `gift_exchange_log` where `uid`=".intval($uid)." and `state`=".$state." order by `update_time` desc";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif ($state && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5($state);
            $this->sql = "select `id` from `gift_exchange_log` where `state` = ".$state." order by `update_time` desc";
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        elseif ($state == null && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5('all');
            $this->sql = "select `id` from `gift_exchange_log` order by `update_time` desc";
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
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

