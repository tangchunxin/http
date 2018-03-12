<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class KpiNew extends BaseObject
{
    const TABLE_NAME = 'kpi_new';

    public $id;	//主键自增ID
    public $id_time = 0;	//日期时间，每天零点，每天一条
    public $all_user = 0;	//总用户数
    public $new_user = 0;	//新注册用户
    public $active_user = 0;	//活跃用户

    public $game_num = 0;	//每天局数
    public $hour_user = '';	//按时段统计用户在线量
    public $recharge_direct = 0.0;	//直属用户当日充值总额
    public $recharge_subordinate = 0.0;	//下属用户当日充值金额
    public $currency_direct = 0;	//每天消费

    public $currency_subordinate = 0;	//
    public $play_time = 0;	//
    public $agent_id = 0;	//推广员id
    public $pay_status = 0;	//提现状态 0 未提现 1已提现
    public $recharge_direct_shared = 0.0;	//直属用户充值分成占比

    public $recharge_subordinate_shared = 0.0;	//下属用户充值分成占比
    public $recharge_under_subordinate = 0.0;	//下下级用户充值
    public $recharge_under_subordinate_shared = 0.0;	//下下级用户充值分成占比

    public function getUpdateSql() 
    {
        return "update `kpi_new` SET
            `id_time`=".intval($this->id_time)."
            , `all_user`=".intval($this->all_user)."
            , `new_user`=".intval($this->new_user)."
            , `active_user`=".intval($this->active_user)."

            , `game_num`=".intval($this->game_num)."
            , `hour_user`='".BaseFunction::my_addslashes($this->hour_user)."'
            , `recharge_direct`='".($this->recharge_direct)."'
            , `recharge_subordinate`='".($this->recharge_subordinate)."'
            , `currency_direct`=".intval($this->currency_direct)."

            , `currency_subordinate`=".intval($this->currency_subordinate)."
            , `play_time`=".intval($this->play_time)."
            , `agent_id`=".intval($this->agent_id)."
            , `pay_status`=".intval($this->pay_status)."
            , `recharge_direct_shared`='".($this->recharge_direct_shared)."'

            , `recharge_subordinate_shared`='".($this->recharge_subordinate_shared)."'
            , `recharge_under_subordinate`='".($this->recharge_under_subordinate)."'
            , `recharge_under_subordinate_shared`='".($this->recharge_under_subordinate_shared)."'

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `kpi_new` SET

            `id_time`=".intval($this->id_time)."
            , `all_user`=".intval($this->all_user)."
            , `new_user`=".intval($this->new_user)."
            , `active_user`=".intval($this->active_user)."

            , `game_num`=".intval($this->game_num)."
            , `hour_user`='".BaseFunction::my_addslashes($this->hour_user)."'
            , `recharge_direct`='".($this->recharge_direct)."'
            , `recharge_subordinate`='".($this->recharge_subordinate)."'
            , `currency_direct`=".intval($this->currency_direct)."

            , `currency_subordinate`=".intval($this->currency_subordinate)."
            , `play_time`=".intval($this->play_time)."
            , `agent_id`=".intval($this->agent_id)."
            , `pay_status`=".intval($this->pay_status)."
            , `recharge_direct_shared`='".($this->recharge_direct_shared)."'

            , `recharge_subordinate_shared`='".($this->recharge_subordinate_shared)."'
            , `recharge_under_subordinate`='".($this->recharge_under_subordinate)."'
            , `recharge_under_subordinate_shared`='".($this->recharge_under_subordinate_shared)."'
            ";
    }

    public function getDelSql() 
    {
        return "delete from `kpi_new`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

