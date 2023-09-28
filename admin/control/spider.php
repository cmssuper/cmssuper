<?php

if (!defined('IN_SYS')) exit('Access Denied');

class spider_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$spider_types = spiderLog::spider_types();
		$dtime = time() - 24 * 3600;
		$list = db::select("select * from spiderstats where addtime>$dtime");
		$xTime = array_keys(array_column($list, 'id', 'daytime'));

		$xAxis = array();
		foreach ($xTime as $v) {
			$xAxis[] = date("H:i", strtotime($v . ':00:00'));
		}

		$nlist = array();
		foreach ($list as $d => $v) {
			$nlist[$v['spider']][$v['daytime']] = $v['count'];
		}

		$series = array();
		foreach ($spider_types as $sk => $sv) {
			$vdata = array();
			foreach ($xTime as $x) {
				$vdata[] = isset($nlist[$sk][$x]) ? $nlist[$sk][$x] : 0;
			}
			$series[] = array(
				'name' => $sv,
				'type' => 'line',
				'data' => $vdata
			);
		}
		self::json(array('success' => array('xAxis' => $xAxis, 'series' => $series)));
	}

	public  function log()
	{
		$pagesize = max(gp('pagesize'), 100);
		$pg = new page("select * from spiderlog order by id desc");
		$list = $pg->get_list($pagesize);
		$total = $pg->get_total();
		self::json(array('success' => array(
			'list' => $list,
			'total' => $total,
		)));
	}
}
