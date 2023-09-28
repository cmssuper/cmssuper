<?php

if (!defined('IN_SYS')) exit('Access Denied');

class system_controller extends admincp
{

	public function config()
	{
		if (IS_POST) {
			$data = gp("sitemap,spider,downPicture,baidu_tui_token,speed");
			$data['site_code'] = gp('site_code', -1);
			foreach ($data as $key => $value) {
				if (isset($GLOBALS['G'][$key])) {
					db::query("update config set varValue='$value' where varName='$key' ");
				}
			}
			self::json(array('success' => true, 'tip' => '设置已保存'));
		} else {
			$r = db::select("select * from config");
			$var = array();
			foreach ($r as $key => $value) {
				$var[$value['varName']] = $value['varValue'];
			}
			self::json(array('success' => $var));
		}
	}

	public function upcache()
	{
		helper::delDir(DATA . '/tplcache');
		self::json(array('success' => true, 'tip' => '缓存已更新'));
	}

}
