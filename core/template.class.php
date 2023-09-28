<?php

/**
 * @version    $Id: template.class.php 468 2016-06-07 07:55:20Z qinjinpeng $
 */

class template
{

	private  $tpl, $compile, $compress;
	private static $tpls = array();

	private $rules = array(
		//去掉//开头注释
		'/<\!--\s*\/\/.*?-->/is' => '',
		// template
		'/(<\!--|\{)\s*(template|include|require)\s+([\.\$\w\[\]\'"\/\\\]+)\s*(-->|\})/i' => '<?php include self::tpl("$3"); ?>',
		// function
		'/(\{|<\!--)(\s*[\w\:]+\(.*?\)\s*)(-->|\})/i' => array('_parse_function', -1),
		// echo 
		'/\{@?\s?\$[\$\w\[\]\'"]+\s?\}/si' => array('_parse_echo', 0),
		'/\{echo\s+(.+?)\}/is' => "<?php echo $1 ?>",
		// { to <!--
		'/\{\s*((if|else|elseif|foreach|sql|eval|\/if|\/foreach|\/sql).*?)\}/i' => '<!--$1 -->',
		'/<\!--\s*(if|elseif|foreach).*?-->/i' => array('_parse_tag', 0),
		// sql
		'/<\!--\s*sql\s+(.+?)\s*-->/i' => '<!--eval $_sql_result = db::select("$1");--><!--foreach $_sql_result -->',
		'/<\!--\s*\/sql\s*-->/i' => '<!--/foreach -->',
		// if
		'/<\!--\s*if\s+(.+?)\s*-->/i' => '<?php if($1) { ?>',
		'/<\!--\s*else\s*-->/i' => '<?php } else { ?>',
		'/<\!--\s*elseif\s+(.+?)\s*-->/i' => '<?php } elseif ($1) { ?>',
		'/<\!--\s*\/if\s*-->/i' => '<?php } ?>',
		// foreach
		'/<\!--\s*foreach\s+(\S+)\s*-->/i' => '<?php tag::var_protect("IN", get_defined_vars());$index=0;foreach($1 as \$__i => \$__value) {if(is_array(\$__value)) {$index++;foreach(\$__value as \$__k=>\$__v){\${\$__k}=\$__v; } } ?>',
		'/<\!--\s*foreach\s+(\S+)\s+(\S+)\s*-->/i' => '<?php tag::var_protect("IN", get_defined_vars()); $index=0; foreach($1 as $2) { $index++; ?>',
		'/<\!--\s*foreach\s+(\S+)\s+(\S+)\s+(\S+)\s*-->/i' => '<?php tag::var_protect("IN", get_defined_vars()); $index=0; foreach($1 as $2 => $3) { $index++; ?>',
		'/<\!--\s*\/foreach\s*-->/i' => '<?php }; extract( tag::var_protect("OUT"), EXTR_OVERWRITE); ?>',
		// eval
		'/<\!--\s*eval\s+(.+?)\s*-->/is' => '<?php $1 ?>',
	);

	public function __construct($tpl, $tpldir, $cachedir, $prefix = '')
	{
		$this->tpl = $tpldir . DS . $tpl . '.htm';
		$this->compile = $cachedir . DS . $prefix . str_replace("/", "_", $tpl) . '.tpl.php';
	}

	public function view()
	{
		if (!is_file($this->tpl)) exit("Template not exists" . $this->tpl);
		self::$tpls[] = $this->tpl;
		if (!file_exists($this->compile) || is_file($this->tpl) && (filemtime($this->tpl) > filemtime($this->compile) && (isset($_SERVER['DEBUG']) || time() - filemtime($this->tpl) >= 2))) {
			$this->_compile();
		}
		return $this->compile;
	}

	private function _compile()
	{

		$data = file_get_contents($this->tpl);
		$data = $this->_parse($data);
		$dir = dirname($this->compile);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		if (false === @file_put_contents($this->compile, $data)) exit("$this->compile file is not writable");
		return true;
	}

	private function _parse($string)
	{
		$GLOBALS['tmpSection'] = array();
		$string = $this->protect_code($string);

		$string = $this->_before($string);

		foreach ($this->rules as $key => $value) {
			if (is_array($value)) {
				$string = preg_replace_callback($key, function ($data) use ($value) {
					$data = $value[1] >= 0 ? $data[$value[1]] : $data;
					return call_user_func(array('tag', $value[0]), $data);
				}, $string);
			} else {
				$string = preg_replace($key, $value, $string);
			}
		}
		$string = $this->protect_code($string);

		$string = $this->_after($string);

		$string = preg_replace_callback('#~([a-z0-9]{32})~#is', function ($data) {
			if (isset($GLOBALS['tmpSection'][$data[1]])) {
				return $GLOBALS['tmpSection'][$data[1]];
			}
			return $data[0];
		}, $string);
		return $string;
	}

	private function protect_code($string)
	{
		return preg_replace_callback('#<\?php(.*?)\?>#is', function ($data) {
			$key = md5($data[0]);
			$GLOBALS['tmpSection'][$key] = $data[0];
			return '~' . $key . '~';
		}, $string);
	}

	private function _before($string)
	{
		if (preg_match('#<!--\s*//\s*compress\s*on[^>]*-->#', $string)) {
			$this->compress = true;
		} else {
			$this->compress = false;
		}

		if (!defined('APPNAME')) {
			//处理dedecms标签
			$theme = $GLOBALS['G']['site']['template'];
			$theme_info = get_theme_info($theme);
			if ($theme_info['engine'] == 'dedecms') {
				$string = dede::dedehandle($string);
			}
			//兼容老模版，site_code已自动注入
			$string = preg_replace('#\{\$conf\[site_code\]\}#i', '', $string);
			$string = preg_replace('#<body[^>]*?>#i', '$0' . "\r\n" . '{$conf[site_code]}', $string);
		}

		$string = preg_replace_callback("/\<\?php.+?\?\>/is", function ($data) {
			return php_protect($data);
		}, $string);

		$string = str_replace('__APPROOT__', '/{echo APPNAME}', $string);
		return $string;
	}

	private function _after($string)
	{
		//释放PHP保护
		$string = preg_replace_callback('#~([a-z0-9]{16})~#is', function ($mt) {
			if (isset($GLOBALS['php_protect'][$mt[1]])) {
				return $GLOBALS['php_protect'][$mt[1]];
			}
			return $mt[0];
		}, $string);

		//兼容织梦目录
		$string = preg_replace('#\/plus\/([a-z0-9]*)\.php#', '/plus/$1', $string);

		//解析替换图片服务器路径
		$string = preg_replace('#(\.\.\/){1,}static\/#', '/static/', $string);

		//html压缩
		if ($this->compress) {
			$string = tag::compress_html($string);
		}

		$_afterString = '<?php !defined("ROOT") && die(); if(isset($this)) foreach(get_class_vars(get_class($this)) as $__k => $__tmp){ if(!isset($$__k)){ $$__k = $this->$__k; } } ?>';
		if (!defined('APPNAME')) {
			$_afterString .= '<?php if(isset($GLOBALS)) foreach($GLOBALS as $__k => $__G){ if(!isset($$__k)){ $$__k = $__G; } } ?>';
		}
		$string = $_afterString . $string;
		return trim($string);
	}

	public function loaded_tpl()
	{
		return self::$tpls;
	}
}

function php_protect($data)
{
	$protect_key =  substr(md5($data[0]), 10, 16);
	$GLOBALS['php_protect'][$protect_key] = $data[0];
	$string = '~' . $protect_key . '~';
	return $string;
}
