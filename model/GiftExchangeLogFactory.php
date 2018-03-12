<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class GiftExchangeLogFactory extends Factory
{
    const objkey = 'gfplay_mahjong_gift_exchange_log_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `name`
            , `picture`
            , `uid`
            , `time`

            , `receiver_name`
            , `receiver_cellphone`
            , `receiver_address`
            , `remark`
            , `state`

            , `update_time`

            from `gift_exchange_log`
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
            $obj = new GiftExchangeLog;

            $obj->id = intval($row[0]);
            $obj->name = ($row[1]);
            $obj->picture = ($row[2]);
            $obj->uid = intval($row[3]);
            $obj->time = intval($row[4]);

            $obj->receiver_name = ($row[5]);
            $obj->receiver_cellphone = ($row[6]);
            $obj->receiver_address = ($row[7]);
            $obj->remark = ($row[8]);
            $obj->state = intval($row[9]);

            $obj->update_time = intval($row[10]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

