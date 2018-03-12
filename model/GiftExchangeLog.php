<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class GiftExchangeLog extends BaseObject
{
    const TABLE_NAME = 'gift_exchange_log';

    public $id;	//自增主键ID
    public $name = '';	//礼物名称
    public $picture = '';	//礼物图片
    public $uid = 0;	//兑换人
    public $time = 0;	//兑换时间

    public $receiver_name = '';	//收货人姓名
    public $receiver_cellphone = '';	//收件人手机号
    public $receiver_address = '';	//收件人地址
    public $remark = '';	//备注
    public $state = 0;	//发货状态(1,待处理,2,处理中,3已完成)

    public $update_time = 0;	//更改时间

    public function getUpdateSql() 
    {
        return "update `gift_exchange_log` SET
            `name`='".BaseFunction::my_addslashes($this->name)."'
            , `picture`='".BaseFunction::my_addslashes($this->picture)."'
            , `uid`=".intval($this->uid)."
            , `time`=".intval($this->time)."

            , `receiver_name`='".BaseFunction::my_addslashes($this->receiver_name)."'
            , `receiver_cellphone`='".BaseFunction::my_addslashes($this->receiver_cellphone)."'
            , `receiver_address`='".BaseFunction::my_addslashes($this->receiver_address)."'
            , `remark`='".BaseFunction::my_addslashes($this->remark)."'
            , `state`=".intval($this->state)."

            , `update_time`=".intval($this->update_time)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `gift_exchange_log` SET

            `name`='".BaseFunction::my_addslashes($this->name)."'
            , `picture`='".BaseFunction::my_addslashes($this->picture)."'
            , `uid`=".intval($this->uid)."
            , `time`=".intval($this->time)."

            , `receiver_name`='".BaseFunction::my_addslashes($this->receiver_name)."'
            , `receiver_cellphone`='".BaseFunction::my_addslashes($this->receiver_cellphone)."'
            , `receiver_address`='".BaseFunction::my_addslashes($this->receiver_address)."'
            , `remark`='".BaseFunction::my_addslashes($this->remark)."'
            , `state`=".intval($this->state)."

            , `update_time`=".intval($this->update_time)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `gift_exchange_log`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

