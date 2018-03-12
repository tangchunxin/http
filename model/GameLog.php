<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class GameLog extends BaseObject
{
    const TABLE_NAME = 'game_log';

    public $id;	//自增长id
    public $rid = 0;	//房间号
    public $uid = 0;	//房主 uid
    public $game_info = '';	//游戏信息
    public $type = 0;	//1 一局记录 2 一房间记录

    public $time = 0;	//记录时间
    public $save = '';	//
    public $game_type = 0;  //
    public $play_time = 0;  //    

    public function getUpdateSql() 
    {
        return "update `game_log` SET
            `rid`=".intval($this->rid)."
            , `uid`=".intval($this->uid)."
            , `game_info`='".BaseFunction::my_addslashes($this->game_info)."'
            , `type`=".intval($this->type)."

            , `time`=".intval($this->time)."
            , `save`='".BaseFunction::my_addslashes($this->save)."'
            , `game_type`=".intval($this->game_type)."
            , `play_time`=".intval($this->play_time)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `game_log` SET

            `rid`=".intval($this->rid)."
            , `uid`=".intval($this->uid)."
            , `game_info`='".BaseFunction::my_addslashes($this->game_info)."'
            , `type`=".intval($this->type)."

            , `time`=".intval($this->time)."
            , `save`='".BaseFunction::my_addslashes($this->save)."'
            , `game_type`=".intval($this->game_type)."
            , `play_time`=".intval($this->play_time)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `game_log`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

