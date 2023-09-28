<?php

if (!defined('IN_SYS')) exit('Access Denied');

class classlist_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$list = db::select("select c.* from classlist as c where c.yuming_id='" . $this->siteId . "' order by c.weight,id");
		self::json(array('success' => $list));
	}

	public function edit()
	{
		$id = gp('id');
		if (IS_POST) {
			$v = gp("title,ename");
			if (empty($v['title']) || empty($v['ename'])) {
				self::json(array('error' => -1, 'tip' => '请填写栏目名称和英文标记'));
			}
			$check = db::find("select * from classlist where yuming_id='{$this->siteId}' and id<>'$id' and ename='$v[ename]' ");
			if ($check) {
				self::json(array('error' => '-1', 'tip' => '栏目名称或标记已存在！'));
			}
			if (!validate::alpha($v['ename'])) {
				self::json(array('error' => '-1', 'tip' => '栏目目录存在不允许的字符'));
			}
			if ($id) {
				db::query("update classlist set ename='$v[ename]', title='$v[title]' where id='$id'");
			} else {
				db::query("insert into classlist (yuming_id, ename, title, weight, status ) 
				values ('{$this->siteId}','$v[ename]', '$v[title]', '99999', '1')");
			}
			self::json(array('success' => '修改成功', 'redirect' => '/classlist'));
		} else {
			if ($id) {
				$r = db::find("select * from classlist where id='$id' ");
			} else {
				$r = new stdClass();
			}
			self::json(array('success' => $r));
		}
	}

	public function del()
	{
		$yuming_id = $this->siteId;
		$id = gp('id');
		$class = db::getField("select ename from classlist where id='$id'");
		db::query("delete from content_{$yuming_id} where class='$class'");
		db::query("delete from classlist where id='$id' ");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}

	public function stop()
	{
		$id = gp('id');
		db::query("update classlist set status=0,weight='999' where id='$id' ");
		self::json(array('success' => true, 'tip' => '栏目已禁用'));
	}

	public function open()
	{
		$id = gp('id');
		db::query("update classlist set status=1 where id='$id' ");
		self::json(array('success' => true, 'tip' => '栏目已启用'));
	}

	public function weight()
	{
		$data = gp('data');
		$list = db::select("select id,weight from classlist where yuming_id='{$this->siteId}'", 'id');
		foreach ($data as $v) {
			if ($v['weight'] != $list[$v['id']]['weight']) {
				$v['weight'] = addslashes($v['weight']);
				$v['id'] = addslashes($v['id']);
				db::query("update classlist set weight='$v[weight]' where id='$v[id]' ");
			}
		}
		self::json(array('success' => true));
	}
}
