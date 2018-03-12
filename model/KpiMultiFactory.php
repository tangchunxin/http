<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class KpiMultiFactory extends MutiStoreFactory
{
    public $key = 'gfplay_mahjong_kpi_multi_';
    private $sql;

    public function __construct($dbobj, $key_objfactory=null, $id_time=null, $key_add='') 
    {
        if( !$key_objfactory && !$id_time )
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
            `id_time`
            , `all_user`
            , `new_user`
            , `active_user`
            , `game_num`

            , `hour_user`
            , `currency`
            , `play_time`
            ";

        if( $id_time != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from kpi where `id_time`=".intval($id_time)."";
        }
        else
        {
            $this->sql = "select $fields from kpi ";
            if($ids)
            {
                $this->sql = $this->sql." where `id_time` in ($ids) ";
            }
        }
        parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $id_time, 900);
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
            $obj = new Kpi;

            $obj->id_time = intval($row[0]);
            $obj->all_user = intval($row[1]);
            $obj->new_user = intval($row[2]);
            $obj->active_user = intval($row[3]);
            $obj->game_num = intval($row[4]);

            $obj->hour_user = ($row[5]);
            $obj->currency = intval($row[6]);
            $obj->play_time = intval($row[7]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id_time] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


