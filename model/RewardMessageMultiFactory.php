<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class RewardMessageMultiFactory extends MutiStoreFactory
{
    public $key = 'game_txc_reward_message_multi_';
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
            , `type`
            , `state`
            , `invitee`

            , `invitee_name`
            , `currency`
            , `create_time`
            , `update_time`
            ";

        if( $id != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from reward_message where `id`=".intval($id)."";
        }
        else
        {
            $this->sql = "select $fields from reward_message ";
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
            $obj = new RewardMessage;

            $obj->id = intval($row[0]);
            $obj->uid = intval($row[1]);
            $obj->type = intval($row[2]);
            $obj->state = intval($row[3]);
            $obj->invitee = intval($row[4]);

            $obj->invitee_name = ($row[5]);
            $obj->currency = intval($row[6]);
            $obj->create_time = intval($row[7]);
            $obj->update_time = intval($row[8]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->id] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


