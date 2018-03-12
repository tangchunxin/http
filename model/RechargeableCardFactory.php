<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class RechargeableCardFactory extends Factory
{
    const objkey = 'gfplay_mahjong_rechargeable_card_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `password`
            , `gid`
            , `state`

            from `rechargeable_card`
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
            $obj = new RechargeableCard;

            $obj->id = intval($row[0]);
            $obj->password = ($row[1]);
            $obj->gid = intval($row[2]);
            $obj->state = intval($row[3]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

