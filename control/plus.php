<?php

class plus_controller extends controller
{

	public $current, $class;
	public $showtypes = array('new' => '最新', 'hot' => '热点', 'photo' => '图文');
	public $showtype = 'new';

	public function __construct()
	{
		parent::__construct();
	}

	private function category()
	{
		spiderLog::write($this->siteId);
		$this->current = $this->class = M;

		$ename = $typeid = M;
		$title = $typename = $this->nav[M]['title'];

		$addSql =  " and class = '$typeid' ";
		$orderBy = " order by id desc";
		if (preg_match('#/hot#', $this->rest_uri)) {
			$this->showtype = 'hot';
			$orderBy = " order by click desc,id desc";
		} elseif (preg_match('#/photo#', $this->rest_uri)) {
			$this->showtype = 'photo';
			$addSql .=  " and thumb<>'' ";
		}

		if (IS_AJAX) {
			$page = 1;
			if (preg_match("#/[^/]*/list_(\d*).html#si", $this->rest_uri, $mt)) {
				$page = $mt[1];
			}
			$pagesize = gp('pagesize') ? min(100, intval(gp('pagesize'))) : 20;
			$offset = ($page - 1) * $pagesize;
			$data = db::select("select * from `content_{$this->siteId}` where status = 1 {$addSql} {$orderBy} limit {$offset},{$pagesize}");
			$this->json($data);
		} else {
			$keywords = $typename;
			$description = $typename;
			$position = "<a href='/'>{$this->siteName}</a> > <a href='/{$typeid}'>{$typename}</a>";
			$pagetitle = $typename . '-' . $this->siteName;
			require $this->tpl('list_article');
		}
	}

	public function sitemap()
	{
		if ($GLOBALS['G']['sitemap'] == 1) {
			header("Content-Type:text/xml");
			$list = db::select("select * from `content_{$this->siteId}` where status = 1 order by id desc limit 1000");
			$str  = '<urlset xmlns = "http://www.sitemaps.org/schemas/sitemap/0.9">';
			$items  = "";
			foreach ($list as $v) {
				$items .= "<url>
				<loc>" . $this->baseHost . '/' . $v['class'] . '/' . $v['id'] . ".html</loc>
				<lastmod>" . date('Y-m-d', $v['addtime']) . "</lastmod>
				<changefreq>daily</changefreq>
				<priority>0.9</priority>\n</url>";
			}
			echo $str . $items . "</urlset>";
		} else {
			$this->page_404();
		}
	}

	public function __call($method, $ages)
	{
		if (in_array($this->rest_uri, array('/new', '/hot', '/photo', '/tu'))) {
			$this->category();
		} elseif (preg_match("#^/[^/]+/(\d+)\.html#", $this->rest_uri, $mt)) {
			$this->view($mt[1]);
		} elseif (isset($this->nav[M]) && preg_match("#^/[^/]+[/]?#", $this->rest_uri)) {
			$this->category();
		} else {
			self::page_404();
		}
	}

	//内容页
	private function view($id)
	{
		spiderLog::write($this->siteId);
		$field = db::find("select id,class,title,flag,source,thumb,keyword,description,status,click,addtime from `content_{$this->siteId}` where id='$id' ");
		if (empty($field)) {
			$this->page_404();
		} elseif ($field['status'] != 1) {
			throw new Exception("文章审核中，不能查看");
			exit;
		}
		db::query("update `content_{$this->siteId}` set click = click +1 where id = '$id'");

		$this->current = $this->class = $typeid = $field['class'];
		$field['typename'] = $this->nav[$typeid]['title'];
		$field['title'] = strip_tags($field['title']);
		$field['keywords'] = $field['keyword'];
		$field['description'] = strip_tags($field['description']);
		$field['body'] = $field['content'] = $field['text'] = arcRead($this->siteId, $field['id']);
		$field['writer'] = "admin";
		$field['typeurl'] = '/' . $typeid;
		$field['typelink'] = '<a href="' . $field['typeurl'] . '">' . $field['typename'] . '</a>';

		$pagetitle = $field['title'] . "-" . $field['typename'] . '-' . $this->siteName;
		$position = "<a href='/'>{$this->siteName}</a> > <a href='/{$typeid}'>{$field['typename']}</a> > <span>文章页</span>";

		extract($field);
		//兼容处理
		$pubdate = $pubtime = $addtime;
		$article_content = $field;
		require $this->tpl('article_article');
	}

	public function count()
	{
		$aid = gp('aid');
		$count = db::getfield("select click from `content_{$this->siteId}` where id='$aid' ");
		echo 'document.write(' . $count . ');';
	}

	public function search()
	{
		spiderLog::write($this->siteId);
		$typeid = 'search';
		$typename = "搜索";

		$keyword = trim(gp("keyword", true));
		$q = trim(gp("q", true));

		if (empty($keyword) && !empty($q)) {
			$keyword = $q;
		}

		$title = "搜索结果";
		$pagetitle = $title . '-' . $this->siteName;
		$keywords = $this->site['keywords'];
		$description = $this->site['description'];

		require $this->tpl('search');
	}
}
