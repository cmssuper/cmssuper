<?php

if (!defined('IN_SYS')) exit('Access Denied');

class plus_controller extends admincp
{
	public function __construct()
	{
		parent::__construct();
	}

	public function getCommon()
	{
		$data['conf'] = $this->conf;
		$data['user'] = $this->user;
		$data['sites'] = $this->sites;
		self::json(array('success' => $data));
	}

	public function stats()
	{
		$respond['siteCount'] = db::getfield("select count(*) from yuming");
		$today = date("Ymd");
		$todaystart = strtotime($today);

		// 统计文章数
		$spiderCount = $newsCount = $todayCount = 0;
		if ($this->sites) {
			$countSql = array();
			foreach ($this->sites as $key => $value) {
				$countSql[] = "select sum(case when addtime>$todaystart then 1 else 0 end) as todaycount, count(*) as tmpcount from content_{$key}";
				//爬虫
				$site_spider =  DATA . DS . 'spider' . DS . $key . DS . 'spider.php';
				if (is_file($site_spider)) {
					$spider = require $site_spider;
					$spiderCount += isset($spider[$today]) ? array_sum($spider[$today]) : 0;
				}
			}

			$countSql = join(" union all ", $countSql);
			$countSql = "select sum(todaycount) as todaycount,sum(tmpcount) as tmpcount from ($countSql) a";
			$res = db::find($countSql);

			$newsCount = empty($res['tmpcount']) ? 0 : $res['tmpcount'];
			$todayCount = empty($res['todaycount']) ? 0 : $res['todaycount'];
		}
		$respond['spiderCount'] = $spiderCount;
		$respond['newsCount'] = $newsCount;
		$respond['todayCount'] = $todayCount;

		$respond['memory_limit']  = ini_get('memory_limit');
		$respond['free_space'] = disk_free_space(dirname(dirname(__FILE__)));
		$respond['visitCount'] = (int) db::getField("select varValue from config where varName='visitCount'");
		$respond['softversion']  = $this->conf['softversion'];
		$respond['serverInfo'] =  php_uname('s');
		$respond['systime'] =  date("Y-m-d H:i:s");
		$respond['timestamp'] =  time();
		self::json(array('success' => $respond));
	}

	public function  upload()
	{
		$config = array(
			"pathFormat" => "/uploads/{yyyy}{mm}/{dd}/{time}{rand:6}",
			"maxSize" => 2048000,
			"allowFiles" => array(".png", ".jpg", ".jpeg", ".gif", ".bmp", ".zip", ".rar", ".doc", ".docx", '.xls'),
		);
		$up = new uploader('file', $config);
		$res = $up->getFileInfo();
		if ($res['state'] == 'SUCCESS') {
			self::json(array('success' => $res));
		} else {
			self::page_503($res['state']);
		}
	}
}
