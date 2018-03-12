<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class RechargeableCardMultiFactory extends MutiStoreFactory
{
    public $key = 'gfplay_mahjong_rechargeable_card_multi_';
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
            , `password`
            , `gid`
            , `state`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from rechargeable_card where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from rechargeable_card ";
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
            $obj = new RechargeableCard;

            $obj->id = intval($row[0]);
            $obj->password = ($row[1]);
            $obj->gid = intval($row[2]);
            $obj->state = intval($row[3]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


