<?php

if (!defined('IN_SYS')) exit('Access Denied');

class tags_controller extends admincp
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
			$where = " where tagsname like '%$kw%'  ";
		}
		$pg = new page("select * from tagslist $where order by id desc");
		$list = $pg->get_list(20);
		$total = $pg->get_total();
		if ($list) {
			$ids = array_column($list, 'id');
			$idstr = join(',', $ids);
			$nums = db::select("select tagid,count(*) as num from tagindex where tagid IN ($idstr) group by tagid");
			$nums = array_column($nums, 'num', 'tagid');
			foreach ($list as $k => $v) {
				$list[$k]['num'] = isset($nums[$v['id']]) ? $nums[$v['id']] : 0;
			}
		}
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}

	public function del()
	{
		$id = gp('id');
		db::query("delete from tagslist where id='$id' ");
		db::query("delete from tagindex where tagid='$id' ");
		self::json(array('success' => true, 'tip'=>'删除成功'));
	}
}
