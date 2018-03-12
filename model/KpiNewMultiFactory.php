<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class KpiNewMultiFactory extends MutiStoreFactory
{
    public $key = 'game_txc_kpi_new_multi_';
    private $sql;

    public function __construct($dbobj, $key_objfactory=null, $id=null, $key_add='') 
    {
        if( !$key_objfactory && !$id )
        {
            return false;
        }
        $this->key = $this->key.$key_add;
        $ids = '';
        if($key_objfactory) 
        {
            if($key_objfactory->initialize()) 
            {
                $key_obj = $key_objfactory->get();
                $ids = implode(',', $key_obj);
            }
        }
        $fields = "
            `id`
            , `id_time`
            , `all_user`
            , `new_user`
            , `active_user`

            , `game_num`
            , `hour_user`
            , `recharge_direct`
            , `recharge_subordinate`
            , `currency_direct`

            , `currency_subordinate`
            , `play_time`
            , `agent_id`
            , `pay_status`
            , `recharge_direct_shared`

            , `recharge_subordinate_shared`
            , `recharge_under_subordinate`
            , `recharge_under_subordinate_shared`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from kpi_new where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from kpi_new ";
            if($ids)
            {
                $this->sql = $this->sql." where `id` in ($ids) ";
            }
        }
        parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $id);
        return true;
    }

    public function retrive() 
    {
        $records = BaseFunction::query_sql_backend($this->sql);
        if( !$records ) 
        {
            return null;
        }

        $objs = array();
        while ( ($row = $records->fetch_row()) != false ) 
        {
            $obj = new KpiNew;

            $obj->id = intval($row[0]);
            $obj->id_time = intval($row[1]);
            $obj->all_user = intval($row[2]);
            $obj->new_user = intval($row[3]);
            $obj->active_user = intval($row[4]);

            $obj->game_num = intval($row[5]);
            $obj->hour_user = ($row[6]);
            $obj->recharge_direct = ($row[7]);
            $obj->recharge_subordinate = ($row[8]);
            $obj->currency_direct = intval($row[9]);

            $obj->currency_subordinate = intval($row[10]);
            $obj->play_time = intval($row[11]);
            $obj->agent_id = intval($row[12]);
            $obj->pay_status = intval($row[13]);
            $obj->recharge_direct_shared = ($row[14]);

            $obj->recharge_subordinate_shared = ($row[15]);
            $obj->recharge_under_subordinate = ($row[16]);
            $obj->recharge_under_subordinate_shared = ($row[17]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


