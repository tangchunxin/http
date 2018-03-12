<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class UserActiveMultiFactory extends MutiStoreFactory
{
    public $key = 'game_txc_user_active_multi_';
    private $sql;

    public function __construct($dbobj, $key_objfactory=null, $uid=null, $key_add='') 
    {
        if( !$key_objfactory && !$uid )
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
            `uid`
            , `subscribe_reward`
            , `create_time`
            , `update_time`
            ";

        if( $uid != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from user_active where `uid`=".intval($uid)."";
        }
        else
        {
            $this->sql = "select $fields from user_active ";
            if($ids)
            {
                $this->sql = $this->sql." where `uid` in ($ids) ";
            }
        }
        parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $uid);
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
            $obj = new UserActive;

            $obj->uid = intval($row[0]);
            $obj->subscribe_reward = intval($row[1]);
            $obj->create_time = intval($row[2]);
            $obj->update_time = intval($row[3]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->uid] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


