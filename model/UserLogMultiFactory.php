<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class UserLogMultiFactory extends MutiStoreFactory
{
    public $key = 'game_txc_user_log_multi_';
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
            , `uid`
            , `old_currency`
            , `currency`
            , `type`

            , `time`
            , `money`
            , `aid`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from user_log where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from user_log ";
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
            $obj = new UserLog;

            $obj->id = intval($row[0]);
            $obj->uid = intval($row[1]);
            $obj->old_currency = intval($row[2]);
            $obj->currency = intval($row[3]);
            $obj->type = intval($row[4]);

            $obj->time = intval($row[5]);
            $obj->money = ($row[6]);
            $obj->aid = ($row[7]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


