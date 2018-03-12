<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat;

if(!isset($_GET['test_key']) || $_GET['test_key'] != 'ncbdtocar' )
{
    exit('exit');
}

$data_receive = array(
	'rule'=>array('min_fan'=>0, 'top_fan'=>4, 'zimo_rule'=>0, 'dian_gang_hua'=>1, 'is_change_3'=>1, 'is_yaojiu_jiangdui'=>1, 'is_menqing_zhongzhang'=>1, 'is_tiandi_hu'=>1, 'set_num'=>8)
);



$data_receive = array_merge($_GET, $_POST, $_COOKIE, $_REQUEST, $data_receive );

$_REQUEST = array('randkey'=>$randkey, 'c_version'=>'0.0.1', 'parameter'=>json_encode($data_receive) );

require ("./index.php");

