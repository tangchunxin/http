<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class RewardMessage extends BaseObject
{
    const TABLE_NAME = 'reward_message';

    public $id;	//主键
    public $uid = 0;	//玩家id(邀请人)
    public $type = 0;	//奖励类型
    public $state = 0;	//领取状态(0未领取;1,已领取可删除)
    public $invitee = 0;	//被邀请人

    public $invitee_name = '';	//被邀请人姓名
    public $currency = 0;	//奖励钻石数
    public $create_time = 0;	//创建时间
    public $update_time = 0;	//更新时间

    public function getUpdateSql() 
    {
        return "update `reward_message` SET
            `uid`=".intval($this->uid)."
            , `type`=".intval($this->type)."
            , `state`=".intval($this->state)."
            , `invitee`=".intval($this->invitee)."

            , `invitee_name`='".BaseFunction::my_addslashes($this->invitee_name)."'
            , `currency`=".intval($this->currency)."
            , `create_time`=".intval($this->create_time)."
            , `update_time`=".intval($this->update_time)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `reward_message` SET

            `uid`=".intval($this->uid)."
            , `type`=".intval($this->type)."
            , `state`=".intval($this->state)."
            , `invitee`=".intval($this->invitee)."

            , `invitee_name`='".BaseFunction::my_addslashes($this->invitee_name)."'
            , `currency`=".intval($this->currency)."
            , `create_time`=".intval($this->create_time)."
            , `update_time`=".intval($this->update_time)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `reward_message`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

