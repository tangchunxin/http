<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
use bigcat\inc\BaseFunction;
class Kpi extends BaseObject
{
    const TABLE_NAME = 'kpi';

    public $id_time;	//日期时间，每天零点，每天一条
    public $all_user = 0;	//总用户数
    public $new_user = 0;	//新注册用户
    public $active_user = 0;	//活跃用户
    public $game_num = 0;	//每天局数

    public $hour_user = '';	//按时段统计用户在线量
    public $currency = 0;	//每天消费
    public $play_time = 0;  //

    public function getUpdateSql() 
    {
        return "update `kpi` SET
            `all_user`=".intval($this->all_user)."
            , `new_user`=".intval($this->new_user)."
            , `active_user`=".intval($this->active_user)."
            , `game_num`=".intval($this->game_num)."

            , `hour_user`='".BaseFunction::my_addslashes($this->hour_user)."'
            , `currency`=".intval($this->currency)."
            , `play_time`=".intval($this->play_time)."

            where `id_time`=".intval($this->id_time)."";
    }

    public function getInsertSql() 
    {
        return "insert into `kpi` SET

            `id_time`=".intval($this->id_time)."
            , `all_user`=".intval($this->all_user)."
            , `new_user`=".intval($this->new_user)."
            , `active_user`=".intval($this->active_user)."
            , `game_num`=".intval($this->game_num)."

            , `hour_user`='".BaseFunction::my_addslashes($this->hour_user)."'
            , `currency`=".intval($this->currency)."
            , `play_time`=".intval($this->play_time)."
            ";
    }

    public function getDelSql() 
    {
        return "delete from `kpi`
            where `id_time`=".intval($this->id_time)."";
    }

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

