<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class Room extends BaseObject
{
    const TABLE_NAME = 'room';

    public $rid;	//room id
    public $state = 0;	//state 0 空闲 1 开放  2 正在游戏

    public function getUpdateSql() 
    {
        return "update `room` SET
            `state`=".intval($this->state)."

            where `rid`=".intval($this->rid)."";
    }

    public function getInsertSql() 
    {
        return "insert into `room` SET

            `state`=".intval($this->state)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `room`
            where `rid`=".intval($this->rid)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

