<?php

function super__autoload($class)
{
	if (is_file(__DIR__ . DS . $class . '.class.php')) {
		include __DIR__ . DS . $class . '.class.php';
	}
}

function isspider()
{
	static $spiders = 'Bot|Crawl|Spider';
	if (empty($_SERVER['HTTP_USER_AGENT'])) return false;
	if (preg_match("/($spiders)/i", $_SERVER['HTTP_USER_AGENT'])) {
		return true;
	} else {
		return false;
	}
}

function gp($key, $filter = true)
{
	if (strpos($key, ',') !== false) {
		$keys = explode(',', $key);
		foreach ($keys as $key) $tmp[$key] = gp($key, $filter);
		return $tmp;
	}
	if (isset($_REQUEST[$key])) {
		$value = $_REQUEST[$key];
		if (is_string($filter) && !validate::$filter($value)) {
			$value = false;
		} elseif ($filter === true) {
			return safe_replace($value);
		} elseif ($filter === false) {
			return filter::xss($value);
		} else {
			return $value;
		}
	} else {
		return null;
	}
}

function safe_replace($string)
{
	if (empty($string)) return $string;
	if (is_array($string)) return array_map('safe_replace', $string);
	$string = str_replace('%20', '', $string);
	$string = str_replace('%27', '', $string);
	$string = str_replace('%2527', '', $string);
	$string = str_replace('*', '', $string);
	$string = str_replace('#', '', $string);
	$string = str_replace('%', '', $string);
	$string = str_replace(';', '', $string);
	$string = str_replace('&', '&amp;', $string);
	$string = str_replace('"', '&quot;', $string);
	$string = str_replace("'", '&#039;', $string);
	$string = str_replace('<', '&lt;', $string);
	$string = str_replace('>', '&gt;', $string);
	$string = str_replace("{", '', $string);
	$string = str_replace('}', '', $string);
	$string = str_replace('\\', '', $string);
	return $string;
}

function replace_word($list_rword, $body)
{
	foreach ($list_rword as $r) {
		if ($r['type'] == 1) {
			$body = str_replace($r['oldword'], $r['newword'], $body);
		} elseif ($r['type'] == 2) {
			$body = str_replace($r['oldword'], '#@*@#', $body);
			$body = str_replace($r['newword'], $r['oldword'], $body);
			$body = str_replace('#@*@#', $r['newword'], $body);
		}
	}
	return $body;
}

function getScheme()
{
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) return $_SERVER['HTTP_X_FORWARDED_PROTO'];
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') return 'https';
	return 'http';
}

function get_url()
{
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
	return getScheme() . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

function get_client_ip()
{
	$ip = $_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) and preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach ($matches[0] as $xip) {
			if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
				$ip = $xip;
				break;
			}
		}
	}
	return $ip;
}

function cut($string, $length, $dot = '')
{
	$string = strip_tags($string);
	if (strlen($string) <= $length) {
		return $string;
	}
	$pre = chr(1);
	$end = chr(1);
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;', '&nbsp;'), array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end, $pre . ' ' . $end), $string);
	$strcut = mb_substr($string, 0, $length, 'utf-8');
	$strcut = str_replace(array($pre . '&' . $end, $pre . '"' . $end, $pre . '<' . $end, $pre . '>' . $end, $pre . ' ' . $end), array('&amp;', '&quot;', '&lt;', '&gt;', '&nbsp;'), $strcut);
	$pos = strrpos($strcut, chr(1));
	if ($pos !== false) {
		$strcut = substr($strcut, 0, $pos);
	}
	return $strcut . $dot;
}

function get_themes()
{
	$dir = ROOT . "/templates";
	$handle = opendir($dir);
	$themes = array();
	while ($file = readdir($handle)) {
		if ($file != "." && $file != ".." && is_file($dir . '/' . $file . '/index.htm')) {
			$themes[$file] = get_theme_info($file);
		}
	}
	uksort($themes, function ($a, $b) {
		if (strpos($b, "default") === 0) {
			return true;
		}
	});
	closedir($handle);
	return $themes;
}

function get_theme_info($name)
{
	$configXml = ROOT . '/templates/' . $name . '/yumingcms.xml';
	$theme = ROOT . '/templates/' . $name . '/theme.xml';
	if (is_file($configXml)) {
		rename($configXml, $theme);
	}
	if (is_file($theme)) {
		$xmlstring = file_get_contents($theme);
	} else {
		$xmlstring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<root>\r\n<name>未命名模版</name>\r\n<engine>default</engine>\r\n</root>";
		file_put_contents($theme, $xmlstring);
	}
	$xml = new SimpleXMLElement($xmlstring);
	$xml = json_decode(json_encode($xml), true);
	$xml['name'] = !isset($xml['name']) ? $name : $xml['name'];
	return $xml;
}

function arcRead($siteId, $id)
{
	$id = intval($id);
	$folderId = floor($id / 1000) * 1000;
	$filepath = DATA . "/content/$siteId/$folderId/$id.txt";
	$body = "";
	if (is_file($filepath)) {
		$body = file_get_contents($filepath);
	}
	return $body;
}

function arcWrite($siteId, $id, $body)
{
	$id = intval($id);
	$folderId = floor($id / 1000) * 1000;
	$filepath = DATA . "/content/$siteId/$folderId/$id.txt";
	$filedir = dirname($filepath);
	if (!is_dir($filedir)) {
		mkdir($filedir, 0777, true);
	}
	if (file_put_contents($filepath, $body)) {
		return true;
	}
	return false;
}

function arcDelete($siteId, $id)
{
	$id = intval($id);
	$folderId = floor($id / 1000) * 1000;
	$filepath = DATA . "/content/$siteId/$folderId/$id.txt";
	if (is_file($filepath) && @unlink($filepath)) {
		return true;
	}
	return false;
}

function convert_images_url_path($url, $link = '', $yuming_id = '')
{
	if ($link) {
		$url = htmlHelper::fix_path($link, $url);
	}
	$img_ext = pathinfo($url, PATHINFO_EXTENSION);
	$md5 = md5($url);
	$md51 = substr($md5, 0, 2);
	$md52 = substr($md5, 2, 2);
	$md53 = substr($md5, 10, 16);
	$hash = $md51 . $md52 . $md53;
	if (in_array($img_ext, array('jpg', 'jpeg', 'png', 'gif'))) {
		$img_ext = "." . $img_ext;
	} elseif ($img_ext != '') {
		return "";
	}

	if ($yuming_id) {
		$str_img = "/uploads/{$yuming_id}/" . $md51 . "/" . $md52 . "/" . $md53 . $img_ext;
	} else {
		$str_img = "/uploads/attachment/" . $md51 . "/" . $md52 . "/" . $md53 . $img_ext;
	}

	if (!is_file(ROOT . $str_img)) {
		$imgcon = db::find("select * from attachment where hash='$hash'");
		if (empty($imgcon)) {
			$now_time = time();
			db::query("insert into attachment (hash,url,addtime) values ('$hash', '$url', '$now_time')");
		}
	}
	return $str_img;
}

function get_config()
{
	$r = db::select("select * from config");
	foreach ($r as $value) {
		$GLOBALS['G'][$value['varName']] = $value['varValue'];
	}
	return $GLOBALS['G'];
}

function site($hostName)
{
	$GLOBALS['G']['site'] = db::find("select * from yuming where name='www.{$hostName}' OR name='{$hostName}'");
	if (empty($GLOBALS['G']['site'])) {
		global $ishowA;
		if (empty($ishowA)) {
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			echo '<style>html,body{margin:0;}</style><title>cmsSuper</title><div style="background:rgb(18, 107, 174);color:#FFF;text-align:center;font-size:24px;padding:100px;">请登录管理后台 > 创建网站</div>';
			$ishowA = true;
		}
		return false;
	}
	return $GLOBALS['G']['site'];
}

function siteInit($site)
{
	$site['logo'] = empty($site['logo']) ? '' : $site['logo'];
	db::query("INSERT into yuming set name='$site[name]', sitename='$site[sitename]', logo='$site[logo]', siteTitle='$site[siteTitle]',keywords='$site[keywords]' ,description='$site[description]', template='$site[template]',mobileSwitch=1");
	$sid = db::insert_id();
	$contentSql = "CREATE TABLE `content_{$sid}` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`class` varchar(30) DEFAULT NULL,
			`title` varchar(255) DEFAULT NULL,
			`flag` char(1) DEFAULT NULL,
			`source` varchar(50) DEFAULT '',
			`thumb` varchar(255) DEFAULT '',
			`keyword` varchar(255) DEFAULT '',
			`description` varchar(255) DEFAULT '',
			`status` tinyint(1) DEFAULT '0',
			`click` int(11) DEFAULT '0',
			`addtime` int(11) DEFAULT '0',
			`ping_status` tinyint(2) DEFAULT '0',
			`ping_errMsg` varchar(3) DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `flag` (`flag`),
			KEY `title` (`title`),
			KEY `addtime` (`addtime`),
			KEY `cfsa` (`class`,`flag`,`status`,`addtime`) USING BTREE,
			KEY `csa` (`class`,`status`,`addtime`) USING BTREE
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	db::query($contentSql);
	return $sid;
}

function curlpost($url, $data = array())
{
	$data['version'] = $GLOBALS['G']['softversion'];
	$data['http_host'] = $_SERVER["HTTP_HOST"];
	$data['path'] = ROOT;
	return httpUnit::post($url, $data);
}
