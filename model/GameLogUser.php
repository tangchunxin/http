<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class GameLogUser extends BaseObject
{
    const TABLE_NAME = 'game_log_user';

    public $id;	//自增长id
    public $game_log_id = 0;	//房间号
    public $uid = 0;	//用户 id

    public function getUpdateSql() 
    {
        return "update `game_log_user` SET
            `game_log_id`=".intval($this->game_log_id)."
            , `uid`=".intval($this->uid)."

            where `id`=".intval($this->id)."";
    }

    public function getInsertSql() 
    {
        return "insert into `game_log_user` SET

            `id`=".intval($this->id)."
            , `game_log_id`=".intval($this->game_log_id)."
            , `uid`=".intval($this->uid)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `game_log_user`
            where `id`=".intval($this->id)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

