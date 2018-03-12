<?php
namespace bigcat\model;

use bigcat\inc\ListFactory;
class KpiNewListFactory extends ListFactory
{
    public $key = 'kpi_new_list_';
    public function __construct($dbobj, $id = null, $id_multi_str='',$agent_id=null,$agent_ids=null,$id_time = null, $sql = null)
    {
        //$id_multi_str 是用,分隔的字符串
        if($sql)
        {
            $this->key = $this->key.md5($sql);
            $this->sql = $sql;
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($id)
        {
            $this->key = $this->key.$id;
            $this->sql = "select `id` from `kpi_new` where id=".intval($id)."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($agent_id)
        {
            $this->key = $this->key.$agent_id;
            $this->sql = "select `id` from `kpi_new` where agent_id=".intval($agent_id)."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($agent_ids && $id_time == null)
        {
            $this->key = $this->key.$agent_ids;
            $this->sql = "select `id` from `kpi_new` where agent_id IN ".$agent_ids."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($id_time && $agent_ids == null)
        {
            $this->key = $this->key.$id_time;
            $this->sql = "select `id` from `kpi_new` where id_time = ".$id_time."";
            parent::__construct($dbobj, $this->key);
            return true;
        }
        elseif($id_time && $agent_ids)
        {
            $this->key = $this->key.$id_time;
            $this->sql = "select `id` from `kpi_new` where id_time = ".$id_time." and agent_id in ".$agent_ids;
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

