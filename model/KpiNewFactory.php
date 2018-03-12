<?php
namespace bigcat\model;

use bigcat\inc\Factory;
use bigcat\inc\BaseFunction;
class KpiNewFactory extends Factory
{
    const objkey = 'game_txc_kpi_new_multi_';
    private $sql;
    public function __construct($dbobj, $id) 
    {
        $serverkey = self::objkey;
        $objkey = self::objkey."_".$id;
        $this->sql = "select
            `id`
            , `id_time`
            , `all_user`
            , `new_user`
            , `active_user`

            , `game_num`
            , `hour_user`
            , `recharge_direct`
            , `recharge_subordinate`
            , `currency_direct`

            , `currency_subordinate`
            , `play_time`
            , `agent_id`
            , `pay_status`
            , `recharge_direct_shared`

            , `recharge_subordinate_shared`
            , `recharge_under_subordinate`
            , `recharge_under_subordinate_shared`

            from `kpi_new`
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
            $obj = new KpiNew;

            $obj->id = intval($row[0]);
            $obj->id_time = intval($row[1]);
            $obj->all_user = intval($row[2]);
            $obj->new_user = intval($row[3]);
            $obj->active_user = intval($row[4]);

            $obj->game_num = intval($row[5]);
            $obj->hour_user = ($row[6]);
            $obj->recharge_direct = ($row[7]);
            $obj->recharge_subordinate = ($row[8]);
            $obj->currency_direct = intval($row[9]);

            $obj->currency_subordinate = intval($row[10]);
            $obj->play_time = intval($row[11]);
            $obj->agent_id = intval($row[12]);
            $obj->pay_status = intval($row[13]);
            $obj->recharge_direct_shared = ($row[14]);

            $obj->recharge_subordinate_shared = ($row[15]);
            $obj->recharge_under_subordinate = ($row[16]);
            $obj->recharge_under_subordinate_shared = ($row[17]);

            $obj->before_writeback();
            break;
        }
        $records->free();
        unset($records);
        return $obj;
    }
}

