<?php

class filter
{
	private static $_allowtags = 'p|br|b|strong|hr|a|img|object|param|form|input|label|dl|dt|dd|ul|li|div|font|blockquote|span|table|tbody|th|td|tr|colgroup|col',
		$_allowattrs = 'id|class|style|align|valign|src|border|href|target|width|height|title|alt|name|action|method|value|type|bgcolor|bordercolor|cellspacing|cellpadding|colspan|rel|data-lightbox|data',
		$_disallowattrvals = 'expression|javascript:|behaviour:|vbscript:|mocha:|livescript:';

	static function input($string)
	{
		return self::addslashes($string);
	}

	static function xss($string)
	{
		if (is_array($string)) {
			$string = array_map(array('self', 'xss'), $string);
		} else {
			if (strlen($string) > 20) {
				$string = self::addslashes(self::_strip_tags(self::stripslashes($string)));
			}
		}
		return $string;
	}

	static function _strip_tags($string)
	{
		return preg_replace_callback("|(<)(/?)(\w+)([^>]*)(>)|", array('self', '_strip_attrs'), $string);
	}

	static function _strip_attrs($matches)
	{
		if (preg_match("/^(" . self::$_allowtags . ")$/", $matches[3])) {
			if ($matches[4]) {
				preg_match_all("/\s(" . self::$_allowattrs . ")\s*=\s*(['\"]?)(.*?)\\2/i", $matches[4], $m, PREG_SET_ORDER);
				$matches[4] = '';
				foreach ($m as $k => $v) {
					if (!preg_match("/(" . self::$_disallowattrvals . ")/", $v[3])) {
						$matches[4] .= $v[0];
					}
				}
			}
		} else {
			$matches[1] = '&lt;';
			$matches[5] = '&gt;';
		}
		unset($matches[0]);
		return implode('', $matches);
	}

	static function addslashes($string)
	{
		if (!is_array($string)) return addslashes($string);
		return array_map(array('self', 'addslashes'), $string);
	}

	static function stripslashes($string)
	{
		if (!is_array($string)) return stripslashes($string);
		return array_map(array('self', 'stripslashes'), $string);
	}

}
