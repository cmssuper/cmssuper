<?php

class crawler
{

    public $html;

    public function down($url)
    {
        $res = httpUnit::get($url);
        if ($res['httpcode'] == 200) {
            if ($res['charset']) {
                $charset = $res['charset'];
            } else {
                $charset = $this->get_charset($res['html']);
            }
            return $this->html = $this->charset($res['html'], $charset, 'utf-8');
        }
    }

    public function getLinks($keyword, $e)
    {
        $dp = $this->DP();
        $word_enc = urlencode($dp[2][0] != 'utf-8' ? mb_convert_encoding($keyword, $dp[2][0], 'utf-8') : $keyword);
        $html = $this->down(str_replace('{p}', mt_rand(0, 50) * $dp[2][1], str_replace('{w}', $word_enc, $dp[0])));
        if (strpos($html, '输入验证码')) return 'checkcode Error';
        preg_match_all($dp[1], $html, $r);
        $res = array();
        if (!empty($r[0])) {
            $r[0] = array_unique($r[0]);
            foreach ($r[0] as $v) {
                if (strpos($v, 'javascript:') !== false) continue;
                $res[] = $v;
            }
        }
        return $res;
    }

    public function getTitle()
    {
        if (preg_match("/<title>(.{10,})<\/title>/isU", $this->html, $t)) {
            if (preg_match_all("/<h([1-3])>(.{10,})<\/h\\1>/isU", $this->html, $ts)) {
                foreach ($ts[2] as $vt) {
                    if (strpos($t[1], $vt) !== false) return $vt;
                }
            }

            $t[1] = str_replace(array('-', '—', '_', '>'), '|', $t[1]);
            $splits = explode('|', $t[1]);
            $l = 0;
            foreach ($splits as $tp) {
                $len = strlen($tp);
                if ($l < $len) {
                    $l = $len;
                    $tt = $tp;
                }
            }
            $tt = trim(str_replace('"', '＂', mb_substr(strip_tags($tt), 0, 50, 'utf-8')));
            return $tt;
        }
        return false;
    }

    // description
    function getDescription()
    {
        preg_match("/<meta[\s]+name=['\"]description['\"] content=['\"]([^>]*)['\"]/isU", $this->html, $inarr);
        preg_match("/<meta[\s]+content=['\"]([^>]*)['\"] name=['\"]description['\"]/isU", $this->html, $inarr2);
        if (!isset($inarr[1]) && isset($inarr2[1]))
            $inarr[1] = $inarr2[1];
        if (isset($inarr[1])) return trim(mb_substr(strip_tags($inarr[1]), 0, 150));
        else return false;
    }

    //keyword
    function getKeyword()
    {
        preg_match("/<meta[\s]+name=['\"]keywords['\"] content=['\"]([^>]*)['\"]/isU", $this->html, $inarr);
        preg_match("/<meta[\s]+content=['\"]([^>]*)['\"] name=['\"]?keywords['\"]/isU", $this->html, $inarr2);
        if (!isset($inarr[1]) && isset($inarr2[1]))
            $inarr[1] = $inarr2[1];
        if (isset($inarr[1])) {
            $k = trim(mb_substr(strip_tags($inarr[1]), 0, 100));
            if (!preg_match('/,/', $k))
                $k = str_replace(' ', ',', $k);
            return $k;
        }
        return false;
    }

    public function getBody()
    {
        $brule[0] = array('/<!--.*-->/isU', '');   //去掉注释
        $brule[1] = array('/<(!doctype|xml|meta|link|base|basefont|bgsound|area|wbr)[^>]*>/isU', '');
        $brule[2] = array('/<(html|body|noscript|font|table|tbody|tfoot|thead)[^>]*>(.*)<\/\1>/isU', '\\2');
        $brule[3] = array('/<(script|head|style|form|textarea|select|object|noframes|frame|iframe|frameset|applet|label|map).*<\/\1>/isU', '');
        $brule[4] = array('/\s+(class|id|name|onmouseover|onmouseout|onload|click|onclick|onload|align|rel|style|height|width|border|itemprop)=([\'"])[^\2]*\2/isU', '');
        $brule[5] = array('/<p>((&nbsp;)*[\r\n\t\s]*)*/is', '<p>');
        $brule[6] = array('/(<a[^>]*>[^>]*<\/a>\s*(\-|&nbsp;|\|)*\s*){3,}/is', '');  //连续多a 
        $brule[7] = array('/<(a|p|span|i|em)[^>]*>[\s\r\n]*<\/\1>/isU', '');
        $brule[8] = array('/<a[^>]*(#|javascript)[^>]*>.*<\/a>/iU', '');
        $brule[9] = array('/[\s]{5,}/', "\r\n"); //去掉空格
        $brule[10] = array('/<(tr|dl)[^>]*>(.*)<\/\1>/isU', '\\2 <br />'); // tr 转 div ，为了切割
        $brule[11] = array('/<(dt|dd|td)[^>]*>(.*)<\/\1>/isU', '<span>\\2</span> &nbsp; ');
        if (!preg_match("/<body[^>]*>(.*?)<\/body>/isU", $this->html, $t)) return false;
        $text = $t[1];
        if (substr_count($this->html, "\n") > 5000 || substr_count($text, "\n") > 3500 || substr_count($text, "<a") > 500) return false;
        $l = 0;
        while ($l != strlen($text)) {
            $l = strlen($text);
            foreach ($brule as $v)
                $text = preg_replace($v[0], $v[1], $text);
        }
        $s = preg_replace_callback('/<ul[^>]*>.*?<\/ul>/is', function ($mt) {
            if (strpos($mt[0], '<a') == false) return $mt[0];
            if (count(explode('<li', $mt[0])) >= 3) {
                return '';
            }
        }, $text);
        $bl = strlen($s);
        $ry = array();
        $prepos = 0;
        for ($i = 0; $i < $bl - 3; $i++) {
            $ntag = strtolower($s[$i] . $s[$i + 1] . $s[$i + 2] . $s[$i + 3]);
            $etag = strtolower($s[$i] . '/' . $s[$i + 1] . $s[$i + 2]);
            if ($ntag == '<div') {
                for ($j = $i, $g = 0, $temp = ''; $j < $bl - 3; $j++) {
                    if ($ntag == strtolower($s[$j] . $s[$j + 1] . $s[$j + 2] . $s[$j + 3])) $g++;
                    if ($etag == strtolower($s[$j] . $s[$j + 1] . $s[$j + 2] . $s[$j + 3])) $g--;
                    if ($g == 0) {
                        $ry[] = $temp . $etag . 'v>';
                        break;
                    }
                    $temp .= $s[$j];
                }
            }
        }
        $by = $this->BY($ry);
        $arr = explode('|', '版权所有|ICP备|All Rights Reserved|免责声明');
        foreach ($arr as $vr) {
            if (strpos($by, $vr) !== false) {
                return false;
            }
        }
        return $by;
    }

    function BY($by)
    {
        $total = count($by);
        $w = 0;
        $mp = 0;
        $mr = 0;
        $mt = 0;
        foreach ($by as $k => $v) {
            $text = strip_tags($v, '<img>');
            $texttmp = str_replace(array('，', '。', '!', '？'), ',', $text);
            $texttmps = explode(',', $texttmp);
            $to[$k]['sp'] = count($texttmps);
            $mp = max($mp, $to[$k]['sp']);
            $s = strlen($v);
            $l = strlen($text);
            $mr = max($l, $mr);
            $to[$k]['ps'] = (1 - pow(abs($k / $total - 0.5) * 2, 3)) / 2;
            $to[$k]['w1'] = $l / $s / 2;
            $to[$k]['l'] = $l;
            $to[$k]['tg'] = count(explode('<div', $v));
            $mt = max($to[$k]['tg'], $mt);
        }
        if ($mp > 0) {
            foreach ($by as $k => $v) {
                $w2 = $to[$k]['l'] / $mr / 2;
                $w3 = ($to[$k]['sp'] / $mp) / 2;
                $w4 = (1 - $to[$k]['tg'] / $mt) / 2;
                $wg = $w3 + $w4 + $to[$k]['ps'] + $to[$k]['w1'] + $w2;
                if ($to[$k]['sp'] > 5 && $wg > 1 && $to[$k]['w1'] > 0.3 && $w < $wg) {
                    $w = $wg;
                    $bk = $k;
                }
            }
        }
        if (isset($bk)) {
            return $this->LP($by[$bk]);
        } else return false;
    }

    //最后过滤
    function LP($r)
    {
        $hrule[0] = array('/<([\/]?)div[^>]*>/i', '<\1p>');
        $hrule[1] = array('/<([\/]?)P[^>]*>/', '<\1p>'); //大P转小p
        $hrule[1] = array('/<([\/]?)li[^>]*>/', '<\1p>'); //li转p
        $hrule[3] = array('/<img([^>]*)>/isU', '<p align="center"><img\1></p>');
        $hrule[4] = array('/([^\'"\=\/])(http:\/\/|www\.)[A-Za-z0-9_\-\.\/]*([^A-Za-z0-9_\-\.\/])/i', '\1\3');  //去除文本网址

        foreach ($hrule as $t)
            $r = preg_replace($t[0], $t[1], $r);

        $r = preg_replace_callback('/<a([^>]*)>(.*)<\/a>/isU', function ($arr) {
            if (strpos($arr[1], 'http://') !== false || strpos($arr[1], 'https://') !== false) {
                return $arr[2];
            }
            return $arr[0];
        }, $r);
        $r = strip_tags($r, '<img>,<p>,<span>,<br>,<br/>,<b>,<strong>,<a>,<embed>,<video>');

        $i = 8;
        $for = '相关|热点|上一篇|下一篇|下一页|新闻订阅|编辑|来源|阅读|次数|时间|作者|本文|转自|版权所有|转载请|不得转载|法律责任|更多|现在的位置|首页|观点不代表';
        $forarr = explode('|', $for);
        $x = 1;
        $y = 1;
        while ($i--) {
            $fp = $this->PO($r, $x);
            $sf = trim(strip_tags($fp, '<img>,<embed>,<video>'));
            foreach ($forarr as $v) {
                if ((strpos($fp, $v) !== false && strlen($sf) < 100 + $x * 30) || (strlen($fp) > 0 && strlen($sf) < 20)) {
                    $len = strlen($fp);
                    $r = substr($r, $len);
                    $x = 0;
                    break;
                }
            }
            $x++;
            $fp = $this->LO($r, $y);
            $sf = trim(strip_tags($fp, '<img>,<embed>,<video>'));
            foreach ($forarr as $v) {
                if ((strpos($fp, $v) !== false && strlen($sf) < 100 + $y * 30) || (strlen($fp) > 0 && strlen($sf) < 20)) {
                    $len = strlen($fp);
                    $r = substr($r, 0, -$len);
                    $y = 0;
                    break;
                }
            }
            $y++;
        }

        if (strlen($r) > 50) return $r;
        else return false;
    }

    //处理文章开始
    function PO($s, $i = 1)
    {
        $p = "<p>";
        $a = explode($p, $s);
        $arr = array();
        for ($j = 0; $j < $i; $j++) {
            if (isset($a[$j])) {
                $arr[] = $a[$j];
                if (empty($a[$j])) {
                    $arr[] = $a[$j + 1];
                    $j++;
                }
            }
        }
        return join($p, $arr);
    }

    //处理文章结尾
    function LO($s, $i = 1)
    {
        $p = "</p>";
        $a = explode($p, $s);
        $arr = array();
        $c = count($a);
        for ($j = 0; $j < $i; $j++) {
            if (isset($a[$c - 1 - $j])) {
                $arr[] = $a[$c - 1 - $j];
                if (empty($a[$c - 1 - $j])) {
                    array_unshift($arr, $a[$c - 2 - $j]);
                    $j++;
                }
            }
        }
        return join($p, $arr);
    }

    function DP()
    {
        $s = array(
            array(
                'https://www.baidu.com/s?rtt=1&bsst=1&cl=2&tn=news&rsv_dl=ns_pc&word={w}&pn={p}',
                '/(?<=href=")(http[s]?:\/\/)((?!baidu|").)*[^\/](?=")/iU',
                array('utf-8', '10'),
            ),
            array(
                'https://www.sogou.com/web?ie=utf8&query={w}&page={p}',
                '/(?<=href=")(http[s]?:\/\/)((?!sohu|sogou|").)*[^\/](?=")/iU',
                array('utf-8', '1'),
            ),
            array(
                'https://cn.bing.com/search?q={w}&first={p}',
                '/(?<=href=")(http[s]?:\/\/)((?!bing|content4ads|live|micro|sogou|msn|").)*[^\/](?=")/iU',
                array('utf-8', '10'),
            ),
        );
        return $s[array_rand($s)];
    }

    function charset($str, $from, $to)
    {
        if ($from == $to) return $str;
        return mb_convert_encoding($str, $to, $from);
    }

    function get_charset($v)
    {
        $u8rule = '/^(?:
					 [\x09\x0A\x0D\x20-\x7E]            # ASCII
				   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
				   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
				   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
				   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
				   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
				   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
				   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
               )*$/xs';
        $gbrule = '/^(?:[\x09\x0A\x0D\x20-\x7E]|[\xA1-\xFE][\xA0-\xFF])*$/xs';
        if (preg_match("/(?<=charset=).*(?=['\"])/isU", $v, $i) && in_array(strtolower($i[0]), array('utf-8', 'gb2312', 'gbk'))) {
            $charset = strtolower($i[0]);
        }
        if (preg_match("/(?<=meta\scharset=['\"]).*(?=['\"])/isU", $v, $i) && in_array(strtolower($i[0]), array('utf-8', 'gb2312', 'gbk'))) {
            $charset = strtolower($i[0]);
        } else {
            $v = preg_replace('/0-9a-z\-_/i', '', Html2Text($v));
            $v0 = substr($v, 0, 20);
            $v1 = substr($v, 0, 21);
            $v2 = substr($v, 0, 22);
            if (preg_match($u8rule, $v0) || preg_match($u8rule, $v1) || preg_match($u8rule, $v2)) $charset = 'utf-8';
            else if (preg_match($gbrule, $v0) || preg_match($gbrule, $v1) || preg_match($gbrule, $v2)) $charset = 'gb2312';
        }
        return isset($charset) ? $charset : false;
    }
}
