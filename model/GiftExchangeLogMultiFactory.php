<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class GiftExchangeLogMultiFactory extends MutiStoreFactory
{
    public $key = 'gfplay_mahjong_gift_exchange_log_multi_';
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
            , `name`
            , `picture`
            , `uid`
            , `time`

            , `receiver_name`
            , `receiver_cellphone`
            , `receiver_address`
            , `remark`
            , `state`

            , `update_time`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from gift_exchange_log where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from gift_exchange_log ";
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
            $obj = new GiftExchangeLog;

            $obj->id = intval($row[0]);
            $obj->name = ($row[1]);
            $obj->picture = ($row[2]);
            $obj->uid = intval($row[3]);
            $obj->time = intval($row[4]);

            $obj->receiver_name = ($row[5]);
            $obj->receiver_cellphone = ($row[6]);
            $obj->receiver_address = ($row[7]);
            $obj->remark = ($row[8]);
            $obj->state = intval($row[9]);

            $obj->update_time = intval($row[10]);

            $obj->before_writeback();
            $objs[$this->key.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


