<?php

function FormatScript($atme)
{
	return $atme == '&nbsp;' ? '' : $atme;
}

function MyDate($format = 'Y-m-d H:i:s', $timestamp = 0)
{
	$timestamp = empty($timestamp) ? time() : $timestamp;
	if (empty($format)) {
		$format = 'Y-m-d H:i:s';
	}
	return date($format, $timestamp);
}

function GetDateMk($mktime)
{
	if ($mktime == "0") return "暂无";
	else return MyDate("Y-m-d", $mktime);
}

function GetDateTimeMk($mktime)
{
	return MyDate('Y-m-d H:i:s', $mktime);
}

function cn_substr($str, $slen, $dot = '')
{
	return cut($str, $slen, $dot);
}

function GetCurUrl()
{
	if (!empty($_SERVER['REQUEST_URI'])) {
		$nowurl = str_replace('/index.php', '', $_SERVER['REQUEST_URI']);
		$nowurls = explode('?', $nowurl);
		$nowurl = $nowurls[0];
	} else {
		$nowurl = $_SERVER['PHP_SELF'];
	}
	return $nowurl;
}

function html2text($string)
{
	if (empty($string)) return $string;
	if (is_string($string)) {
		return strip_tags($string);
	} else {
		return array_map('html2text', $string);
	}
}

function GetTags($aid)
{
	$tagres = db::select("SELECT t.tagsname FROM `tagindex` as i,`tagslist` as t WHERE t.id=i.tagid and i.aid='$aid' ");
	$tags = '';
	foreach ($tagres as $v) {
		$tags = empty($tags) ? $v['tagsname'] : ',' . $v['tagsname'];
	}
	return $tags;
}

function RemoveXSS($val)
{
	return filter::xss($val);
}

/**************************************** 函数 End ****************************************/

//dede标签分析
function getTagAttr($tags)
{
	$vars = array();
	$vars_key_start = false;
	$vars_key_end = false;
	$vars_value_start = false;
	$vars_value_fag = false;
	$vars_value_str = '';

	$tags = trim($tags);
	$tags_strlen = strlen($tags);
	for ($i = 0; $i < $tags_strlen; $i++) {
		$tags_char = $tags[$i];
		$char_ord = ord($tags_char);
		// 检索key的位置
		if ($vars_key_end === false) {
			if ($char_ord >= 97 && $char_ord <= 122) {
				if ($vars_key_start === false) {
					$vars_key_start = $i;
				}
				continue;
			} elseif ($vars_key_start !== false) {
				$vars_key_end = $i;
			} else {
				continue;
			}
		}
		// 检索内容开始位置
		if ($vars_value_start === false) {
			if ($char_ord == 61) {
				$vars_value_start = $i;
			}
			continue;
		}
		// 检索内容标识符
		if ($vars_value_fag === false) {
			if ($char_ord != 32) {
				if ($char_ord == 34 || $char_ord == 39) {
					$vars_value_fag = $char_ord;
				} else {
					$vars_value_fag = -1;
					$vars_value_str = $tags_char;
				}
			}
			continue;
		}

		// 检索内容
		if ($vars_value_fag != -1) {
			if ($char_ord != $vars_value_fag) {
				$vars_value_str .= $tags_char;
				continue;
			}
		} else {
			if ($char_ord != 32) {
				$vars_value_str .= $tags_char;
				if ($i < $tags_strlen - 1) {
					continue;
				}
			}
		}
		$vars_key = substr($tags, $vars_key_start, $vars_key_end - $vars_key_start);
		$vars[$vars_key] = $vars_value_str;
		$vars_key_start = false;
		$vars_key_end = false;
		$vars_value_start = false;
		$vars_value_fag = false;
		$vars_value_str = '';
	}
	return $vars;
}

/*导航*/
function get_classlist()
{
	$siteId = $GLOBALS['G']['site']['id'];
	$res = db::select("select ename,title from classlist where yuming_id='{$siteId}' and status=1 order by weight,id");
	return array_column($res, null, 'ename');
}

function get_nav($arr = array())
{
	$result = array();
	$classlist = $GLOBALS['G']['nav'];

	$type = empty($arr['type']) ? '' : $arr['type'];
	$typeid = empty($arr['typeid']) ? '' : $arr['typeid'];

	$typeid_arr = array();
	if ($typeid && $type != 'top' && $type != 'all' && $typeid != 'top' && $typeid != 'all') {
		if (preg_match('#,#', $typeid)) {
			$typeid_arr = explode(',', $typeid);
		} else {
			$typeid_arr = array($typeid);
		}
	} else {
		$typeid_arr = array_keys($classlist);
	}

	foreach ($classlist as $ename => $v) {
		if (in_array($ename, $typeid_arr)) {
			if (isset($arr['current']) && $arr['current'] == $ename) {
				$item['rel'] = 1;
			}
			$item['id'] = $item['typeid'] = $ename;
			$item['typename'] = $v['title'];
			$item['typeurl'] = $item['typelink'] = '/' . $ename;
			$result[] = $item;
		}
	}


	if (isset($arr['row']) && $arr['row'] > 0) {
		$result = array_slice($result, 0, $arr['row']);
	}

	return $result;
}

function get_type($arr = array())
{
	$result = array();
	if (empty($arr['typeid'])) {
		return $result;
	}
	$typeid = $arr['typeid'];

	$classlist = $GLOBALS['G']['nav'];

	if (isset($classlist[$typeid])) {

		$v = $classlist[$typeid];
		$item['id'] = $item['typeid'] = $v['ename'];
		$item['typename'] = $v['title'];
		$item['typeurl'] = '/' . $v['ename'];
		$result[] = $item;
	}
	return $result;
}

function get_arclist($arr = array())
{
	if (empty($GLOBALS['G']['site'])) {
		return false;
	}
	$tagname = empty($arr['tagname']) ? 'arclist' : $arr['tagname'];
	$typeid = empty($arr['typeid']) ? 0 : $arr['typeid'];
	$current = empty($arr['current']) ? 0 : $arr['current'];
	$flag = empty($arr['flag']) ? '' : $arr['flag'];
	$row = empty($arr['row']) ? 10 : $arr['row'];
	$pagesize = empty($arr['pagesize']) ? 0 : $arr['pagesize'];
	$row = $pagesize ? $pagesize : $row;

	$titlelen = empty($arr['titlelen']) ? 60 : $arr['titlelen'];
	$infolen = empty($arr['infolen']) ? 120 : $arr['infolen'];
	$imgwidth = empty($arr['imgwidth']) ? 120 : $arr['imgwidth'];
	$imgheight = empty($arr['imgheight']) ? 120 : $arr['imgheight'];

	$arcid = empty($arr['arcid']) ? 0 : $arr['arcid'];

	$orderby = empty($arr['orderby']) ? '' : $arr['orderby'];
	$sort = empty($arr['sort']) ? '' : $arr['sort'];
	$orderby = empty($orderby) ? $sort : $orderby;

	$orderWay = empty($arr['orderWay']) ? 'desc' : $arr['orderWay'];

	$subday = empty($arr['subday']) ? 0 : $arr['subday'];
	$keyword = empty($arr['keyword']) ? '' : $arr['keyword'];

	$tagid = empty($arr['tagid']) ? '' : $arr['tagid'];

	$limit = empty($arr['limit']) ? '' : $arr['limit'];

	if (!empty($arr['type']) && preg_match('#image.#', $arr['type'])) {
		$flag = empty($flag) ? 'p' : $flag . ',p';
	}
	if (!empty($arr['type']) && preg_match('#commend.#', $arr['type'])) {
		$flag = empty($flag) ? 'c' : $flag . ',c';
	}

	if (!empty($arr['listtype'])) {
		if (preg_match('/commend/i', $arr['listtype'])) {
			$flag = $flag ? $flag . ',' . 'c' : 'c';
		} else if (preg_match('/image/i', $arr['listtype'])) {
			$flag = $flag ? $flag . ',' . 'p' : 'p';
		}
	}

	//TODO: cfg_keyword_like未定义
	// if($orderby=='near' && $cfg_keyword_like=='N') {
	// 	$keyword = '';
	// }

	$where = array();

	//当前域名
	$siteId = $GLOBALS['G']['site']['id'];

	//时间限制(用于调用最近热门文章、热门评论之类)，这里的时间只能计算到天，否则缓存功能将无效
	if ($subday > 0) {
		$ntime = gmmktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y'));
		$limitday = $ntime - ($subday * 24 * 3600);
		$where[] = " addtime > $limitday ";
	}

	//文档属性
	if ($flag != '') {
		$flags = explode(',', $flag);
		$orwhere = array();
		for ($i = 0; isset($flags[$i]); $i++) {
			$cflag = $flags[$i];
			if ($cflag == 'p') {
				$where[] = " thumb<>'' ";
			} else {
				$orwhere[] = " flag='$cflag' ";
			}
		}
		if ($orwhere) {
			$where[] = " (" . implode(' OR ', $orwhere) . ") ";
		}
	}

	if (!empty($typeid) && $typeid != 'all' && $typeid != 'index' && $typeid != 'search' && $typeid != 'tags') {
		//指定了多个栏目时，不再获取子类的id
		if (preg_match('#,#', $typeid)) {
			$typeid_arr = explode(',', $typeid);
			$typeid_str = "'" . implode("','", $typeid_arr) . "'";
			$where[] = " class IN ($typeid_str) ";
		} else {
			$where[] = " class='$typeid' ";
		}
	}

	//文档排序的方式
	$ordersql = '';
	if ($orderby == 'hot' || $orderby == 'click') {
		$ordersql = " ORDER BY click $orderWay";
	} else if ($orderby == 'rand') {
		$ordersql = "  ORDER BY rand()";
	} else if ($orderby == 'near') {
		$ordersql = " ORDER BY ABS(id - " . $arcid . ")";
	} else {
		$ordersql = " ORDER BY addtime $orderWay";
	}

	//分页
	$page = 1;
	$uri_info = parse_url($_SERVER['REQUEST_URI']);
	$rest_uri = trim($uri_info['path'], '/');
	if (preg_match("#.*?/list_(\d*).html#si", $rest_uri, $mt)) {
		$page = $mt[1];
	}
	$offset = ($page - 1) * $row;

	//limit条件
	$limit = trim(preg_replace('#limit#is', '', $limit));
	if ($limit != '') {
		$limitsql = " LIMIT $limit ";
		$limitarr = explode(',', $limit);
		$row = isset($limitarr[1]) ? $limitarr[1] : $row;
	} else {
		$limitsql = " LIMIT $offset,$row ";
	}

	//如果是标签
	$tagid = 0;
	if (M == 'tags' && !empty($arr['tagid'])) {
		$tagid = $arr['tagid'];
		$addsql = empty($where) ? "" : implode(' AND ', $where) . " AND ";
		if ($tagname == 'list') {
			$total = db::getField("SELECT COUNT(*) from `content_{$siteId}` join tagindex as i on i.aid=id and i.tagid='$tagid' WHERE {$addsql} status = 1");
		}
		$res = db::select("SELECT * FROM `content_{$siteId}` join tagindex as i on i.aid=id and i.tagid='$tagid' WHERE {$addsql} status = 1 {$ordersql} {$limitsql}");
	} else {
		//关键字条件
		if ($keyword != '') {
			$keyword = str_replace(',', '|', $keyword);
			$where[] = " CONCAT(title,keyword) REGEXP '$keyword' ";
		}
		$addsql = empty($where) ? "" : implode(' AND ', $where) . " AND ";

		if ($tagname == 'list') {
			$total = db::getField("SELECT COUNT(*) FROM `content_{$siteId}` WHERE {$addsql} status = 1");
		}
		$res = db::select("SELECT * FROM `content_{$siteId}` WHERE {$addsql} status = 1 {$ordersql} {$limitsql}");
	}

	$GLOBALS["pageinfo"] = false;

	if ($tagname == 'list') {
		$pageinfo['pagesize'] = $row;
		$pageinfo['total'] = $total;
		$pageinfo['nowpage'] = $page;
		$pageinfo['typeid'] = $typeid;
		$pageinfo['keyword'] = $keyword;
		$pageinfo['tagid'] = $tagid;

		$GLOBALS["pageinfo"] = $pageinfo;
	}

	$list = array();
	$classlist = $GLOBALS['G']['nav'];
	foreach ($res as $k => $row) {
		$row['autoindex'] = $k + 1;
		$row['typeid'] = 'index';
		$row['typename'] = '';
		if (!empty($row['class']) && !empty($classlist[$row['class']])) {
			$row['typeid'] = $row['class'];
			$row['typename'] = $classlist[$row['class']]['title'];
		}

		$row['info'] = $row['infos'] = cn_substr($row['description'], $infolen);
		$row['typeurl'] = "/{$row['typeid']}";

		$row['url'] = $row['filename'] = $row['arcurl'] = $row['typeurl'] . "/{$row['id']}.html";


		if (!empty($row['thumb'])) {
			if (strpos($row['thumb'], 'http') === false) {
				$row['thumb'] = preg_replace("#(\.[^\.]*)$#", '_small$1', $row['thumb']);
			}
		} else {
			$row['thumb'] = '/static/common/images/nopic.png';
		}
		$row['picname'] = $row['litpic'] = $row['thumb'];

		$row['pubdate'] = $row['addtime'];
		$row['stime'] = GetDateMK($row['pubdate']);

		$row['typelink'] = "<a href='" . $row['typeurl'] . "'>" . $row['typename'] . "</a>";
		$row['image'] = "<img src='" . $row['picname'] . "' border='0' width='$imgwidth' height='$imgheight' alt='" . preg_replace("#['><]#", "", $row['title']) . "'>";
		$row['imglink'] = "<a href='" . $row['filename'] . "'>" . $row['image'] . "</a>";
		$row['fulltitle'] = $row['title'];
		$row['title'] = cn_substr($row['title'], $titlelen);
		if ($row['flag'] == 'b') {
			$row['title'] = "<b>" . $row['title'] . "</b>";
		}
		$row['textlink'] = "<a href='" . $row['filename'] . "'>" . $row['title'] . "</a>";

		$row['plusurl'] = $row['phpurl'] = $row['filename'];
		$row['templeturl'] = $GLOBALS['G']['site']['template'];
		$list[] = $row;
	}
	return $list;
}

function get_pagelist($arr = array())
{

	if (empty($GLOBALS['pageinfo'])) return;
	$pageInfo = $GLOBALS['pageinfo'];

	$list_len = empty($arr['listsize']) ? 5 : $arr['listsize'];
	$listitem = empty($arr['listitem']) ? 'index,end,pre,next,pageno' : $arr['listitem'];


	$typeid = empty($pageInfo['typeid']) ? 'index' : $pageInfo['typeid'];
	$keyword = empty($pageInfo['keyword']) ? '' : $pageInfo['keyword'];
	$tagid =  empty($pageInfo['tagid']) ? '' : $pageInfo['tagid'];

	$prepage = "";
	$nextpage = "";

	$prepagenum = $pageInfo['nowpage'] - 1;
	$nextpagenum = $pageInfo['nowpage'] + 1;

	if ($list_len == "" || preg_match("/[^0-9]/", $list_len)) {
		$list_len = 3;
	}

	$totalpage = ceil($pageInfo['total'] / $pageInfo['pagesize']);
	if ($totalpage <= 1 && $pageInfo['total'] > 0) {
		return "<span>共<strong>1</strong>页<strong>" . $pageInfo['total'] . "</strong>条记录</span>";
	}
	if ($pageInfo['total'] == 0) {
		return "<span>共<strong>0</strong>页<strong>" . $pageInfo['total'] . "</strong>条记录</span>";
	}

	$maininfo = "<span>共<strong>{$totalpage}</strong>页<strong>" . $pageInfo['total'] . "</strong>条</span>";

	if ($tagid) {
		$tnamerule = "/tags/{$tagid}/list_{page}.html";
	} else {
		$uri_info = parse_url($_SERVER['REQUEST_URI']);
		$url = trim($uri_info['path'], '/');

		if (empty($url) || preg_match('#/new#', $url)) {
			$showtypepath = '/new';
		} elseif (preg_match('#/hot#', $url)) {
			$showtypepath = '/hot';
		} elseif (preg_match('#/photo#', $url)) {
			$showtypepath = '/photo';
		} else {
			$showtypepath = '';
		}
		$tnamerule = "/{typeid}{$showtypepath}/list_{page}.html";
	}

	if ($keyword) {
		$tnamerule .= '?keyword=' . $keyword;
	}

	if (!preg_match('#,#', $typeid) && $typeid != 'all' && $typeid != 'top' && $typeid != 'index') {
		$tnamerule = str_replace('{typeid}', $typeid, $tnamerule);
	} else {
		if ($keyword) {
			$tnamerule = str_replace('{typeid}', 'plus/search', $tnamerule);
		} else {
			$tnamerule = str_replace('{typeid}', 'index', $tnamerule);
		}
	}

	//获得上一页和主页的链接
	if ($pageInfo['nowpage'] != 1) {
		$prepage .= "<a href='" . str_replace("{page}", $prepagenum, $tnamerule) . "'>上一页</a>\r\n";
		$indexpage = "<a href='" . str_replace("{page}", 1, $tnamerule) . "'>首页</a>\r\n";
	} else {
		$indexpage = "<span>首页</span>\r\n";
	}

	//下一页,未页的链接
	if ($pageInfo['nowpage'] != $totalpage && $totalpage > 1) {
		$nextpage .= "<a href='" . str_replace("{page}", $nextpagenum, $tnamerule) . "'>下一页</a>\r\n";
		$endpage = "<a href='" . str_replace("{page}", $totalpage, $tnamerule) . "'>末页</a>\r\n";
	} else {
		$endpage = "<span>末页</span>";
	}

	//获得数字链接
	$listdd = "";
	$total_list = $list_len * 2 + 1;
	if ($pageInfo['nowpage'] >= $total_list) {
		$j = $pageInfo['nowpage'] - $list_len;
		$total_list = $pageInfo['nowpage'] + $list_len;
		if ($total_list > $totalpage) {
			$total_list = $totalpage;
		}
	} else {
		$j = 1;
		if ($total_list > $totalpage) {
			$total_list = $totalpage;
		}
	}

	for ($j; $j <= $total_list; $j++) {
		if ($j == $pageInfo['nowpage']) {
			$listdd .= "<span class=\"nowpage\">$j</span>\r\n";
		} else {
			$listdd .= "<a href='" . str_replace("{page}", $j, $tnamerule) . "'>" . $j . "</a>\r\n";
		}
	}

	$plist = "";
	if (preg_match('/index/i', $listitem)) {
		$plist .= $indexpage . ' ';
	}
	if (preg_match('/pre/i', $listitem)) {
		$plist .= $prepage . ' ';
	}
	if (preg_match('/pageno/i', $listitem)) {
		$plist .= $listdd . ' ';
	}
	if (preg_match('/next/i', $listitem)) {
		$plist .= $nextpage . ' ';
	}
	if (preg_match('/end/i', $listitem)) {
		$plist .= $endpage . ' ';
	}
	if (preg_match('/info/i', $listitem)) {
		$plist .= $maininfo . ' ';
	}
	return $plist;
}

function get_prenext($article, $arr = array())
{
	$siteId = $GLOBALS['G']['site']['id'];

	$aid = $article['id'];
	$typeid = $article['class'];

	$get = empty($arr['get']) ? '' : $arr['get'];
	if ($get == 'pre') {
		$preRow =  db::find("SELECT id,class,title From `content_{$siteId}` where status=1 AND id<$aid AND class='{$typeid}' order by id desc");
		if ($preRow) {
			$mlink = "/{$preRow['class']}/{$preRow['id']}.html";
			$preArc = "上一篇：<a href=\"{$mlink}\">{$preRow['title']}</a>";
		} else {
			$preArc = "上一篇：没有了";
		}
		return $preArc;
	} else if ($get == 'next') {
		$nextRow =  db::find("SELECT id,class,title From `content_{$siteId}` where status=1 AND id>$aid AND class='{$typeid}' order by id asc");
		if ($nextRow) {
			$mlink = "/{$nextRow['class']}/{$nextRow['id']}.html";
			$nextArc = "下一篇：<a href=\"{$mlink}\">{$nextRow['title']}</a>";
		} else {
			$nextArc = "下一篇：没有了";
		}
		return $nextArc;
	} else {

		$preRow =  db::find("SELECT id,class,title From `content_{$siteId}` where status=1 AND id<$aid AND class='{$typeid}' order by id desc");
		$preRow = db::find("SELECT id,class,title From `content_{$siteId}` where status=1 AND id>$aid AND class='{$typeid}' order by id asc");

		if (!empty($preRow)) {
			$mlink = "/{$preRow['class']}/{$preRow['id']}.html";
			$preArc = "上一篇：<a href=\"{$mlink}\">{$preRow['title']}</a>";
		} else {
			$preArc = "上一篇：没有了";
		}

		if (!empty($nextRow)) {
			$mlink = "/{$nextRow['class']}/{$nextRow['id']}.html";
			$nextArc = "下一篇：<a href=\"{$mlink}\">{$nextRow['title']}</a>";
		} else {
			$nextArc = "下一篇：没有了";
		}

		return $preArc . '&nsub;' . $nextArc;
	}
}

function get_flink($arr = array(), $returnHtml = 1)
{
	$and = "";
	if (!empty($GLOBALS['G']['site'])) {
		$siteId = $GLOBALS['G']['site']['id'];
		$and = " and (yuming_id='$siteId' or yuming_id=0)";
	}

	$row = empty($arr['row']) ? 30 : $arr['row'];
	$titlelen = empty($arr['row']) ? 24 : $arr['row'];

	$res = db::select("SELECT * FROM flink where status=1 {$and} order by id desc limit 0,$row");

	$flink = array();
	$flink_text = "";
	foreach ($res as $v) {
		$link = "<li><a href='" . $v['url'] . "' target='_blank'>" . cn_substr($v['sitename'], $titlelen) . "</a></li>";
		$flink_text .= $link;
		$v['webname'] = cn_substr($v['sitename'], $titlelen);
		$flink[] = $v;
	}

	if ($returnHtml) {
		return $flink_text;
	} else {
		return $flink;
	}
}

function get_tag($arr = array(), $returnHtml = 0)
{
	if (empty($GLOBALS['G']['site'])) {
		return false;
	}

	$siteId = $GLOBALS['G']['site']['id'];

	$ltype = empty($arr['sort']) ? '' : $arr['sort'];
	$num = empty($arr['row']) ? '' : $arr['row'];
	$getall = empty($arr['getall']) ? 0 : $arr['getall'];


	if ($ltype == 'rand') $orderby = 'rand() ';
	else if ($ltype == 'week') $orderby = ' i.click DESC ';
	else if ($ltype == 'month') $orderby = ' i.click DESC ';
	else if ($ltype == 'hot') $orderby = ' i.click DESC ';
	else if ($ltype == 'total') $orderby = ' i.click DESC ';
	else $orderby = ' t.addtime DESC  ';

	if ($getall == 0 && !empty($GLOBALS['article_content'])) {
		$aid = $GLOBALS['article_content']['id'];
		$taglist = db::select("SELECT t.id,t.tagsname,t.addtime,i.aid,i.click from tagindex as i LEFT JOIN tagslist as t ON i.tagid=t.id where i.aid='$aid' and i.siteId='$siteId' order by $orderby LIMIT 0,$num");
	} else {
		$taglist = db::select("SELECT t.id,t.tagsname,t.addtime,i.aid,i.click from tagindex as i LEFT JOIN tagslist as t ON i.tagid=t.id where i.siteId='$siteId' order by $orderby LIMIT 0,$num");
	}

	$taglist_text = '';
	foreach ($taglist as $key => $row) {
		$taglist[$key]['keyword'] = $row['tagsname'];
		$taglist[$key]['tag'] = htmlspecialchars($row['tagsname']);
		$taglist[$key]['link'] = "/tags/" . $row['id'];
		$taglist[$key]['highlight'] = 0;
		if ($row['click'] > 300) {
			$taglist[$key]['highlight'] = mt_rand(3, 4);
		} else if ($row['click'] > 3000) {
			$taglist[$key]['highlight'] = mt_rand(5, 6);
		} else {
			$taglist[$key]['highlight'] = mt_rand(1, 2);
		}
		$taglist_text .= "<a href='" . $taglist[$key]['link'] . "' target='_blank'>" . $taglist[$key]['tag'] . "</a>";
	}
	if ($returnHtml) {
		return $taglist_text;
	} else {
		return $taglist;
	}
}

function get_likearticle($arc, $arr = array())
{
	if (empty($arc)) {
		return array();
	}

	if (empty($GLOBALS['G']['site'])) {
		return array();
	}

	$siteId = $GLOBALS['G']['site']['id'];

	$row = empty($arr['row']) ? 5 : $arr['row'];
	$titlelen = empty($arr['titlelen']) ? 30 : $arr['titlelen'];
	$infolen = empty($arr['infolen']) ? 120 : $arr['infolen'];
	$mytypeid = empty($arr['mytypeid']) ? 0 : $arr['mytypeid'];
	$imgwidth = empty($arr['imgwidth']) ? '100%' : $arr['imgwidth'];
	$imgheight = empty($arr['imgheight']) ? '100%' : $arr['imgheight'];

	$arcid = (!empty($arc['id']) ? $arc['id'] : 0);

	$tagindex = db::select("SELECT tagid FROM tagindex WHERE aid='$arcid'");
	if (!$tagindex) {
		return array();
	}
	$tag_ids = '';
	foreach ($tagindex as $v) {
		$tag_ids = empty($tag_ids) ? $v['tagid'] : ',' . $v['tagid'];
		# code...
	}

	$query = "SELECT arc.* FROM `content_{$siteId}` as arc join tagindex as t on t.aid=arc.id where t.tagid in ($tag_ids) order by arc.id desc limit $row";

	$res = db::select($query);

	$list = array();
	$classlist = $GLOBALS['G']['nav'];
	foreach ($res as $k => $row) {
		$row['autoindex'] = $k + 1;
		$row['typeid'] = 'index';
		$row['typename'] = '';
		if (!empty($row['class']) && !empty($classlist[$row['class']])) {
			$row['typeid'] = $row['class'];
			$row['typename'] = $classlist[$row['class']]['title'];
		}

		$row['info'] = $row['infos'] = cn_substr($row['description'], $infolen);
		$row['typeurl'] = "/{$row['typeid']}";

		$row['url'] = $row['filename'] = $row['arcurl'] = $row['typeurl'] . "/{$row['id']}.html";


		if (!empty($row['thumb'])) {
			if (strpos($row['thumb'], 'http') === false) {
				$row['thumb'] = preg_replace("#(\.[^\.]*)$#", '_small$1', $row['thumb']);
			}
		} else {
			$row['thumb'] = '/static/common/images/nopic.png';
		}
		$row['picname'] = $row['litpic'] = $row['thumb'];

		$row['pubdate'] = $row['addtime'];
		$row['stime'] = GetDateMK($row['pubdate']);

		$row['typelink'] = "<a href='" . $row['typeurl'] . "'>" . $row['typename'] . "</a>";
		$row['image'] = "<img src='" . $row['picname'] . "' border='0' width='$imgwidth' height='$imgheight' alt='" . preg_replace("#['><]#", "", $row['title']) . "'>";
		$row['imglink'] = "<a href='" . $row['filename'] . "'>" . $row['image'] . "</a>";
		$row['fulltitle'] = $row['title'];
		$row['title'] = cn_substr($row['title'], $titlelen);
		if ($row['flag'] == 'b') {
			$row['title'] = "<b>" . $row['title'] . "</b>";
		}
		$row['textlink'] = "<a href='" . $row['filename'] . "'>" . $row['title'] . "</a>";

		$row['plusurl'] = $row['phpurl'] = $row['filename'];
		$row['templeturl'] = $GLOBALS['G']['site']['template'];
		$list[] = $row;
	}
	return $list;
}
