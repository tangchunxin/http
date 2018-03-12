<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class RechargeableCard extends BaseObject
{
    const TABLE_NAME = 'rechargeable_card';

    public $id;	//主键
    public $password = '';	//充值卡密码
    public $gid = 0;	//1.联通20元,2.移动20元,3.电信20元 4.联通50元,5.移动50元,6电信50元,7联通100元,8移动100元,9电信100元
    public $state = 0;	//1,可充值,2 不可充值

    public function getUpdateSql() 
    {
        return "update `rechargeable_card` SET
            `password`='".BaseFunction::my_addslashes($this->password)."'
            , `gid`=".intval($this->gid)."
            , `state`=".intval($this->state)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `rechargeable_card` SET

            `password`='".BaseFunction::my_addslashes($this->password)."'
            , `gid`=".intval($this->gid)."
            , `state`=".intval($this->state)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `rechargeable_card`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

