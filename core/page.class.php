<?php

/**
 * @version    $Id: page.class.php 1 2016-07-19 08:45:53Z tic $
 */

class page
{

	public $sql, $page, $pagesize, $start, $total;

	public function __construct($sql)
	{
		$this->sql = $sql;
	}

	public function set_total($num)
	{
		$this->total = $num;
	}

	public function set_page($num)
	{
		$this->page = $num;
	}

	public function get_list($pagesize)
	{
		$this->pagesize = intval($pagesize);
		if ($this->pagesize < 1) $$this->pagesize = 1;
		if ($this->pagesize > 1000) $$this->pagesize = 1000;
		if (!isset($this->page)) {
			$this->page = gp('page') ? intval(gp('page')) : 1;
		}
		if ($this->page <= 0) $this->page = 1;
		$this->start = ($this->page - 1) * $this->pagesize;
		$sql = $this->sql . " LIMIT {$this->start},{$this->pagesize}";
		return db::select($sql);
	}

	public function get_page($uritpl = null, $firsturi = null)
	{
		if (!isset($this->total)) {
			$this->total = $this->get_total();
		}
		$totalpage = ceil($this->total / $this->pagesize);
		$startpage = $this->page - 3 < 1 ? 1 : $this->page - 3;
		$endpage = $this->page + 3 > $totalpage ? $totalpage : $this->page + 3;

		$page = '';
		if ($totalpage > 1) {
			$page = '';
			if (IS_MOBILE_SITE) {
				if ($startpage > 1) $page .= $this->make_uri('1', 1, $uritpl, $firsturi);
				if ($startpage > 2) $page .= '...';
			}
			for ($i = $startpage; $i <= $endpage; $i++) {
				if ($i == $this->page) {
					$page .= '<span>' . $i . '</span>';
				} else {
					$page .= $this->make_uri($i, $i, $uritpl, $firsturi);
				}
			}
			if (IS_MOBILE_SITE) {
				if ($endpage < $totalpage - 1) $page .= '...';
				if ($endpage < $totalpage) $page .= $this->make_uri($totalpage, $totalpage, $uritpl, $firsturi);
			}
		}
		return $page;
	}

	private function make_uri($name, $page, $uritpl = null, $firsturi = null, $class = null)
	{
		if ($page == 1 && !is_null($firsturi)) {
			$uri = $firsturi;
		} else if (is_null($uritpl)) {
			$nowurl = preg_replace("#\?.*?$#", '', $_SERVER['REQUEST_URI']);
			$param = '';
			$_gp = array_merge($_GET, $_POST);
			foreach ($_gp as $_k => $_v) {
				if ($_k != 'page' && is_string($_v)) {
					$param .= $_k . '=' . stripslashes($_v) . '&';
				}
			}
			if ($page == 1) {
				$uri = $nowurl . '?' . trim($param, '&');
			} else {
				$uri = $nowurl . '?' . $param . 'page=' . $page;
			}
		} else {
			$uri = str_replace('{page}', $page, $uritpl);
		}
		$class = $class == null ? "" : ' class="' . $class . '"';
		return "<a href=\"{$uri}\" $class>{$name}</a>";
	}

	public function get_total()
	{
		$sql = preg_replace("#SELECT[ \r\n\t](.*?)[ \r\n\t]FROM#is", 'SELECT COUNT(*) AS cc FROM', $this->sql, 1);
		$sql = preg_replace("#ORDER[ \r\n\t]{1,}BY(.*)#is", '', $sql, 1);
		$rs = db::select($sql);
		if (empty($rs)) return 0;
		$count = count($rs);
		if ($count > 1) {
			return $count;
		} else {
			return $rs[0]['cc'];
		}
	}
}
