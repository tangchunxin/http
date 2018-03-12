<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class RoomMultiFactory extends MutiStoreFactory
{
    public $key = 'gfplay_mahjong_room_multi_';
    private $sql;

    public function __construct($dbobj, $key_objfactory=null, $rid=null, $key_add='') 
    {
        if( !$key_objfactory && !$rid )
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
            `rid`
            , `state`
            ";

        if( $rid != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from room where `rid`=".intval($rid)."";
        }
        else
        {
            $this->sql = "select $fields from room ";
            if($ids)
            {
                $this->sql = $this->sql." where `rid` in ($ids) ";
            }
        }
        parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $rid);
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
            $obj = new Room;

            $obj->rid = intval($row[0]);
            $obj->state = intval($row[1]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->rid] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


