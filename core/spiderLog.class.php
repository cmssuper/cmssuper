<?php

class spiderLog
{
	public static function spider_types()
	{
		return array(
			'Baiduspider' => '百度',
			'Googlebot' => '谷歌',
			'Sogou' => '搜狗',
			'Bingbot' => '必应',
			'Yisouspider' => '神马',
			'360Spider' => '360搜索',
			// 'Safari' => '浏览器'
		);
	}

	public static  function write($siteId)
	{
		$today = date("Y/m/d");
		$timestamp = time();
		$spider_types = spiderLog::spider_types();
		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$spidertype = null;
		foreach ($spider_types as $k => $v) {
			if (stripos($user_agent, $k) !== false) {
				$spidertype = $k;
				break;
			}
		}
		if ($spidertype) {
			if (empty($GLOBALS['G']['spider']) || $GLOBALS['G']['spider'] != 1) {
				return;
			}
			$url = addslashes(get_url());
			$user_agent = addslashes($user_agent);
			$ip = get_client_ip();
			db::query("INSERT into spiderlog (siteId, spider, url, ip, ua, addtime) values ('$siteId', '$spidertype', '$url', '$ip', '$user_agent', '$timestamp')");
			$hour = date("YmdH");
			db::query("UPDATE spiderstats set count=count+1 where siteId='$siteId' AND spider='$spidertype' AND daytime='$hour'");
			if (!db::affected_rows()) {
				db::query("INSERT INTO spiderstats set siteId='$siteId', spider='$spidertype', daytime='$hour',count=1,addtime='$timestamp'");
			}
			if (mt_rand(0, 100) == 0) {
				$deltime = $timestamp - 3600 * 24;
				db::query("delete from spiderstats where addtime<$deltime");
				$maxid = db::getfield("select id from spiderlog order by id desc");
				$maxid = $maxid - 1000;
				db::query("delete from spiderlog where id<'$maxid'");
			}
		} else {
			$time = time();
			$visitTime = !empty($GLOBALS['G']['visitTime']) ? $GLOBALS['G']['visitTime'] : 0;
			if (date("Y/m/d", $visitTime) == $today) {
				db::query("UPDATE config set varValue=varValue+1 where varName='visitCount'");
			} else {
				db::query("UPDATE config set varValue=1 where varName='visitCount'");
			}
			if ($time - $visitTime > 30) {
				db::query("UPDATE config set varValue='$time' where varName='visitTime'");
			}
		}
	}
}
