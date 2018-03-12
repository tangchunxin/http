<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class UserActiveFactory extends Factory
{
    const objkey = 'game_txc_user_active_multi_';
    private $sql;
    public function __construct($dbobj, $uid) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$uid;
        $this->sql = "select
            `uid`
            , `subscribe_reward`
            , `create_time`
            , `update_time`

            from `user_active`
            where `uid`=".intval($uid)."";

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
            $obj = new UserActive;

            $obj->uid = intval($row[0]);
            $obj->subscribe_reward = intval($row[1]);
            $obj->create_time = intval($row[2]);
            $obj->update_time = intval($row[3]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

