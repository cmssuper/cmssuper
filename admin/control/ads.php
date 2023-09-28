<?php

if (!defined('IN_SYS')) exit('Access Denied');

class ads_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$pagesize = gp('pagesize');
		$pg = new page("SELECT * from ads where yuming_id=0 order by id desc");
		$list = $pg->get_list($pagesize);
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
			$v = gp("abc,title,allowSpider,content,status", -1);
			$time = time();
			if (empty($v['abc'])) {
				self::json(array('error' => '-1', 'tip' => '广告标记不能为空！'));
			}
			if (!preg_match("#[a-z0-9]*#", $v['abc'])) {
				self::json(array('error' => '-1', 'tip' => '广告标记只能是字母和数字组合'));
			}
			if (!$id) {
				if (db::find("select * from ads where abc='$v[abc]'")) {
					self::json(array('error' => '-1', 'tip' => '广告标记已存在！'));
				}
				db::query("insert into ads (yuming_id,abc,title,allowSpider,content,addtime,status) 
				values (0,'$v[abc]','$v[title]', '$v[allowSpider]', '$v[content]','$time','$v[status]') ");
				$id = db::insert_id();
				self::json(array('success' => true, 'tip' => '添加成功', 'redirect' => '/ads'));
			} else {
				db::query("update ads set title='$v[title]', allowSpider='$v[allowSpider]', content='$v[content]' ,status='$v[status]' where id='$id' ");
				db::query("update ads set status='$v[status]' where abc='$v[abc]' ");
				self::json(array('success' => true, 'tip' => '修改成功', 'redirect' => '/ads'));
			}
		} else {
			if ($id) {
				$r = db::find("select * from ads where yuming_id=0 and id='$id' ");
				$r['list'] = db::select("SELECT * from ads where yuming_id>0 and abc='$r[abc]' order by id asc");
			} else {
				$r = array(
					'allowSpider' => '1',
					'list' => array()
				);
			}
			self::json(array('success' => $r));
		}
	}

	public function del()
	{
		$id = gp('id');
		$abc = db::getfield("select abc from ads where id='$id'");
		db::query("delete from ads where abc='$abc' ");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}

	public function subedit()
	{
		if (IS_POST) {
			$v = gp("yuming_ids,abc,content,status", -1);
			$time = time();
			foreach ($v['yuming_ids'] as $yuming_id) {
				if (db::find("select id from ads where yuming_id='$yuming_id' AND abc='$v[abc]' ")) {
					db::query("update ads set yuming_id='$yuming_id', content='$v[content]', status='$v[status]' where yuming_id='$yuming_id' AND abc='$v[abc]'  ");
				} else {
					db::query("insert into ads (yuming_id,abc,content,addtime,status) 
					values ('$yuming_id','$v[abc]', '$v[content]','$time', '$v[status]') ");
				}
			}
			self::json(array('success' => true));
		} else {
			$abc = gp('abc');
			$r = db::select("select ads.id,ads.yuming_id,ads.content,yuming.name from ads left join yuming ON ads.yuming_id=yuming.id where ads.yuming_id>0 and ads.abc='$abc' ");
			self::json(array('success' => $r));
		}
	}

	public function subdel()
	{
		$id = gp('id');
		db::query("delete from ads where id='$id' ");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}
}
