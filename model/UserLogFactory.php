<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class UserLogFactory extends Factory
{
    const objkey = 'game_txc_user_log_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `uid`
            , `old_currency`
            , `currency`
            , `type`

            , `time`
            , `money`
            , `aid`

            from `user_log`
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
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

