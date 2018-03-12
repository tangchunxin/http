<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class UserLog extends BaseObject
{
    const TABLE_NAME = 'user_log';

    public $id;	//
    public $uid = 0;	//用户id
    public $old_currency = 0;	//用户以前的货币值
    public $currency = 0;	//游戏币变化值 有正负
    public $type = 0;	//1 开房间消费 2 代理充值 3分享充值 4微信充值 21红钻消耗(红钻兑换积分) 22 红钻充值 23 后台红钻充值 31游戏积分增减 32 开房积分赠送 33 积分换礼物 34 积分增加(红钻兑换积分) 35后台充值积分 36活动赠送积分 41 游戏赠送奖杯 42 奖杯换礼物 43后台充值奖杯

    public $time = 0;	//记录时间
    public $money = 0.0;	//充值金额
    public $aid = '';	//代理id

    public function getUpdateSql() 
    {
        return "update `user_log` SET
            `uid`=".intval($this->uid)."
            , `old_currency`=".intval($this->old_currency)."
            , `currency`=".intval($this->currency)."
            , `type`=".intval($this->type)."

            , `time`=".intval($this->time)."
            , `money`='".($this->money)."'
            , `aid`='".BaseFunction::my_addslashes($this->aid)."'

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `user_log` SET

            `uid`=".intval($this->uid)."
            , `old_currency`=".intval($this->old_currency)."
            , `currency`=".intval($this->currency)."
            , `type`=".intval($this->type)."

            , `time`=".intval($this->time)."
            , `money`='".($this->money)."'
            , `aid`='".BaseFunction::my_addslashes($this->aid)."'
            ";
    }

    public function getDelSql() 
    {
        return "delete from `user_log`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

