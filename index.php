<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat;

use bigcat\inc\BaseFunction;
use bigcat\conf\Config;
use bigcat\conf\CatConstant;

class CatHead
{
	/**
     * Autoload root path.
     *
     * @var string
     */
	protected static $_autoload_root_path = '';

	/**
     * Set autoload root path.
     *
     * @param string $root_path
     * @return void
     */
	public static function set_root_path($root_path)
	{
		self::$_autoload_root = $root_path;
	}

	/**
     * Load files by namespace.
     *
     * @param string $name
     * @return boolean
     */
	public static function load_by_namespace($name)
	{
		$class_path = str_replace('\\', DIRECTORY_SEPARATOR, $name);
		if (strpos($name, 'bigcat\\') === 0)
		{
			$class_file = __DIR__ . substr($class_path, strlen('bigcat')) . '.php';
		} else 
		{
			if (self::$_autoload_root_path)
			{
				$class_file = self::$_autoload_root_path . DIRECTORY_SEPARATOR . $class_path . '.php';
			}
			if (empty($class_file) || !is_file($class_file))
			{
				$class_file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . "$class_path.php";
			}
		}

		if (is_file($class_file))
		{
			require_once($class_file);
			if (class_exists($name, false) || interface_exists($name, false))
			{
				return true;
			}
		}
		return false;
	}

	public static function init()
	{
		if(Config::DEBUG)
		{
			error_reporting(7);
			error_reporting(E_ALL|E_STRICT);
			ini_set('display_errors', 'on');
		}

		date_default_timezone_set('Asia/Chongqing');

		spl_autoload_register('\bigcat\CatHead::load_by_namespace');
	}

	public static function run()
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__);
		do {
			$requests = array_merge($_GET, $_POST, $_REQUEST );
			$requests['parameter'] = @rawurldecode($requests['parameter']);			
			if( empty($requests['parameter']) )
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}
			
			$requests['parameter'] = urldecode($requests['parameter']);
			$parameter = json_decode($requests['parameter'], true);
			if( empty($parameter) || !is_array($parameter) )
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$params = array();
			foreach( $parameter as $key => $value )
			{
				$params[$key] = $value;
			}

			if( empty($params['mod']) || empty($params['act']) )
			{
				$response['code'] = CatConstant::ERROR;	$response['desc'] = __LINE__; break;
			}
			$module = $params['mod'];
			$action = $params['act'];

			//BaseFunction::logger("./log/log.log", "【obj_user】:\n".var_export($params, true)."\n".__LINE__."\n");
			
			//版本校验
			if(
			Config::C_VERSION_CHECK
			&& ( empty($requests['c_version'])
				|| empty($params['platform']) || !in_array($params['platform'], array('gfplay', 'gfplay_ios', 'gfplay_h5'))
				|| ($requests['c_version'] != Config::C_VERSION && $requests['c_version'] != Config::C_VERSION_PRE && $params['platform'] == 'gfplay')
				|| ($requests['c_version'] != Config::C_VERSION_IOS && $requests['c_version'] != Config::C_VERSION_IOS_PRE && $params['platform'] == 'gfplay_ios')
			)
			&& (empty(CatConstant::UNCHECK_C_CERSION_ACT[$module]) || !in_array($action, CatConstant::UNCHECK_C_CERSION_ACT[$module]))
			)
			{
				$response['code'] = CatConstant::ERROR_VERSION; $response['desc'] = __LINE__;
				$response['sub_code'] = CatConstant::ERROR_VERSION_DESC; $response['sub_desc'] = '版本号过低请重新下载'; break;
			}
			
			//API权限校验
			$verified = false;
			if(!empty($requests['randkey']) && BaseFunction::encryptMD5($params) == $requests['randkey'])
			{
				$verified = true;
			}
			else if( !empty(CatConstant::UNCHECK_VERIFIED_ACT[$module]) && in_array($action, CatConstant::UNCHECK_VERIFIED_ACT[$module]))
			{
				//开放权限接口
				$verified = true;
			}
			else if(!empty($params['key']) && $params['key']==Config::API_KEY)
			{
				$verified = true;
			}
			else
			{
				$verified = false;
			}

			if( !$verified )
			{
				$response['code'] = CatConstant::ERROR_VERIFY; $response['desc'] = __LINE__; break;
			}

			$params['c_version'] = $requests['c_version'];

			if( empty(CatConstant::MODELS[$module]) )
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__; break;
			}

			$var_class = CatConstant::MODELS[$module];
			if (in_array($action, CatConstant::BACKGROUND_ACTION)) 
			{
				$var_class = CatConstant::MODELS['BackgroundSystem'];
			}

			$obj = new $var_class();
			if( !method_exists($obj, $action) )
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __LINE__.$action; break;
			}

			$response = $obj->$action($params);

			if( !empty($response['sub_code']) )
			{
				if(isset(CatConstant::SUB_DESC[$module.'_'.$action]['sub_code_'.$response['sub_code']]))
				{
					$response['sub_desc'] = CatConstant::SUB_DESC[$module.'_'.$action]['sub_code_'.$response['sub_code']];
				}
			}
			else
			{
				$response['sub_code'] = 0;
			}

			$response['module'] = $module;
			$response['action'] = $action;

		}while(false);

		BaseFunction::output($response);
	}
}

//////////////////////////////////////////////////////////////

require('./conf/ConfigSub.php');
require('./conf/config.php');

if(defined("bigcat\\conf\\Config::XHPROF") && Config::XHPROF)
{
	xhprof_enable();
}

\bigcat\CatHead::init();
\bigcat\CatHead::run();

if(defined("bigcat\\conf\\Config::XHPROF") && Config::XHPROF)
{
	$xhprof_data = xhprof_disable();
	include_once '/data/www/html/xhprof_lib/utils/xhprof_lib.php';
	include_once '/data/www/html/xhprof_lib/utils/xhprof_runs.php';

	$xhprof_runs = new \XHProfRuns_Default();

	$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_mahjong");
}

