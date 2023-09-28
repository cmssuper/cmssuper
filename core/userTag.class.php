<?php

class userTag
{

	/**
	 *  调用广告
	 * @access    public
	 * @return    array
	 */

	public static function ad($abc)
	{
		$yuming_id = $GLOBALS['G']['site']['id'];
		$ad = db::find("select allowSpider,content from ads where abc ='$abc' AND (yuming_id=0 or yuming_id='$yuming_id') AND status=1 order by yuming_id desc");
		if ($ad) {
			if ($ad['allowSpider'] == 0 && isspider()) {
				return '';
			}
			return $ad['content'];
		}
	}

	/**
	 *  友情链接
	 * @access    public
	 * @return    array
	 */

	public static function flink($num)
	{
		$yuming_id = $GLOBALS['G']['site']['id'];
		$flink = db::select("select * from flink where status=1 and yuming_id=0 OR yuming_id='$yuming_id' limit $num");
		return  $flink;
	}

	/**
	 *  页面标签
	 * @access    public
	 * @return    array
	 */

	public static function tags($num = 10)
	{
		$yuming_id = $GLOBALS['G']['site']['id'];
		$tags = db::select("select l.* from tagindex i LEFT JOIN tagslist l ON i.tagid=l.id where i.siteId='$yuming_id' limit $num");
		return  $tags;
	}

	public static function volist($request_string)
	{
		return self::arclist($request_string, true);
	}

	public static function arclist($request_string, $autosp = false)
	{
		parse_str($request_string, $arr);

		if ($autosp) {
			$arr['tagname'] = 'list';
		}

		$rest_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if (preg_match('#/hot#', $rest_uri)) {
			$arr['orderby'] = 'click';
			$arr['orderway'] = '/desc';
		} elseif (preg_match('#/photo#', $rest_uri)) {
			$arr['flag'] = empty($arr['flag']) ? 'p' : $arr['flag'] . ',p';
		}

		$list = get_arclist($arr, 'list');

		foreach ($list as $key => $val) {
			$content = arcRead($GLOBALS['G']['site']['id'], $val['id']);

			preg_match_all('/<img.+?src=["\']*([^"\'\s\>]+)/i', $content, $mt);

			$imglist = array_filter($mt[1]);
			if (count($imglist) > 4) {
				$imglist = array_slice($imglist, 0, 5);
				foreach ($imglist as $k => $v) {
					if (strpos($v, 'http') === false) {
						$imglist[$k] = preg_replace("#(\.[^\.]*)$#", '_small$1', $v);
					}
				}
			} else {
				$imglist = false;
			}

			$val['imglist'] = $imglist;

			$list[$key] = $val;
		}
		return $list;
	}

	public static function hots($num = 10)
	{
		return self::arclist("orderby=click", $num);
	}

	public static function news($num = 10)
	{
		return self::arclist("orderby=news", $num);
	}

	public static function pagelink($request_string = "")
	{
		parse_str($request_string, $arr);
		return get_pagelist($arr);
	}

	public static function meta()
	{
		if (IS_MOBILE_SITE_OPEN) {
			if (IS_MOBILE_SITE) {
				return "<link rel=\"canonical\" href=\"" . WEBURL . "\">\n";
			} else {
				return "<meta name=\"mobile-agent\" content=\"format=html5;url=" . MOBILEURL . "\" />
	<link rel=\"alternate\" media=\"only screen and (max-width: 640px)\" href=\"" . MOBILEURL . "\">
	<script type=\"text/javascript\">
		if (window.location.href.indexOf(\"?via=\") < 0) {
			if (/AppleWebKit.*Mobile/i.test(navigator.userAgent) || /Android|Windows Phone|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
				window.location.href = \"" . MOBILEURL . "\";
			}
		}
	</script>\n";
			}
		}
	}

	// php5.3+
	public static function __callStatic($func, $args)
	{
		switch ($func) {
			case 'test':
				trigger_error("您正在调用测试标签", E_USER_ERROR);
				break;
			default:
				trigger_error("您正在调用一个不存在的标签 " . __CLASS__ . "::$func()", E_USER_ERROR);
				break;
		}
	}
}
