<?php
namespace bigcat\model;

use bigcat\inc\BaseObject;
class Agent extends BaseObject
{
    public $id;	//agent ID
    public $agent;	//agent info

    public function before_writeback() 
    {
        parent::before_writeback();
        return true;
    }

}

