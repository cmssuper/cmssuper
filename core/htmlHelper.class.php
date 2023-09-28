<?php

class htmlHelper{

	public static function matchcontent($data,$rule){
		if(strpos($rule, '[内容]')===false){
			return '';
		}
		list($a,$b) = explode('[内容]', $rule);
		$a = trim($a);
		$b = trim($b);
		$tmp = explode($a,$data);
		if(!empty($tmp[1]) && strpos($tmp[1], $b)){
			$tmp2 = explode($b,$tmp[1]);
		}
		return !empty($tmp2[0])?$tmp2[0]:'';
	}

	public static function get_longest($array){
		if(count($array)==1) return 0;
		$rekey = false;
		$int = 0;
		foreach ($array as $key => $value) {
			$len = mb_strlen($value, 'utf-8');
			if($len>$int && $len<50){
				$rekey = $key;
				$int = $len;
			}
		}
		return $rekey;
	}

	public static function fix_path($baseurl, $url){
		if(	empty($baseurl) || 
			strpos($url, '#')===0 || 
			//strpos($url, 'https://')===0 || 
			strpos($url, 'javascript:')===0||
			strpos($url, 'data:')===0) 
		{
			return '';
		}
		if(strpos($url, '#')!==false || strpos($url, '?')!==false){
			$url = preg_replace('/[\#\s].*?$/i', '', $url);
		}
		//简单路径
		if(strpos($url, 'http://')===0 || strpos($url, 'https://')===0){
			return $url;
		}elseif(strpos($url, '/')===0){
			if (strpos($url, '//')===0) {
				return "http:".$url;
			}
			$urlinfo = parse_url($baseurl);
			return "http://".$urlinfo['host'].$url;
		}
		//相对路径
		if(substr_count($baseurl, '/')>2){
			$basepath = preg_replace('#/[^/]*$#', "", $baseurl);
		}else{
			$basepath = rtrim($baseurl, '/');
		}

		if(strpos($url, './')===0){
			return $basepath.substr($url, 1);
		}elseif(strpos($url, '../../')===0){
			return dirname(dirname($basepath)).substr($url, 5);
		}elseif(strpos($url, '../')===0){
			return dirname($basepath).substr($url, 2);
		}else{
			return $basepath.'/'.$url;
		}
	}

}
