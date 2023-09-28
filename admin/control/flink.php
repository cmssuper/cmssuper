<?php

if (!defined('IN_SYS')) exit('Access Denied');

class flink_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$kw = gp("kw");
		$where = " where (yuming_id=0 or yuming_id='$this->siteId') ";
		if ($kw) {
			$where .= " and sitename like '%$kw%'";
		}
		$pg = new page("select * from flink {$where} order by id desc");
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
			$v = gp("sitename,url,note,status,yuming_id");
			if (!$id) {
				$time = time();
				db::query("INSERT into flink set sitename='$v[sitename]', url='$v[url]', note='$v[note]' ,status='$v[status]',yuming_id='$v[yuming_id]',addtime='$time' ");
			} else {
				db::query("UPDATE flink set sitename='$v[sitename]', url='$v[url]', note='$v[note]' ,status='$v[status]',yuming_id='$v[yuming_id]' where id='$id' ");
			}
			self::json(array('success' => true, 'tip' => '保持成功', 'redirect' => '/flink'));
		} else {
			if ($id) {
				$r = db::find("select *  from flink  where id='$id' ");
			} else {
				$r = new stdClass();
			}
			self::json(array('success' => $r));
		}
	}

	public function del()
	{
		$id = gp('id');
		db::query("delete from flink where id='$id' ");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}
}
