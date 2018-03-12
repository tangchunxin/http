<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class GameTableLog extends BaseObject
{
    const TABLE_NAME = 'game_table_log';

    public $id;	//自增长id
    public $rid = 0;	//房间号
    public $uid = 0;	//房主 uid
    public $game_table_info = '';	//每桌总分
    public $time = 0;	//记录时间

    public $state = 0;	//记录状态(0:未读,1:已读)

    public function getUpdateSql() 
    {
        return "update `game_table_log` SET
            `rid`=".intval($this->rid)."
            , `uid`=".intval($this->uid)."
            , `game_table_info`='".BaseFunction::my_addslashes($this->game_table_info)."'
            , `time`=".intval($this->time)."

            , `state`=".intval($this->state)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `game_table_log` SET

            `rid`=".intval($this->rid)."
            , `uid`=".intval($this->uid)."
            , `game_table_info`='".BaseFunction::my_addslashes($this->game_table_info)."'
            , `time`=".intval($this->time)."

            , `state`=".intval($this->state)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `game_table_log`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

