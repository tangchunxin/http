<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class GameTableLogMultiFactory extends MutiStoreFactory
{
    public $key = 'game_txc_game_table_log_multi_';
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
            , `rid`
            , `uid`
            , `game_table_info`
            , `time`

            , `state`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from game_table_log where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from game_table_log ";
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
            $obj = new GameTableLog;

            $obj->id = intval($row[0]);
            $obj->rid = intval($row[1]);
            $obj->uid = intval($row[2]);
            $obj->game_table_info = ($row[3]);
            $obj->time = intval($row[4]);

            $obj->state = intval($row[5]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


