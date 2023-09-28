<?php
class tags_controller extends controller
{

	public $current = "index";
	public $class = "index";
	public $showtype = 'new';
	
	public function __construct()
	{
		parent::__construct();
		spiderLog::write($this->siteId);
	}

	public function index()
	{
		$title = '标签';
		$position = "<a href='/'>{$this->siteName}</a> > $title";
		$pagetitle = $title . '-' . $this->siteName;
		$keywords = $this->site['keywords'];
		$description = $this->site['description'];
		require $this->tpl('tag');
	}

	public function __call($method, $ages)
	{
		$page = 1;
		if (preg_match("#/tags/(\d*)(/(\d*)\.html)?#", $this->rest_uri, $mt)) {
			$tagid = $mt[1];
			if (!empty($mt[3])) {
				$page = $mt[3];
			}
		} else {
			$this->page_404();
		}

		$tag = db::find("select id,tagsname from tagslist where id='$tagid'");

		if (!$tag) {
			$this->page_404();
		}
		$GLOBALS['tagid'] = $tagid;

		$title = $tag['tagsname'];

		$position = "<a href='/'>{$this->siteName}</a> > $title";
		$pagetitle = $title . '-' . $this->siteName;
		$keywords = $this->site['keywords'];
		$description = $this->site['description'];

		require $this->tpl('taglist');
	}
}
