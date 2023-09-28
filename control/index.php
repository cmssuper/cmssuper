<?php

class index_controller extends controller
{

	public $current = 'index';
	public $class = 'index';
	public $showtypes = array('new' => '最新', 'hot' => '热点', 'photo' => '图文');

	public function __construct()
	{
		parent::__construct();
		spiderLog::write($this->siteId);
	}

	public function index()
	{
		$typeid = 0;
		$typename = "首页";
		
		if (preg_match('#/hot#', $this->rest_uri)) {
			$showtype = 'hot';
		} elseif (preg_match('#/photo#', $this->rest_uri)) {
			$showtype = 'photo';
		} else {
			$showtype = 'new';
		}

		$keywords = $this->site['keywords'];
		$description = $this->site['description'];
		$position = "<a href='/'>{$this->siteName}</a> > 首页";
		$title = $pagetitle = ($this->site['siteTitle'] ? $this->site['siteTitle'] : $this->site['sitename']);
		require $this->tpl('index');
	}

	public function __call($method, $ages)
	{
		if(!isset($this->showtypes[$method])){
			self::page_404();
		}
		$this->index();
	}
}
