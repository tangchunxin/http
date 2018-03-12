<?php
namespace bigcat\model;

use bigcat\inc\BaseFunction;

class Uid
{
    public static $sql = 'INSERT INTO `uid`(uid) VALUES(DEFAULT )';
    public static function get_uid() 
    {
    	$result = BaseFunction::execute_sql_backend(array(self::$sql));
		if(!empty($result[0]['insert_id']))
		{
			return $result[0]['insert_id'];
		}
		return 0;
    }
}

