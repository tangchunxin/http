<?php
/**
 * @author xuqiang76@163.com
 * @final 20170519
 */

exit();

namespace bigcat;

require('./conf/config.php');
require('./conf/ConfigSub.php');
require('./conf/CatConstant.php');
require('./inc/BaseFunction.php');
require('./inc/iCache.php');
require('./inc/CatMemcache.php');

use bigcat\conf\Config;
use bigcat\conf\ConfigSub;
use bigcat\conf\CatConstant;
use bigcat\inc\BaseFunction;
use bigcat\inc\CatMemcache;

//////////////////////////////////////////////////////////////
// $rawsqls[] = "CREATE INDEX `idx_game_log_user_game_log_id`  ON `game_log_user` (game_log_id) COMMENT ''";
$rawsqls[] = "UPDATE `user_game` SET `currency2` = '0', `score` = '0', `cup` = '0'";


var_dump(BaseFunction::execute_sql_backend($rawsqls));