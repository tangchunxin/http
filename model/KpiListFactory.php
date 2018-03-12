<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class KpiListFactory extends ListFactory
{
    public $key = 'gfplay_mahjong_kpi_list_';
    public function __construct($dbobj, $uid = null, $id_multi_str='') 
    {
        //$id_multi_str 是用,分隔的字符串
        if($uid) 
        {
            $this->key = $this->key.$uid;
            $this->sql = "select `id_time` from `kpi` order by id_time desc ";
            parent::__construct($dbobj, $this->key, 60);            
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

