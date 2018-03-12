<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class UserActive extends BaseObject
{
    const TABLE_NAME = 'user_active';

    public $uid;	//玩家id
    public $subscribe_reward = 0;	//是否领取关注公众号奖励（0，未领取 1已领取）
    public $create_time = 0;	//创建时间
    public $update_time = 0;	//更新时间

    public function getUpdateSql() 
    {
        return "update `user_active` SET
            `subscribe_reward`=".intval($this->subscribe_reward)."
            , `create_time`=".intval($this->create_time)."
            , `update_time`=".intval($this->update_time)."

            where `uid`=".intval($this->uid)."";
    }

    public function getInsertSql() 
    {
        return "insert into `user_active` SET

            `uid`=".intval($this->uid)."
            , `subscribe_reward`=".intval($this->subscribe_reward)."
            , `create_time`=".intval($this->create_time)."
            , `update_time`=".intval($this->update_time)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `user_active`
            where `uid`=".intval($this->uid)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

