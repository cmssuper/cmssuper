<?php

if (!defined('IN_SYS')) exit('Access Denied');

class crawler_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function crawlKeywordData()
	{
		$data['sites'] = db::select("select id,name,sitename from yuming order by id desc");
		$class = db::select("select id,yuming_id,ename,title,crawlWords from classlist where status=1 order by weight");
		$siteClass = array();
		foreach ($class as $v) {
			$siteClass[$v['yuming_id']]['class'][] = $v;
			if (!empty($v['crawlWords'])) {
				$siteClass[$v['yuming_id']]['kwnum'] = isset($siteClass[$v['yuming_id']]['kwnum']) ? $siteClass[$v['yuming_id']]['kwnum'] + 1 : 1;
			}
		}
		foreach ($data['sites'] as $k => $site) {
			foreach ($siteClass as $x => $y) {
				if ($site['id'] == $x) {
					$data['sites'][$k] = array_merge($site, $y);
				}
			}
		}
		self::json($data);
	}

	public function crawlKeywordSave()
	{
		$id = gp('id');
		$crawlWords = gp('crawlWords');
		db::query("update classlist set crawlWords='$crawlWords' where id='$id' ");
		self::json(array('success' => true));
	}

	public function crawlRuleData()
	{
		$list = db::select("select id,yuming_id,class,listurl,updatetime,page,autoStart from crawler where yuming_id=0 or yuming_id='" . $this->siteId . "' order by id desc");
		self::json($list);
	}

	public function crawlRuleChange()
	{
		$id = gp('id');
		$autoStart = gp('autoStart');
		if ($id) {
			db::query("update crawler set autoStart='$autoStart' where id='$id'");
		} else {
			db::query("update crawler set autoStart='$autoStart'");
		}
	}

	public function crawlRuleEdit()
	{
		$id = gp('id');
		if (IS_POST) {
			$time = time();
			$gp = gp("yuming_id,class,listurl,articlerule,titlerule,contentrule,norule", -1);
			if ($gp['yuming_id'] == 0) {
				$gp['class'] = '';
			}
			if ($id) {
				db::query("update crawler set yuming_id='$gp[yuming_id]', class='$gp[class]', listurl='$gp[listurl]',articlerule='$gp[articlerule]' ,titlerule='$gp[titlerule]',contentrule='$gp[contentrule]' ,norule='$gp[norule]',updatetime='$time'  where id='$id' ");
				self::json(array('success' => true, 'tip' => '保存成功', 'redirect' => '/crawler?tab=crawlRule'));
			} else {
				db::query("insert into crawler (yuming_id,class,listurl,articlerule,titlerule,contentrule,norule,updatetime ) 
				values ('$gp[yuming_id]', '$gp[class]', '$gp[listurl]', '$gp[articlerule]','$gp[titlerule]','$gp[contentrule]','$gp[norule]','$time' )");
				self::json(array('success' => true, 'tip' => '保存成功', 'redirect' => '/crawler?tab=crawlRule'));
			}
		} else {
			$v = $id ? db::find("select * from crawler where id='$id' ") : new stdClass();
			self::json(array('success' => $v));
		}
	}

	public function crawlRuleDel()
	{
		$id = gp('id');
		db::query("delete from crawler where id='$id' ");
		self::json(array('success' => true, 'tip' => '删除成功'));
	}

	public function clearRuleLog()
	{
		$id = gp('id');
		db::query("delete from crawllinks where ruleid='$id' ");
		self::json(array('success' => true));
	}

	public function get_classlist()
	{
		$siteId = $this->siteId;
		$classlist = array();
		if ($siteId) {
			$res = db::select("select c.* from classlist as c where c.status=1 and c.yuming_id='" . $siteId . "' order by c.weight,id");
			foreach ($res as $v) {
				$classlist[$v['ename']] = $v;
			}
		}
		self::json(array('success' => $classlist));
	}

	public function AiNewsData()
	{
		$data['sites'] = db::select("select id,name,sitename from yuming order by id desc");
		$data['sites'] = array_column($data['sites'], null, 'id');
		$class = db::select("select id,yuming_id,ename,title from classlist where status=1 order by weight");
		foreach ($class as $v) {
			if (isset($data['sites'][$v['yuming_id']])) { //早期系统删除站点未删除栏目
				$data['sites'][$v['yuming_id']]['class'][] = $v;
			}
		}
		$data['sites'] = array_values($data['sites']);
		self::json($data);
	}

	public function AiNewsPost()
	{
		$g = gp('word,titleBetter,bodyLength,bodyBetter,sites', -1);
		$site = $g['sites'][mt_rand(0, count($g['sites']) - 1)];
		if (isset($site['class'])) {
			$class = $site['class'][mt_rand(0, count($site['class']) - 1)];
		} else {
			$class = db::getfield("select ename from classlist where yuming_id='$site[id]' order by rand() ");
		}
		$ai = new AiNews();
		$title = $ai->title($g['word'], $g['titleBetter']);
		$title = mb_substr($title, 0, 50, 'utf-8');
		$title_adds = addslashes($title);
		$body = $ai->body($g['word'], $g['bodyLength'], $g['bodyBetter']);
		$time = time();
		db::query("insert into content_{$site['id']} (title, class, addtime, status ) 
				values ('$title_adds', '$class', '$time', '1')");
		$id = db::insert_id();
		arcWrite($site['id'], $id, $body);
		self::json(array('success' => true, 'title' => $title));
	}
}
