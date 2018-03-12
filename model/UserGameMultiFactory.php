<?php
namespace bigcat\model;

use bigcat\inc\MutiStoreFactory;
use bigcat\inc\BaseFunction;
class UserGameMultiFactory extends MutiStoreFactory
{
    public $key = 'gfplay_mahjong_user_game_multi_';
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
            , `currency`
            , `room`
            , `is_room_owner`
            , `update_time`

            , `last_game_time`
            , `currency2`
            , `agent_id`
            , `sum_money`
            , `sum_currency`

            , `status`
            , `bind_time`
            , `score`
            , `cup`
            , `reward_state`

            , `inviter`
            , `vip_type`
            , `vip_overtime`
            ";

        if( $uid != null )
        {
            $this->bInitMuti = false;
            $this->sql = "select $fields from user_game where `uid`=".intval($uid)."";
        }
        else
        {
            $this->sql = "select $fields from user_game ";
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
            $obj = new UserGame;

            $obj->uid = intval($row[0]);
            $obj->currency = intval($row[1]);
            $obj->room = intval($row[2]);
            $obj->is_room_owner = intval($row[3]);
            $obj->update_time = intval($row[4]);

            $obj->last_game_time = intval($row[5]);
            $obj->currency2 = intval($row[6]);
            $obj->agent_id = intval($row[7]);
            $obj->sum_money = intval($row[8]);
            $obj->sum_currency = intval($row[9]);

            $obj->status = intval($row[10]);
            $obj->bind_time = intval($row[11]);
            $obj->score = intval($row[12]);
            $obj->cup = intval($row[13]);
            $obj->reward_state = ($row[14]);

            $obj->inviter = intval($row[15]);
            $obj->vip_type = intval($row[16]);
            $obj->vip_overtime = intval($row[17]);

            $obj->before_writeback();
            $objs[$this->objkey.'_'.$obj->uid] = $obj;
        }
        $records->free();
        unset($records);
        return $objs;
    }
}


