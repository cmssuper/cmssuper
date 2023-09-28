<?php

if (!defined('IN_SYS')) exit('Access Denied');

class sites_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$where = "";
		$kw = gp("kw");
		if ($kw) {
			$where = " where (yuming.name like '%$kw%' OR yuming.sitename like '%$kw%') ";
		}
		$pg = new page("select * from yuming $where order by id desc");
		$list = $pg->get_list(20);
		$total = $pg->get_total();
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}

	public function edit()
	{
		$id = gp('id');
		if (IS_POST) {
			$v = gp("name,sitename,siteTitle,logo,keywords,description,template");
			$v['name'] = preg_replace('#^(www\.|https:\/\/)#', '', trim($v['name']));
			if (!$id) {
				$res = db::find("select * from yuming where name='$v[name]'");
				if ($res) {
					self::json(array('info' => '此域名已经添加'));
				}
				$sid = siteInit($v);
				if ($sid) {
					$_SESSION['siteId'] = $sid;
					self::json(array('success' => true, 'tip' => '添加成功'));
				} else {
					self::json(array('error' => '-1', 'tip' => '添加失败，超过授权数量'));
				}
			} else {
				db::query("UPDATE yuming set name='$v[name]', sitename='$v[sitename]', logo='$v[logo]',siteTitle='$v[siteTitle]',keywords='$v[keywords]' , description='$v[description]',template='$v[template]' where id='$id' ");
				$this->delLogoCache($id);
				self::json(array('success' => true, 'tip' => '修改成功'));
			}
		} elseif ($id) {
			$r = db::find("select * from yuming where id='$id' ");
			self::json(array('success' => $r));
		}
	}

	public function get_themes()
	{
		$themes = get_themes();
		self::json(array('success' => $themes));
	}

	public function multiSave()
	{
		$data = gp('data');
		$succIndexs = array();
		$errIndexs = array();
		if (!empty($data)) {
			foreach ($data as $k => $v) {
				$v['name'] = isset($v['name']) ? addslashes(preg_replace('#^(www\.|https:\/\/)#', '', trim($v['name']))) : '';
				$v['sitename'] = isset($v['sitename']) ? addslashes($v['sitename']) : '';
				$v['template'] = isset($v['template']) ? addslashes($v['template']) : '';
				$v['siteTitle'] = isset($v['siteTitle']) ? addslashes($v['siteTitle']) : '';
				$v['keywords'] = isset($v['keywords']) ? addslashes($v['keywords']) : '';
				$v['description'] = isset($v['description']) ? addslashes($v['description']) : '';
				if (empty($v['name']) || empty($v['sitename']) || empty($v['template'])) {
					$errIndexs[] = $k;
					continue;
				}
				$res = db::find("select * from yuming where name='$v[name]'");
				if ($res) {
					$succIndexs[] = $k;
					continue;
				}
				if (!siteInit($v)) {
					$errIndexs[] = $k;
					continue;
				}
				$succIndexs[] = $k;
			}
		}
		self::json(array('success' => array('successIndexs' => $succIndexs, 'errorIndexs' => $errIndexs)));
	}

	public function del()
	{
		$id = gp('id');
		$ym = db::find("select * from yuming where id = '$id'");
		if ($ym) {
			helper::delDir(DATA . '/content/' . $id);
			db::query("delete from classlist where yuming_id ='$id'");
			db::query("delete from ads where yuming_id ='$id'");
			db::query("delete from crawler where yuming_id ='$id'");
			db::query("delete from crawllinks where yuming_id ='$id'");
			db::query("delete from flink where yuming_id ='$id'");
			db::query("delete from reword where yuming_id ='$id'");
			db::query("delete from seoconfig where yuming_id ='$id'");
			db::query("delete from tagindex where siteId ='$id'");
			db::query("delete from yuming where id='$id' ");
			db::query("drop table `content_{$id}` ");
			$this->delLogoCache($id);
			self::json(array('success' => true));
		}
	}

	// 手机站开关
	public function mobileSwitch()
	{
		$id = gp("id");
		$mobileSwitch = gp("mobileSwitch");
		db::query("update yuming set mobileSwitch='$mobileSwitch' where id='$id' ");
		self::json(array('success' => true));
	}

	//删除logo缓存
	public function delLogoCache($id)
	{
		$file = ROOT . '/uploads/logo/' . $id . '.png';
		if (is_file($file)) unlink($file);
	}
}
