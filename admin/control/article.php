<?php

if (!defined('IN_SYS')) exit('Access Denied');

class article_controller extends admincp
{
	public function __construct()
	{
		parent::__construct();
		if (empty($this->siteId)) {
			self::json(array('error' => '-1', 'tip' => '请先创建站点'));
		}
	}

	public function index()
	{
		$pagesize = gp('pagesize');
		$siteId = $this->siteId;
		$where = array();
		$kw = gp("kw");
		if ($kw) {
			$where[] = " c.title like '%$kw%' ";
		}
		$where = empty($where) ? "" : "where " . join(" AND ", $where);
		$pg = new page("select c.* , y.title as classname from content_{$siteId} as c left join classlist as y on c.class = y.ename and y.yuming_id={$siteId} $where order by c.id desc");

		$list = $pg->get_list($pagesize);
		$total = $pg->get_total();

		foreach ($list as $key => $r) {
			$list[$key]['url'] = "http://" . $this->sites[$siteId]['name'] . "/{$r['class']}/{$r['id']}.html";
		}
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}

	public function edit()
	{
		$type = gp('type'); //single/multi
		$id = gp('id');
		$data = gp("title,class,flag,thumb,keyword,description,tags,siteId,addtime,status");

		$siteId = $data['siteId'] ? $data['siteId'] : $this->siteId;
		$site = $this->sites[$siteId];

		$classlist = $this->get_classlist($siteId);
		if (IS_POST) {
			$data['body'] = gp("body", false);
			if (!$data['addtime']) {
				$data['addtime'] = time();
			} elseif ($data['addtime'] - time() > 60) {
				$data['status'] = 2;
			}
			if (!$id) {
				if (empty($classlist[$data['class']])) {	//如果不存在栏目，随机栏目
					if ($type == 'multi') {
						$classkeys = array_keys($classlist);
						$randkey = rand(0, count($classkeys));
						$data['class'] = $classkeys[$randkey];
					} else {
						self::json(array('error' => '-1', 'tip' => '请选择栏目'));
					}
				}
				db::query("insert into content_{$siteId} (title, class, flag, thumb, keyword, addtime, status, description ) 
				values ('$data[title]', '$data[class]', '$data[flag]', '$data[thumb]', '$data[keyword]', '$data[addtime]', '1', '$data[description]')");
				$id = db::insert_id();
			} else {
				db::query("update content_{$siteId} set title='$data[title]', class='$data[class]', flag='$data[flag]', thumb='$data[thumb]', keyword='$data[keyword]',description='$data[description]',addtime='$data[addtime]',status='$data[status]' where id='$id' ");
			}

			//存入标签
			$this->insert_tags($data['tags'], $siteId, $id);

			$data['body'] = stripslashes($data['body']);
			if (arcWrite($siteId, $id, $data['body'])) {
				//定时文章
				if ($data['status'] == 2) {
					if (isset($GLOBALS['G']['arcTimer'])) {
						$arcTimer = json_decode($GLOBALS['G']['arcTimer'], true);
					}
					$arcTimer = empty($arcTimer) ? array() : $arcTimer;
					$arcTimer[$id] = array('siteId' => $siteId, 'addtime' => $data['addtime']);
					$arcTimer = addslashes(json_encode($arcTimer));
					db::query("REPLACE INTO config (varName, varValue) values ('arcTimer', '$arcTimer')");
				}
				$url = "http://" . $site['name'] . "/{$data['class']}/{$id}.html";
				self::json(array('success' => true, 'url' => $url));
			} else {
				self::json(array('error' => '-1', 'tip' => '文章写入失败'));
			}
		} else {
			$r = db::find("select * from content_{$siteId} where id='$id' ");
			if(empty($r)){
				self::json(array('error'=>'noArt', 'redirect'=>'/article'));
			}
			$res = db::select("select t.tagsname from tagindex as i LEFT JOIN tagslist as t ON i.tagid=t.id where i.siteId='$siteId' and i.aid='$id'");
			$arr_tags = array();
			foreach ($res as $v) {
				$arr_tags[] = $v['tagsname'];
			}
			$r['tags'] = $arr_tags;
			$r['body'] = arcRead($siteId, $r['id']);
			self::json(array('success' => $r));
		}
	}

	public function  classlist()
	{
		$classlist = $this->get_classlist($this->siteId);
		self::json(array('success' => $classlist));
	}

	public function del()
	{
		$siteId = $this->siteId;
		$id = gp('id');
		if (is_array($id)) {
			foreach ($id as $v) {
				$this->del_handle($siteId, $v);
			}
		} else {
			$this->del_handle($siteId, $id);
		}
		self::json(array('success' => '删除成功'));
	}

	public function tags()
	{
		$kw = gp('kw');
		$res = db::select("select id,tagsname from tagslist where tagsname like '%$kw%' limit 10");
		self::json($res);
	}

	private function del_handle($siteId, $id)
	{
		arcDelete($siteId, $id);
		db::query("delete from content_{$siteId} where id='$id' ");
	}

	private function insert_tags($tags, $siteId, $content_id)
	{
		db::query("delete from tagindex where siteId='$siteId' AND aid='$content_id'");
		if (!empty($tags)) {
			$time = time();
			$tagstr = implode("','", $tags);
			$res = db::select("select id,tagsname from tagslist where tagsname in ('$tagstr') ");
			$oldtags = array();
			foreach ($res as $v) {
				$oldtags[] = $v['tagsname'];
			}
			foreach ($tags as $key => $value) {
				if (!in_array($value, $oldtags)) {
					db::query("insert into tagslist (tagsname,addtime) values('$value', '$time') ");
				}
			}
			db::query("insert into tagindex (aid,siteId,tagid) select '$content_id','$siteId',id from tagslist where tagsname in ('$tagstr')");
		}
	}

	private function get_classlist($siteId)
	{
		$classlist = array();
		if ($siteId) {
			$res = db::select("select c.* from classlist as c where c.status=1 and c.yuming_id='" . $siteId . "' order by c.weight,id");
			foreach ($res as $v) {
				$classlist[$v['ename']] = $v;
			}
		}
		return $classlist;
	}
}
