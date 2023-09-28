<?php

/**
 * @version    $Id: tag.class.php 468 2016-06-07 07:55:20Z qinjinpeng $
 */

class tag extends userTag
{

	public static function var_protect($type = "IN", $vars = array())
	{
		static $keepvar, $index;
		if ($type == "IN") {
			$index = isset($index) ? $index + 1 : 0;
			$keepvar[$index] = array();
			foreach ($vars as $k => $r) {
				if (!is_object($r)) {
					$keepvar[$index][$k] = $r;
				}
			}
		} else {
			foreach ($keepvar[$index] as $k => $r) {
				$vars[$k] = $r;
			}
			$index--;
			return $vars;
		}
	}

	public static function _parse_echo($string)
	{
		$string = substr($string, 1, -1);
		$string = self::_parse_var($string);
		if ($string[0] == '@') {
			$string = substr($string, 1);
			return "<?php echo isset({$string})?{$string}:''; ?>";
		}
		return "<?php echo $string; ?>";
	}

	public static function _parse_var($string)
	{
		if (preg_match('/^@?\$[\w]+(\[\$?\w+\])*$/i', $string)) {
			$string = preg_replace_callback('/\[(\$?\w+)\]/i', function ($data) {
				$data[1] = trim($data[1]);
				if (substr($data[1], 0, 1) == '$' || is_numeric($data[1]) || defined($data[1])) {
					return $data[0];
				} else {
					return "['{$data[1]}']";
				}
			}, $string);
		}
		return $string;
	}

	public static function _parse_tag($str)
	{
		return preg_replace_callback('/\$[\w]+(\[\$?\w+\])*/si', function ($string) {
			return tag::_parse_var($string[0]);
		}, $str);
	}

	public static function _parse_function($arr)
	{
		$funcstr = $arr[2];
		$funcstr = self::_parse_tag($funcstr);
		$funcname = trim(preg_replace('/\(.*?$/', '', $funcstr));
		if (function_exists($funcname)) {
			return '<?php echo ' . $funcstr . '; ?>';
		} elseif (strpos($funcname, '::') !== false) {
			list($class, $func) = explode('::', $funcname);
			if (method_exists($class, $func)) {
				return '<?php echo ' . $funcstr . '; ?>';
			}
		}
		return $arr[1] . $arr[2] . $arr[3];
	}

	public static function compress_html($string)
	{
		$string = preg_replace('#\s*([><])\s*#', '$1', $string);
		$string = preg_replace_callback('#<style[^>]*>(.*?)</style>#is', function ($data) {
			return tag::compress_css($data[0]);
		}, $string);
		$string = preg_replace_callback('#<script[^>]*>(.*?)</script>#is', function ($data) {
			return tag::compress_script($data[0]);
		}, $string);
		return $string;
	}

	public static function compress_css($string)
	{
		$string = preg_replace('#\s*/\*.*?\*/\s*#', '', $string);
		$string = preg_replace('#\s*([\{\}\:\;])\s*#', '$1', $string);
		return $string;
	}

	public static function compress_script($string)
	{
		$string = preg_replace('#//[^\r\n]*?[\r\n]{1,}\s*#', '', $string);
		return preg_replace('#\s*([;\{\}\,])\s*#', '$1', $string);
	}
}
