<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class RechargeableCardListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_rechargeable_card_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='' , $gid = null, $state = null, $limit = null) 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid) 
        {
            $this->key = $this->key.$uid;
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif ($gid == null && $state == null && $uid == null && $id_multi_str == '' && $limit == null) 
        {
            $this->key = $this->key.md5('all');
            $this->sql = "select `id` from `rechargeable_card` order by `id` desc";
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        elseif ($gid && $limit == null && $state == null && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5($gid);
            $this->sql = "select `id` from `rechargeable_card` where gid =".$gid." order by `id` desc";
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        elseif ($state && $gid == null && $limit == null && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5($state);
            $this->sql = "select `id` from `rechargeable_card` where state = ".$state." order by `id` desc";
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        elseif ($state && $gid && $limit && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5($state.$gid.$limit);
            $this->sql = "select `id` from `rechargeable_card` where gid =".$gid." and state = ".$state." order by `id` desc limit ".$limit;
            parent::__construct($dbobj, $this->key, null, $id_multi_str);
            return true;
        }
        elseif ($gid && $state && $limit == null && $uid == null && $id_multi_str == '') 
        {
            $this->key = $this->key.md5($gid.$state);
            $this->sql = "select `id` from `rechargeable_card` where gid =".$gid." and state = ".$state." order by `id` desc";
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

