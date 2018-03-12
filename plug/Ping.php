<?php  

class Ping
{
	public $address;
	public $ip;
	public $ip_ddos;
	public $log;
	public $log_ok;
	public $log_fail;

	
	public function __construct()
	{
		require(__DIR__."/../conf/ConfigSub.php");
		$this->address = __DIR__."/../conf/ConfigSub.php";
		$this->log =  __DIR__."/../log/log.log";
			
		$ip_arr = \bigcat\conf\ConfigSub::TCP_ARR[0][0];
		$this->ip = strstr($ip_arr ,":", TRUE);

		$ip_arr_ddos = \bigcat\conf\ConfigSub::TCP_ARR_DDOS[0][0];
		$this->ip_ddos = strstr($ip_arr_ddos ,":", TRUE);

		//测试
		//$this->ip = '211.159.160.119';
		$this->log_ok = "echo `date +%H:%M:%S` ping " . " $this->ip \" is OPEN \" >> $this->log";
		$this->log_fail = "echo `date +%H:%M:%S` ping" . " $this->ip \" is CLOSE \" >> $this->log";
		$this->log_fail_ddos = "echo `date +%H:%M:%S` ping" . " $this->ip_ddos \" is CLOSE \" >> $this->log";


	}

	public function pingAddress()
	{
	    $status = -1;
	    if (strcasecmp(PHP_OS, 'Linux') === 0) {
	        // Linux服务器下
	        $pingresult = exec("ping -c 1 {$this->ip}", $outcome, $status);
	    } elseif (strcasecmp(PHP_OS, 'WINNT') === 0) {
	        // Windows 服务器下
	        $pingresult = exec("ping -n 1 {$this->ip}", $outcome, $status);
	    }
		if (0 == $status)
		{
	        $status = true;
			$shell = "sed -i \"s/gaofang_open/gaofang_close/\"  ".$this->address;
			exec( $shell , $outcome, $status);
			passthru($this->log_ok);
		} 
		else
		{
			$pingresult = exec("ping -c 1 {$this->ip}", $outcome, $status);
			if (0 == $status) 
			{
				$status = true;
				$shell = "sed -i \"s/gaofang_open/gaofang_close/\"  ".$this->address;
				exec( $shell , $outcome, $status);
				passthru($this->log_ok);
			} 
			else 
			{
				$status = false;
				passthru($this->log_fail);
				$pingresult = exec("ping -c 1 {$this->ip_ddos}", $outcome, $status);
				if(0 == $status)
				{
					$shell = "sed -i \"s/gaofang_close/gaofang_open/\"  ".$this->address;
					exec( $shell , $outcome, $status);
				}
				else
				{
					passthru($this->log_fail_ddos);
				}
			}
	    }
		return $status;
		
	}

}

$ping = new Ping();

$ping->pingAddress();