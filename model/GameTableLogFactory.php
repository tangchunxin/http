<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class GameTableLogFactory extends Factory
{
    const objkey = 'game_txc_game_table_log_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `rid`
            , `uid`
            , `game_table_info`
            , `time`

            , `state`

            from `game_table_log`
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
            $obj = new GameTableLog;

            $obj->id = intval($row[0]);
            $obj->rid = intval($row[1]);
            $obj->uid = intval($row[2]);
            $obj->game_table_info = ($row[3]);
            $obj->time = intval($row[4]);

            $obj->state = intval($row[5]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

