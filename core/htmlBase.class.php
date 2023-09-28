<?php

class htmlBase
{

	public static $rule_filter = array(
		//去掉注释
		array('/<!--.*-->/isU', ''),
		//去掉独立标签
		array('/<(!doctype|xml|meta|link|base|basefont|bgsound|area|wbr)[^>]*>/isU', ''),
		//去掉闭合标签，留内容
		array('/<(html|body|noscript|form|font|blockquote)[^>]*>(.*)<\/\1>/isU', '\\2'),
		//去掉闭合标签，删除内容
		array('/<(script|style|textarea|select|object|noframes|frame|iframe|frameset|applet|label|embed|map).*<\/\1>/isU', ''),
		//去掉属性
		array('/\s+(onmouseover|onmouseout|onload|click|onclick|onload|align|rel|style|height|width|border|target|title)=([\'"])[^\2]*\2/isU', ''),
		array('/<([a-z1-6]*)[^>]*>[\s\r\n]*<\/\1>/isU', ''),
		array('/<a[^>]*(#|javascript)[^>]*>.*<\/a>/iU', ''),
	);

	/*
	 * HTML过滤预处理
	 */
	public static function tag_filter($body)
	{
		foreach (self::$rule_filter as $key => $value) {
			$body = preg_replace($value[0], $value[1], $body);
		}
		$rule[] = array('/<([\/]?)div[^>]*>/i', '<\1p>');
		$rule[] = array('/<([\/]?)P[^>]*>/', '<\1p>'); //大P转小p
		$rule[] = array('/<([\/]?)li[^>]*>/', '<\1p>'); //li转p
		$rule[] = array('/<img([^>]*)>/isU', '<p align="center"><img\1></p>');
		$rule[] = array('/([^\'"\=\/])(http:\/\/|www\.)[A-Za-z0-9_\-\.\/]*([^A-Za-z0-9_\-\.\/])/i', '\1\3'); //去除文本网址s
		foreach ($rule as $key => $value) {
			$body = preg_replace($value[0], $value[1], $body);
		}
		return trim($body);
	}

	//获取编码     
	public static function get_charset($data)
	{
		if (preg_match("#meta[\s]*charset=\"(gb2312|gbk|utf-8)\"#si", $data, $mt)) {
			return strtolower($mt[1]);
		}
		if (preg_match(
			"#meta[\s]*charset=['\"](gb2312|gbk|utf-8)['\"]#si",
			$data,
			$mt
		)) {
			return strtolower($mt[1]);
		}
		if (preg_match("#text/html;[\s]*charset=(gb2312|gbk|utf-8)#si", $data, $mt)) {
			return strtolower($mt[1]);
		}
		if (preg_match(
			"#text/html;[\s]*charset=\"(gb2312|gbk|utf-8)\"#si",
			$data,
			$mt
		)) {
			return strtolower($mt[1]);
		}
	}

	//提取关键词
	public static function get_keywords($body)
	{
		$rule1 = "/<meta[\s]+name=['\"]keywords['\"] content=['\"]([^>]*)['\"]/isU";
		$rule2 = "/<meta[\s]+content=['\"]([^>]*)['\"] name=['\"]?keywords['\"]/isU";
		if (preg_match($rule1, $body, $mt)) {
			return $mt[1];
		} elseif (preg_match($rule2, $body, $mt)) {
			return $mt[1];
		}
		return '';
	}

	//提取描述
	public static function get_description($body)
	{
		$rule1 = "/<meta[\s]+name=['\"]description['\"] content=['\"]([^>]*)['\"]/isU";
		$rule2 = "/<meta[\s]+content=['\"]([^>]*)['\"] name=['\"]description['\"]/isU";
		if (preg_match($rule1, $body, $mt)) {
			return $mt[1];
		} elseif (preg_match($rule2, $body, $mt)) {
			return $mt[1];
		}
		return '';
	}
}
