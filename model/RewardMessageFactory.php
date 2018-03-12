<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class RewardMessageFactory extends Factory
{
    const objkey = 'game_txc_reward_message_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `uid`
            , `type`
            , `state`
            , `invitee`

            , `invitee_name`
            , `currency`
            , `create_time`
            , `update_time`

            from `reward_message`
            where `id`=".intval($id)."";

        parent::__construct($dbobj, $serverkey, $objkey);
        return true;
    }

    public function retrive() 
    {
        $records = BaseFunction::query_sql_backend($this->sql);
        if( !$records ) 
        {
            return null;
        }

        $obj = null;
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
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

