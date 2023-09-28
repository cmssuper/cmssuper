<?php

if (!defined('IN_SYS')) exit('Access Denied');

class seo_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function reword()
	{
		$pg = new page("select * FROM reword where yuming_id=0 OR yuming_id='{$this->siteId}' order by id desc");
		$list = $pg->get_list(20);
		$total = $pg->get_total();
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}

	public function reword_del()
	{
		$yuming_id = $this->siteId;
		$id = gp('id');
		db::query("delete from reword where id='$id'");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}

	public function reword_delall()
	{
		db::query("delete from reword");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}

	public function reword_add()
	{
		$count = db::getField("select count(*) from reword where yuming_id=0 OR yuming_id='{$this->siteId}'");
		if (IS_POST) {
			if ($count >= 8000) {
				self::json(array('error' => "-1", 'tip' => '该站点同义词已达上限，为了不影响服务器正常运转，最多只能添加8000个！'));
			}
			$v = gp('yuming_id,oldword,newword,type');
			$addtime = time();
			db::query("insert into reword (oldword, newword, type, yuming_id, addtime) values ('$v[oldword]','$v[newword]','$v[type]','$v[yuming_id]','$addtime')");
			self::json(array('success' => true, 'tip' => '保存成功', 'redirect' => '/seo/reword'));
		}
	}

	//导入同义词替换文件
	public function reword_upload()
	{
		$upSiteId = gp('upSiteId');
		$time = time();
		$config = array(
			"pathFormat" => "/data/temp/{time}{rand:6}",
			"maxSize" => 2048000,
			"allowFiles" => array('.txt'),
		);
		$up = new uploader('file', $config);
		$data = $up->getFileInfo();
		$count = db::getField("select count(*) from reword where yuming_id='{$this->siteId}'");
		$file = ROOT . $data['location'];
		$content = file_get_contents($file);
		unlink($file);
		$array = explode("\n", $content);
		$insertCount = 8000 - $count;
		$insertCount = count($array) < $insertCount ? count($array) : $insertCount;
		for ($i = 0; $i < $insertCount; $i++) {
			$str_word = $array[$i];
			if (strstr($str_word, ',')) {
				$str = explode(',', trim($str_word));
				$r = db::find("select * from reword where oldword='$str[0]' and newword='$str[1]' and `type`=2 and (yuming_id=0 || yuming_id='{$upSiteId}')");
				if (!$r) {
					db::query("insert into reword (oldword,newword,`type`,addtime,yuming_id) values ('$str[0]','$str[1]',2,'$time','{$upSiteId}')");
				}
			} elseif (strstr($str_word, '->')) {
				$str = explode('->', trim($str_word));
				$r = db::find("select * from reword where oldword='$str[0]' and newword='$str[1]' and `type`=1 and (yuming_id=0 || yuming_id='{$upSiteId}')");
				if (!$r) {
					db::query("insert into reword (oldword,newword,`type`,addtime,yuming_id) values ('$str[0]','$str[1]',1,'$time','{$upSiteId}')");
				}
			}
		}
	}

	//文章标题随机插入关键词
	public function keyword()
	{
		if (IS_POST) {
			$v = gp("seoWordNum,seoWordScale,seoTitlex,seoTitle");
			$v['seoWord'] = gp('seoWord', false);
			$r = db::find("select * from seoconfig where yuming_id='{$this->siteId}'");
			if ($r) {
				db::query("update seoconfig set seoWordNum='$v[seoWordNum]',seoWordScale='$v[seoWordScale]',seoTitlex='$v[seoTitlex]',seoTitle='$v[seoTitle]',seoWord='$v[seoWord]' where yuming_id='{$this->siteId}'");
			} else {
				db::query("insert into seoconfig (seoWordNum,seoWordScale,seoTitlex,seoTitle,seoWord,yuming_id) values('$v[seoWordNum]','$v[seoWordScale]','$v[seoTitlex]','$v[seoTitle]','$v[seoWord]','{$this->siteId}')");
			}
			self::json(array('success' => true, 'tip' => '更新成功'));
		} else {
			$r = db::find("select * from seoconfig where yuming_id='{$this->siteId}'");
			if (!$r) {
				$r = new stdClass();
			}
			self::json(array('success' => $r));
		}
	}
}
