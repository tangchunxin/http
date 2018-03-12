<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class RoomFactory extends Factory
{
    const objkey = 'gfplay_mahjong_room_multi_';
    private $sql;
    public function __construct($dbobj, $rid) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$rid;
        $this->sql = "select
            `rid`
            , `state`

            from `room`
            where `rid`=".intval($rid)."";

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
            $obj = new Room;

            $obj->rid = intval($row[0]);
            $obj->state = intval($row[1]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

