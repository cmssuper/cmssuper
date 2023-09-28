<?php

if (!defined('IN_SYS')) exit('Access Denied');

class admin_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$pagesize = max(gp('pagesize'), 100);
		$pg = new page("select id,username,siteids,status from admin order by id asc");
		$list = $pg->get_list($pagesize);
		$total = $pg->get_total();
		foreach ($list as &$v) {
			$v['siteNum'] = count(array_filter(explode(',', $v['siteids'])));
		}
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}

	public function edit()
	{
		$id = gp('id');
		if ($this->user['id'] != 1) {
			$id = $this->user['id'];
		}
		if (IS_POST) {
			$username = gp('username');
			$password = gp('password');
			$siteids = gp('siteids');
			$siteids = $siteids ? join(',', $siteids) : '';
			$status = gp('status');
			if (empty($username)) {
				self::json(array('error' => '-1', 'tip' => '请输入用户名'));
			}
			if (!empty($password)) {
				$rand = rand(1000, 9999);
				$pwdHash = self::password_encode($username, $password, $rand);
			}
			if ($id) {
				if (!empty($password)) {
					db::query("update admin set password='$pwdHash',salt='$rand' where id= '$id' ");
				}
				if ($this->user['id'] == 1) {
					db::query("update admin set username='$username',siteids='$siteids',status='$status' where id= '$id' ");
				}
			} else if ($this->user['id'] == 1) {
				if (empty($password)) {
					self::json(array('error' => '-1', 'tip' => '请输入密码'));
				}
				db::query("insert into admin set username='$username',password='$pwdHash',salt='$rand',siteids='$siteids',status='$status' ");
			}
			self::json(array('success' => true, 'tip' => '修改成功', 'redirect' => '/admin'));
		} else {
			if (!$id) {
				$resp  = array();
			} else {
				$resp = db::find("select id,username,siteids,status from admin where id='$id'");
				$resp['siteids'] = !empty($resp['siteids']) ? explode(',', $resp['siteids']) : array();
			}
			self::json(array('success' => $resp));
		}
	}

	public function del()
	{
		$id = gp('id');
		if ($this->user['id'] != 1) {
			self::json(array('error' => "-1", 'tip' => "你没有权限"));
		}
		if ($id == 1) {
			self::json(array('error' => "-1", 'tip' => "创始人账号不能删除"));
		}
		db::query("delete from admin where id='$id' ");
		self::json(array('success' => true, 'tip' => "删除成功"));
	}
}
