<?php

class controller
{
	public $scheme, $conf, $resetUri, $baseHost;
	public $site, $siteId, $siteName;
	public $nav, $sitenav;
	public $mobileUrl, $webUrl;
	//兼容老版本和其他程序变量
	public $mobile_url, $web_url;
	public $url, $rest_uri, $yuming_website, $yuming_id, $sitename;

	public function __construct()
	{
		$this->scheme = getScheme();
		$this->resetUri = $this->rest_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$this->conf = get_config();
		if (empty($this->conf)) {
			die();
		}

		if (VERSION != $this->conf['softversion']) {
			throw new Exception("软件版本(" . VERSION . ")与数据库版本(" . $this->conf['softversion'] . ")不匹配");
		}

		//域名处理
		$hostName = $_SERVER['HTTP_HOST'];
		if (strpos($hostName, 'www.') === 0) {
			$hostName = substr($hostName, 4);
		}
		if (strpos($hostName, 'm.') === 0) {
			define('IS_MOBILE_SITE', true);
			$hostName = substr($hostName, 2);
		} else {
			define('IS_MOBILE_SITE', false);
		}

		if (!defined('APPNAME')) {
			$this->site = site($hostName);
			if (!$this->site) {
				die();
			}
			$this->siteId = $this->site['id'];
			$this->siteName = $this->site['sitename'];
			$this->baseHost = $this->scheme . '://' . $_SERVER['HTTP_HOST'];
			$this->webUrl = $this->scheme . '://' . $this->site['name'] . $_SERVER['REQUEST_URI'];
			$this->mobileUrl = $this->scheme . '://m.' . $hostName . $_SERVER['REQUEST_URI'];
			define('WEBURL', $this->webUrl);
			define('MOBILEURL', $this->mobileUrl);

			if (!IS_MOBILE_SITE && $this->site['name'] != $hostName) {
				header("HTTP/1.1 301 Moved Permanently");
				header("Location:" . $this->webUrl);
				exit;
			}
			define('IS_MOBILE_SITE_OPEN', $GLOBALS['G']['site']['mobileSwitch'] == 1);

			$GLOBALS['G']['nav'] = $this->nav = get_classlist();

			//兼容老模版
			$this->yuming_website = $this->site;
			$this->yuming_id = $this->siteId;
			$this->web_url = $this->webUrl;
			$this->mobile_url = $this->mobileUrl;
			$this->sitename = $this->siteName;
			$this->sitenav = $this->nav;
		}

		//定时器启动
		$lockfile = DATA . '/session/crawler.lock';
		if (is_file($lockfile)) {
			$lockfileTime =  filemtime($lockfile);
			if (time() - $lockfileTime > 60) {
				$threadId = date('YmdHis') . mt_rand(1000, 9999);
				file_put_contents($lockfile, $threadId);
				touch($lockfile, time() - 10);
				self::sendNext('/crawler/runner', 'threadId=' . $threadId . '&counter=0');
			} elseif (defined('APPNAME') && APPNAME == 'CRAWLER' && M == 'crawler' && A == 'index') {
				echo '<!--' . (time() - $lockfileTime) . '-->';
			}
		}

		//定时发布处理器
		$this->arcAutoPub();

		//推送
		if (!empty($GLOBALS['G']['baidu_tui_token']) && rand(0, 10) == 0) {
			$ping = new ping_RPC();
			$ping->ping();
		}

		if ($this->site) {
			$GLOBALS['cfg_webname'] = $this->site['sitename'];
			$GLOBALS['cfg_keywords'] = $this->site['keywords'];
			$GLOBALS['cfg_description'] = $this->site['description'];
			$GLOBALS['cfg_clihost'] = $GLOBALS['cfg_basehost'] = $this->baseHost;
			$GLOBALS['cfg_powerby'] = "Copyright &copy; 2023 " . $this->site['sitename'] . " 版权所有";
			$GLOBALS['cfg_mobileurl'] = $this->mobile_url;
			$GLOBALS['cfg_mb_open'] = "";
			$GLOBALS['cfg_cmspath'] = "";
			$cfg_templets_dir = '/templates';
			$GLOBALS['cfg_templeturl'] = $GLOBALS['cfg_clihost'] . $cfg_templets_dir;
			$GLOBALS['theme'] = $GLOBALS['templets'] = $GLOBALS['cfg_templets_skin'] = $cfg_templets_dir . '/' . $this->site['template'];
		}
	}

	public static function redirect($url, $msg = null)
	{
		if (!empty($url)) {
			if (defined('APPNAME')) {
				$url = str_replace('__APPROOT__', '/' . APPNAME, $url);
			}
			if ($msg) {
				echo ("<script>alert('$msg', 0);</script>");
			}
			echo ("<script>window.location.href='$url';</script>");
			exit;
		}
	}

	public static function json($data)
	{
		header('Content-type: application/json');
		echo json_encode($data);
		exit;
	}

	public static function page_404()
	{
		header('HTTP/1.1 404 Not Found');
		header("Status: 404 Not Found");
		if (self::tpl('404', true)) {
			require self::tpl('404');
		} else {
			echo 'HTTP/1.1 404 Not Found';
		}
		exit;
	}

	public static function page_503()
	{
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 60');
	}

	public static function tpl($tplName, $onlyCheck = false)
	{
		if (defined('APPNAME')) {
			$path = ROOT . DS . APPNAME . DS . 'templates';
			$cachepath = DATA . DS . 'tplcache' . DS . APPNAME;
		} else {
			$template = isset($GLOBALS['G']['site']['template']) ? $GLOBALS['G']['site']['template'] : 'default';
			$path = ROOT . DS . 'templates/' . $template;
			$cachepath = DATA . DS . 'tplcache/' . $template;
			if (IS_MOBILE_SITE && IS_MOBILE_SITE_OPEN) {
				$tplName = $tplName . '_m';
			}
			if (!file_exists($path . '/' . $tplName . '.htm')) {
				$GLOBALS['theme'] = $GLOBALS['templets'] = $GLOBALS['cfg_templets_skin'] = '/templates/default';
				$path =  ROOT . DS . 'templates/default';
			}
		}
		if ($onlyCheck) {
			return is_file($path . DS . $tplName . '.htm');
		} else {
			$ins = new template($tplName, $path, $cachepath);
			return $ins->view();
		}
	}

	// 采集定时器
	public function runner()
	{
		set_time_limit(60);
		ignore_user_abort(true);
		$lockfile = DATA . '/session/crawler.lock';
		$threadId = gp('threadId');
		$counter = (int)gp('counter');
		$fileId = file_get_contents($lockfile);
		if ($fileId != $threadId) {
			die('IdNotMatch');
		}
		if (time() - filemtime($lockfile) > 8) {
			if (function_exists('fastcgi_finish_request')) {
				fastcgi_finish_request();
			}
			touch($lockfile);
			sleep(10);
			self::sendNext('/crawler/runner', 'threadId=' . $threadId . '&counter=' . ($counter + 1));
			sleep(2);
			$acs = array('crawlKeyword', 'crawlRule');
			self::sendNext('/crawler/handle', 'acs=' . $acs[$counter % count($acs)]);
		} else {
			echo time() - filemtime($lockfile);
		}
	}

	public static function sendNext($path, $param)
	{
		$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
		$host = $_SERVER['HTTP_HOST'];
		$port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
		$len = strlen($param);
		if (function_exists(('fsockopen'))) {
			$fp = fsockopen($ip, $port, $errorCode, $errorInfo, 20);
			if (!$fp) {
				return false;
			}
			$http  = "POST $path HTTP/1.1\r\nHost: $host\r\nContent-type: application/x-www-form-urlencoded\r\nContent-Length: $len\r\nConnection: close\r\n\r\n$param\r\n\r\n";
			fwrite($fp, $http);
			usleep(10000);
			fclose($fp);
		} else {
			$opts = array('http' =>
			array(
				'method'  => 'POST',
				'timeout' => 5,
				'header' => "Host: $host\r\nContent-type: application/x-www-form-urlencoded\r\nContent-Length: $len\r\nConnection: close\r\n",
				'content' => $param,
			));
			$context  = stream_context_create($opts);
			$scheme = $port == '443' ? 'https' : 'http';
			@file_get_contents("{$scheme}://{$ip}{$path}", false, $context);
		}
	}

	// 文章自动发布
	public static function arcAutoPub()
	{
		if (isset($GLOBALS['G']['arcTimer']) && $json = json_decode($GLOBALS['G']['arcTimer'], true)) {
			$n = 0;
			foreach ($json as $k => $v) {
				if ($v['addtime'] < time()) {
					$n++;
					unset($json[$k]);
					db::query("update content_{$v['siteId']} set status=1 where id='$k'");
				}
			}
			if ($n > 0) {
				$json = addslashes(json_encode($json));
				db::query("REPLACE INTO config (varName, varValue) values ('arcTimer', '$json')");
			}
		}
	}
}
