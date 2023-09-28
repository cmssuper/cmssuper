<?php

if (!defined('IN_SYS')) exit('Access Denied');

class theme_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$themes = get_themes();
		foreach ($themes as $k => $v) {
			if (is_file(ROOT . "/templates/{$k}/preview.png")) {
				$themes[$k]['preview'] = "/templates/{$k}/preview.png";
			} else {
				$themes[$k]['preview'] = "";
			}
		}
		self::json(array('success' => $themes));
	}

	public function del()
	{
		$themeName = gp('themeName');
		if ($themeName == '' || strpos($themeName, '..') !== false) return;
		$tplRoot = ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		$tplDir = $tplRoot . $themeName;
		$cacheDir = DATA . '/tplcache/' . $themeName;
		helper::delDir($tplDir);
		helper::delDir($cacheDir);
		self::json(array('success' => true));
	}

	public function loadDir()
	{
		$dir = gp('dir');
		$tplRoot = ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
		$tplDir = $tplRoot . $dir . DIRECTORY_SEPARATOR;
		$resp = array();
		$def_names = array(
			'index.htm' => '首页模版',
			'list_article.htm' => '列表页模版',
			'article_article.htm' => '文章页模版',
			'taglist.htm' => '标签页面',
			'search.htm' => '标签页面',
			'head.htm' => '页面头部',
			'header.htm' => '页面头部',
			'foot.htm' => '页面尾部',
			'footer.htm' => '页面尾部',
			'preview.png' => '预览图',
			'theme.xml' => '配置文件',
		);
		$dirs = glob($tplDir . '*');
		foreach ($dirs as $k => $dir) {
			$filename = str_replace($tplDir, '', $dir);
			if (isset($def_names[$filename])) {
				$filename = $def_names[$filename];
			} else {
				$filemname = preg_replace('#_m\.htm#', '.htm', $filename);
				if (isset($def_names[$filemname])) {
					$filename = $def_names[$filemname] . '(手机版)';
				}
			}
			$filedir = str_replace($tplRoot, '', $dir);
			$filedir = str_replace('\\', '/', $filedir);
			$isfile = !is_dir($dir);
			$resp[] = array('label' => $filename, 'dir' => $filedir, 'isfile' => $isfile);
		}
		self::json($resp);
	}

	public function loadTplBody()
	{
		$file = ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . gp('file');
		if (strpos($file, '..') !== false) return;
		self::json(array('success' => trim(file_get_contents($file))));
	}

	public function tplSave()
	{
		$theme = gp('theme');
		$file = gp('file');
		$body = stripslashes(gp('body', -1));
		$filepath = ROOT . '/templates' . $file;
		file_put_contents($filepath, $body);
		//修改模版引擎需要更新缓存
		$tplcache = DATA . '/tplcache/' . $theme . "/*";
		$tpls = glob($tplcache);
		foreach ($tpls as $tpl) {
			@unlink($tpl);
		}
		self::json(array('success' => true, 'tip' => '保存成功'));
	}
}
