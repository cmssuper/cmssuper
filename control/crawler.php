<?php
if (!defined('IN_SYS')) {
    exit('Access Denied');
}

define("APPNAME", 'CRAWLER');

class crawler_controller extends crawlerControl
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $ac = gp('acs');
        $acs = array('crawlKeyword', 'crawlRule');
        if (!in_array($ac, $acs)) return;
        set_time_limit(60);
        ignore_user_abort(true);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        self::ThreadLog($ac);
        $lockfile = DATA . '/session/crawler.sock';
        if (!is_file($lockfile)) file_put_contents($lockfile, 0);
        $lockfileTime = filemtime($lockfile);
        $step = max(2, pow(100 - $GLOBALS['G']['speed'], 2) / 15);
        if (time() - $lockfileTime < $step) {
            exit;
        }
        $i = file_get_contents($lockfile);
        file_put_contents($lockfile, (int)$i + 1);
        call_user_func(array($this, $ac));
    }

    public function crawlKeyword()
    {
        $ws = $this->getCrawlKeyword();
        if (!$ws) {
            return;
        }
        $link = db::find("SELECT * FROM crawllinks where status=1 AND ruleid=0 order by id asc");
        if (empty($link)) {
            $keyword = array_rand($ws);
            $info = $ws[$keyword][array_rand($ws[$keyword])];
            $n = new crawler();
            $links = $n->getLinks($keyword, $info);
            foreach ($links as $v) {
                $v = addslashes($v);
                $keyword_adds = addslashes($keyword);
                $old = db::find("select * from crawllinks where ruleid=0 AND link='$v'");
                if (!$old) {
                    db::query("insert into crawllinks (yuming_id,link,keyword,ruleid,status) values ('$info[yuming_id]','$v','$keyword_adds','0','1')");
                }
            }
            return;
        }
        db::query("UPDATE crawllinks set `status`=0 where id='$link[id]'");
        $site = $ws[$link['keyword']][array_rand($ws[$link['keyword']])];

        $n = new crawler();
        $n->down($link['link']);
        $art['title'] = $n->getTitle();
        $art['body'] = $n->getBody();
        if ($art['title'] && $art['body']) {
            $art['keyword'] = $n->getKeyword();
            $art['description'] = $n->getDescription();
            $this->save($art, $site['yuming_id'], $site['ename'], 1, $link['id'], $link['link']);
            echo $link['link'] . '#success';
        } else {
            echo $link['link'] . '#error';
        }
    }

    private function getCrawlKeyword()
    {
        $class = db::select("select yuming_id,ename,crawlWords from classlist where status=1");
        $ws = array();
        foreach ($class as $v) {
            if (!empty($v['crawlWords'])) {
                $crawlWords = explode("\n", $v['crawlWords']);
                $crawlWords = array_filter($crawlWords);
                unset($v['crawlWords']);
                foreach ($crawlWords as $w) {
                    $ws[$w][] = $v;
                }
            }
        }
        return $ws;
    }

    public function crawlRule()
    {
        $ruleid = gp('ruleid');

        //取出文章链接
        $addSql = empty($ruleid) ? "ruleid>0" : "ruleid='$ruleid'";
        $link = db::find("SELECT * FROM crawllinks where status=1 AND $addSql order by id asc");
        if (empty($link)) {
            echo $this->crawlRuleLinks($ruleid);
            return;
        }

        //设定采集连接状态
        db::query("UPDATE crawllinks set `status`=0 where id='$link[id]'");

        $result = httpUnit::get($link['link']);
        if ($result['httpcode'] != 200) {
            db::query("UPDATE crawllinks set `status`=2 where id='$link[id]'");
            echo  "无法采集此文章($result[httpcode]):" . $link['link'];
            return;
        }
        $data = $result['html'];

        //取出规则
        $res = db::find("SELECT * FROM crawler where id='$link[ruleid]'");

        //编码处理
        $unicode = htmlBase::get_charset($data);
        if (!empty($unicode) && $unicode !== "utf-8") {
            $data = mb_convert_encoding($data, "utf-8", $unicode);
        }

        $art = array();

        //匹配关键词
        $art['keywords'] = htmlBase::get_keywords($data);

        //匹配描述
        $art['description'] = htmlBase::get_description($data);

        //匹配文章标题
        $art['title'] = htmlHelper::matchcontent($data, $res['titlerule']);
        $art['title'] = str_replace(array("\r\n", "\r", "\n"), "", $art['title']);
        $art['title'] = trim(strip_tags($art['title']));

        //匹配文章内容
        $art['body'] = htmlHelper::matchcontent($data, $res['contentrule']);

        if (empty($art['title']) || empty($art['body'])) {
            db::query("update crawllinks set status=2 where id='$link[id]'");
            echo "空标题或者空内容";
            return;
        }

        //获取域名
        if ($link['yuming_id'] == 0) {
            $link['yuming_id'] = db::getfield("SELECT * FROM `yuming` AS t1 JOIN (SELECT ROUND(RAND() * ((SELECT MAX(id) FROM `yuming`)-(SELECT MIN(id) FROM `yuming`))+(SELECT MIN(id) FROM `yuming`)) AS id) AS t2 WHERE t1.id >= t2.id ORDER BY t1.id");
        }

        echo $this->save($art, $link['yuming_id'], $res['class'], 1, $link['id'], $link['link']);
    }

    //存入文章
    private function save($data, $siteId, $class, $status, $linkId, $origUri = '')
    {
        //文章查重
        if ($linkId) {
            $title_adds = addslashes($data['title']);
            $exist = db::find("select * from `content_{$siteId}` where title='$title_adds'");
            if ($exist) {
                db::query("update crawllinks set status=3 where id='$linkId'");
                return "采集到重复文章跳过：" . $data['title'];
            }
        }

        $source = isset($data['source']) ? addslashes($data['source']) : '';

        //处理内容
        $body = preg_replace('#<[/]?a[^>]*?>#', "", $data['body']);
        $body = preg_replace('#<[/]?div[^>]*?>#', "", $body);
        $body = preg_replace('#<([a-z]*?)\s[^>]*></\\1>#', "", $body);

        //图片处理，传入采集URL，构建图片完整路径
        $body = helper::convert_images_patch($body, '', $origUri, $siteId);

        //过滤文章内容
        $body = htmlBase::tag_filter($body);

        //获取栏目
        if ($class == '') {
            $classids = db::select("select ename from classlist where yuming_id='$siteId' AND status=1");
            $class = $classids[array_rand($classids, 1)]['ename'];
        }

        //同义词替换
        $body = helper::replace_word($body, $siteId);

        //关键词优化
        $seodata = helper::seoword(array('title' => $data['title'], 'body' => $body), $siteId);
        $title = addslashes(mb_substr($seodata['title'], 0, 50, 'utf-8'));

        //缩略图
        if (preg_match('#src="([^"]*)"#', $body, $nt)) {
            $thumb = $nt[1];
        } else {
            $thumb = '';
        }

        //文章标记
        $rand = mt_rand(0, 100);
        $flag = '';
        if ($rand < 10) {
            $flag = "h";
        } elseif ($rand < 20) {
            $flag = "c";
        } elseif ($rand < 30) {
            $flag = "a";
        } elseif ($thumb && $rand < 40) {
            $flag = "f";
        }

        $description = empty($data['description']) ? addslashes(mb_substr(trim(strip_tags($seodata['body'])), 0, 150, 'utf-8')) : addslashes($data['description']);
        $keywords = empty($data['keywords']) ? "" : addslashes($data['keywords']);

        $addtime = time();
        db::query("INSERT into `content_{$siteId}` (`class`,title,flag,source,thumb,keyword,`description`,`status`,addtime)  values ('$class','$title','$flag','$source','$thumb','$keywords','$description','$status','$addtime')");
        $id = db::insert_id();
        arcWrite($siteId, $id, $seodata['body']);
        return $title . "文章采集成功";
    }

    //列表采集
    private function crawlRuleLinks($ruleid)
    {
        $addSql = empty($ruleid) ? "" : "where id='$ruleid'";
        $res = db::find("select * from crawler $addSql order by updatetime asc ");
        if (!$res) {
            return "没有采集规则";
        }

        $page = $res['page'];
        $updatetime = time();
        db::query("update crawler set updatetime='$updatetime' where id='" . $res['id'] . "'");
        if (preg_match("#\[([1-9]\d*)\-([1-9]\d*)\]#", $res['listurl'], $mt)) {
            if ($page < $mt[1] || $page >= $mt[2]) {
                $page = $mt[1];
            } else {
                $page++;
            }
            $res['listurl'] = preg_replace("#\[([1-9]\d*)\-([1-9]\d*)\]#", $page, $res['listurl']);
            db::query("update crawler set page='$page' where id='" . $res['id'] . "'");
        } else {
            $page = 1;
        }

        $result = httpUnit::get($res['listurl']);
        if ($result['httpcode'] == 200) {
            $data = $result['html'];
        } else {
            return "无法采集此网站($result[httpcode]):" . $res['listurl'];
        }

        $unicode = htmlBase::get_charset($data);
        if (!empty($unicode) && $unicode !== "utf-8") {
            $data = mb_convert_encoding($data, "utf-8", $unicode);
        }

        $html2 = htmlHelper::matchcontent($data, $res['articlerule']);
        $arr_link = array();
        if (preg_match_all("#href=[\"'\s]*([^\s\"'\\\\]*)[\"'\s]#", $html2, $mt3)) {
            $arr_link = $mt3[1];
        }
        if (!empty($res['norule'])) {
            $norule = explode("\n", $res['norule']);
            foreach ($norule as  $k => $value) {
                $norule[$k] = trim($value);
            }
            $norule = array_filter($norule);
            foreach ($arr_link as $k => $value) {
                foreach ($norule as $x => $y) {
                    if (stripos($value, $y) !== false) {
                        unset($arr_link[$k]);
                    }
                }
            }
        }
        $n = 0;
        $e = 0;
        foreach ($arr_link as $key => $value) {
            if (strpos($value, 'javascript') !== 0 && strpos($value, '#') !== 0) {
                $link = htmlHelper::fix_path($res['listurl'], $value);
                $old = db::find("select * from crawllinks where ruleid='$res[id]' AND link='$link'");
                if (!$old) {
                    $n++;
                    db::query("insert into crawllinks(yuming_id,link,ruleid,status) values('$res[yuming_id]','$link','$res[id]','1')");
                } else {
                    $e++;
                }
            }
        }
        return "第{$page}页：采集到网址{$n}个，已采集网址{$e}个";
    }

    public static function ThreadLog($log)
    {
        $file = DATA . '/session/__log.txt';
        if (!is_file($file)) return;
        if (filesize($file) > 1024 * 100) {
            unlink($file);
        }
        file_put_contents($file, date("Ymd H:i:s") . "#$log\r\n", FILE_APPEND);
    }
}
