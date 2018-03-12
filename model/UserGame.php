<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;

class UserGame extends BaseObject
{
    const TABLE_NAME = 'user_game';

    public $uid;	//用户id
    public $currency = 0;	//游戏币
    public $room = 0;	//未结束的游戏房间号
    public $is_room_owner = 0;	//是否房主 0 否 1 是
    public $update_time = 0;	//更新时间

    public $last_game_time = 0;	//最后一次玩游戏的时间
    public $currency2 = 0;	//
    public $agent_id = 0;	//推广员id
    public $sum_money = 0;	//总计充值金额
    public $sum_currency = 0;	//总计消耗房卡

    public $status = 0;	//0 正常  1黑名单
    public $bind_time = 0;	//绑定工会时间
    public $score = 0;	//积分
    public $cup = 0;	//奖杯
    public $reward_state = '';	//满足的奖励状况

    public $inviter = 0;	//邀请人uid
    public $vip_type = 0;	//1月卡 2季卡 3半年卡 4年卡
    public $vip_overtime = 0;	//到期时间

    public function getUpdateSql() 
    {
        // BaseFunction::logger(CatConstant::LOG_FILE, "set_log:\n".var_export(debug_backtrace(), true)."\n".__LINE__."\n");

        return "update `user_game` SET
            `currency`=".intval($this->currency)."
            , `room`=".intval($this->room)."
            , `is_room_owner`=".intval($this->is_room_owner)."
            , `update_time`=".intval($this->update_time)."

            , `last_game_time`=".intval($this->last_game_time)."
            , `currency2`=".intval($this->currency2)."
            , `agent_id`=".intval($this->agent_id)."
            , `sum_money`=".intval($this->sum_money)."
            , `sum_currency`=".intval($this->sum_currency)."

            , `status`=".intval($this->status)."
            , `bind_time`=".intval($this->bind_time)."
            , `score`=".intval($this->score)."
            , `cup`=".intval($this->cup)."
            , `reward_state`='".BaseFunction::my_addslashes($this->reward_state)."'

            , `inviter`=".intval($this->inviter)."
            , `vip_type`=".intval($this->vip_type)."
            , `vip_overtime`=".intval($this->vip_overtime)."

            where `uid`=".intval($this->uid)."";
    }

    public function getInsertSql() 
    {
        return "insert into `user_game` SET

            `uid`=".intval($this->uid)."
            , `currency`=".intval($this->currency)."
            , `room`=".intval($this->room)."
            , `is_room_owner`=".intval($this->is_room_owner)."
            , `update_time`=".intval($this->update_time)."

            , `last_game_time`=".intval($this->last_game_time)."
            , `currency2`=".intval($this->currency2)."
            , `agent_id`=".intval($this->agent_id)."
            , `sum_money`=".intval($this->sum_money)."
            , `sum_currency`=".intval($this->sum_currency)."

            , `status`=".intval($this->status)."
            , `bind_time`=".intval($this->bind_time)."
            , `score`=".intval($this->score)."
            , `cup`=".intval($this->cup)."
            , `reward_state`='".BaseFunction::my_addslashes($this->reward_state)."'
            
            , `inviter`=".intval($this->inviter)."
            , `vip_type`=".intval($this->vip_type)."
            , `vip_overtime`=".intval($this->vip_overtime)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `user_game`
            where `uid`=".intval($this->uid)."";
    }
    
    public function getSelectSql()
    {
        return "select
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

            from `user_game`
            where `uid`=".intval($this->uid)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

