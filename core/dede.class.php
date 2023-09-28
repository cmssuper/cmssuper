<?php

//处理织梦标签
class dede
{
    private static $dede_rules = array(
        'global' => '/global\.(\w+)\s*\/*/is',
        'include' => '/include\s+file(name)?=[\'\"](\w+)\.htm[\'\"]\s*\/*/is',
        'channelartlist' => '/channelartlist(.*)/is',
        'arclist' => '/(arclist)(.*)/is',
        'arclistsg' => '/arclistsg(.*)/i',
        'imglist' => '/(imglist)(.*)/is',
        'channel' => '/channel(.*)/is',
        'list' => '/list(.*)/is',
        'pagelist' => '/pagelist(.*)/i',
        'type' => '/type(.*)/i',
        'field' => '/field(.+)/i',
        'pagebreak' => '/pagebreak(.*)/i',
        'tag' => '/tag(.*)/i',
        'flinktype' => '/flinktype(.*)/i',
        'flink' => '/flink(.*)/i',
        'adminname' => '/adminname(.*)/i',
        'prenext' => '/prenext(.*)/i',
        'infolink' => '/infolink(.*)/i',
        'infoguide' => '/infoguide(.*)/i',
        'sql' => '/sql(.*)/i',
        'loop' => '/loop(.*)/i',
        'likearticle' => '/likearticle(.*)/i',
        'likeart' => '/likeart(.*)/i',
    );

    private static $dede_replace = array(
        'global' =>  '<?php echo (isset($GLOBALS[\'$1\']) ? $GLOBALS[\'$1\'] : ""); ?>',
        'include' => '<?php include self::tpl("$2"); ?>',
        'channelartlist' => array('channelartlist', 1),
        'arclist' => array('arclist', -1),
        'arclistsg' => array('arclistsg', 1),
        'imglist' => array('arclist', -1),
        'channel' => array('channel', 1),
        'list' => array('_list', 1),
        'pagelist' => array('pagelist', 1),
        'type' => array('type', 1),
        'field' =>  array('field', 1),
        'pagebreak' =>  array('pagebreak', 1),
        'tag' =>  array('tag', 1),
        'flinktype' =>  array('flinktype', 1),
        'flink' =>  array('flink', 1),
        'adminname' =>  array('adminname', 1),
        'prenext' =>  array('prenext', 1),
        'infolink' =>  array('infolink', 1),
        'infoguide' =>  array('infoguide', 1),
        'sql' =>  array('sql', 1),
        'loop' =>  array('loop', 1),
        'likearticle' =>  array('likearticle', 1),
        'likeart' =>  array('likearticle', 1),
    );

    //处理dedecms标签
    public static function dedehandle($string = '')
    {
        $rules = array(
            '/\{dede\:php\s*\}(.+?)\{\/dede\:php\s*\}/is' => array('_dede_php', 1),
            '/\[field\:[\w]+ name=[\'\"]?(\w+)[\'\"]?[^\]]+runphp[^\]]+\](.+?)\[\/field\:[\w]+\]/is'    => array('_dede_php', -1),
            '/\[field\:([\.\w]+)[^\]]+runphp[^\]]+\](.+?)\[\/field\:(\\1)\]/is' => array('_dede_php', -1),
            '/\{dede\:field name=[\'\"]?(\w+)[\'\"]?[^\}]+runphp[^\}]+\}(.+?)\{\/dede\:field(\.\\1)?\}/is'    => array('_dede_php', -1),
            '/\{dede\:field\.([\w]+)[^\}]+runphp[^\}]+\}(.+?)\{\/dede\:field(\.\\1)?\}/is'    => array('_dede_php', -1),
            //'/\{dede\:([^\}]+function[^\}]+)\}/is'	=> array('_dede_field', 1),
            '/\{dede\:([\w\.]+\s*name=[\'\"]?(\w+)[\'\"]?\s*(function)?[^\}]*)\}/is'    => array('_dede_field', 1),
            '/\{dede\:(.+?)\}/is'    => array('_dede_tag', -1),
            '/\[field\:(.+?)\/\]/' => array('_dede_field', 1),
            '/\{\/dede\:(\w+)\}/i' => array('_dede_tag_end', 1),
        );
        foreach ($rules as $key => $value) {
            if (is_array($value)) {
                $GLOBALS['curval'] = $value;
                $string = preg_replace_callback($key, function ($data) {
                    $value = $GLOBALS["curval"];
                    $data = $value[1] >= 0 ? $data[$value[1]] : $data;
                    return call_user_func(array('dede', $value[0]), $data);
                }, $string);
            } else {
                $string =  preg_replace($key, $value, $string);
            }
        }
        return $string;
    }

    public static function _dede_field($string)
    {
        $string = trim($string, '/');
        $string = trim($string);
        preg_match('/([\w\.]+)(\s+function=(["\']?)(.+)\\3)/i', $string, $mt);
        if ($mt && count($mt) > 4) {
            $fieldname = $mt[1];
            if (preg_match('/(\w+)\.(\w+)/i', $fieldname, $m)) {
                if ($m[1] == 'global' || $m[1] == 'field') {
                    $fieldname = $m[2];
                } else {
                    $fieldname = '' . $m[1] . '[\'' . $m[2] . '\']';
                }
            }

            if (empty($mt[4])) {
                if (preg_match('/^\d/i', $fieldname)) {
                    return '';
                }
                return '<?php echo isset($' . $fieldname . ') ? $' . $fieldname . ' : \'\'; ?>';
            }
            $function = $mt[4];
            $function = str_replace('@me', '$' . $fieldname, $function);
            return '<?php $' . $fieldname . ' = isset($' . $fieldname . ') ? $' . $fieldname . ' : \'\'; echo ' . $function . '; ?>';
        } else if (preg_match('/name=(["\']?)(\w+)\\1[\s\w"\']+function=(["\']?)([^\\3]+)\\3/i', $string, $mt2)) {
            $fieldname = $mt2[2];
            $function = $mt2[4];
            if (preg_match('/^\d/i', $fieldname) || preg_match('/^\d/i', $function)) {
                return '';
            }
            $function = str_replace('@me', '$' . $fieldname, $function);
            return '<?php $' . $fieldname . ' = isset($' . $fieldname . ') ? $' . $fieldname . ' : \'\'; echo ' . $function . '; ?>';
        } else {
            if (preg_match('/name=(["\']?)(\w+)\\1/', $string, $mt0)) {
                $fieldname = $mt0[2];
                if (preg_match('/^\d/i', $fieldname)) {
                    return '';
                }
                return '<?php echo isset($' . $fieldname . ') ? $' . $fieldname . ' : ""; ?>';
            } else if (preg_match('/(\w+)\.(\w+)/i', $string, $m)) {
                if ($m[1] == 'global') {
                    $string = $m[2];
                } else {
                    $string = '' . $m[1] . '[\'' . $m[2] . '\']';
                }
            }
            if (preg_match('/^\d/i', $string)) {
                return '';
            }
            return '<?php echo isset($' . $string . ') ? $' . $string . ' : ""; ?>';
        }
    }

    public static function _dede_php($dede_tag_arr)
    {
        if (is_string($dede_tag_arr)) {
            return '<?php ' . $dede_tag_arr . '; ?>';
        } else {
            $fieldname = $dede_tag_arr[1];
            if (preg_match('/(\w+)\.(\w+)/i', $fieldname, $m)) {
                if ($m[1] == 'global') {
                    $fieldname = $m[2];
                } else {
                    $fieldname = '' . $m[1] . '[\'' . $m[2] . '\']';
                }
            }
            $phpscript = $dede_tag_arr[2];
            $phpscript = str_replace('@me', '$me', $phpscript);

            $prestring = "";
            if ($fieldname == 'array') {
                $prestring = '$me=$__value;';
            } else {
                $prestring = '$me=$' . $fieldname . ';';
            }

            return '<?php ' . $prestring . $phpscript . '; echo $me; ?>';
        }
    }

    public static function _dede_tag($dede_tag_arr)
    {
        $dede_tag = trim($dede_tag_arr[1]);
        $dede_tag_name = preg_replace('/(\s+|\.|\/).*/is', '', $dede_tag);
        if (isset(self::$dede_rules[$dede_tag_name]) && isset(self::$dede_replace[$dede_tag_name])) {
            $key = self::$dede_rules[$dede_tag_name];
            $value = self::$dede_replace[$dede_tag_name];

            if (is_array($value)) {
                $GLOBALS["curvalue"] = $value;
                $dede_tag = preg_replace_callback($key, function ($data) {
                    $value = $GLOBALS["curvalue"];
                    $data = $value[1] >= 0 ? $data[$value[1]] : $data;
                    return call_user_func(array('dede', $value[0]), $data);
                }, $dede_tag);
            } else {
                $dede_tag =  preg_replace($key, $value, $dede_tag);
            }
            return $dede_tag;
        } else {
            return '';
        }
    }

    public static function _dede_tag_end($string)
    {
        $dede_tag = array_keys(self::$dede_rules);
        if (in_array($string, $dede_tag)) {
            return '<!-- /foreach -->';
        } else {
            return '';
        }
    }

    /**/
    public static function field($string)
    {
        $string = trim($string);
        preg_match('/name=(["\']?)(\w+)\\1(\s+(function|runphp)=(["\']?)(.+)\\5)?/i', $string, $mt);
        preg_match('/\.(\w+)(\s+(function|runphp)=(["\']?)(.+)\\4)?/i', $string, $mt2);

        if ($mt) {
            $fieldname = $mt[2];

            if ($fieldname == 'imgurls') {
                return '<!--foreach $' . $fieldname . ' -->';
            }

            if (empty($mt[6])) {
                return '<?php echo isset($' . $fieldname . ') ? $' . $fieldname . ' : \'\'; ?>';
            }
            if ($mt[4] == 'function') {
                $function = $mt[6];
                $function = str_replace('@me', '$' . $fieldname, $function);
                return '<?php echo ' . $function . '; ?>';
            }
        } elseif ($mt2) {
            $fieldname = $mt2[1];
            if (empty($mt2[5])) {
                $str = '$' . $fieldname . '';
                return '<?php echo isset(' . $str . ') ? ' . $str . ' : \'{dede:field.' . $fieldname . '/}\'; ?>';
            }

            if ($mt2[3] == 'function') {
                $function = $mt2[5];
                $function = str_replace('@me', '$' . $fieldname . '', $function);
                return '<?php $' . $fieldname . ' = isset($' . $fieldname . ') ? $' . $fieldname . ' : \'\'; echo ' . $function . '; ?>';
            }
        } else {
            return '<?php echo $' . $string . '; ?>';
        }
    }
    /************************ 模板匹配结束 ****************************/

    public static function type($string = '')
    {
        $param_arr = getTagAttr($string);
        $addstr = '';
        if (empty($param_arr['typeid'])) {
            $addstr = '$arr["typeid"] = $typeid;';
        }
        return '<?php $arr = ' . var_export($param_arr, true) . '; ' . $addstr . ' $__type=get_type($arr);?><!-- foreach $__type -->';
    }

    public static function channel($string = '')
    {
        $param_arr = getTagAttr($string);
        unset($param_arr['currentstyle']);
        return '<?php $arr = ' . var_export($param_arr, true) . '; if(empty($arr["typeid"]) && isset($typeid)){$arr["typeid"] = $typeid;} $arr["current"] = $current; $_nav = get_nav($arr);?><!-- foreach $_nav -->';
    }

    public static function channelartlist($string = '')
    {
        $param_arr = getTagAttr($string);
        unset($param_arr['currentstyle']);
        return '<?php $arr = ' . var_export($param_arr, true) . '; $_nav = get_nav($arr);?><!-- foreach $_nav -->';
    }

    public static function arclist($data = array())
    {
        $tagname = $data[1];
        $string = empty($data[2]) ? '' : $data[2];
        $string = trim($string);

        $param_arr = getTagAttr($string);

        if ($tagname == 'imglist' || $tagname == 'imginfolist') {
            $param_arr['listtype'] = 'image';
        } else if ($tagname == 'specart') {
            $param_arr['listtype'] = '';
        } else if ($tagname == 'coolart') {
            $param_arr['listtype'] = 'commend';
        } else {
            $param_arr['listtype'] = '';
        }

        $arr['tagname'] = $tagname;

        $col = empty($param_arr['col']) ? 1 : $param_arr['col'];

        $addstr = '';
        if (empty($param_arr['typeid'])) {
            $addstr = '$arr["typeid"] = $typeid;';
        }
        if (substr($string, -1) == '/') {        //以“/”结尾，标签自动闭合
            return '<?php $arr = ' . var_export($param_arr, true) . ';' . $addstr . '$__arclist=get_arclist($arr,"arclist");echo "<ul class=\"cl_' . $col . '\">\r\n";foreach($__arclist as $v) {echo "<li><a href=\"".$v["arcurl"]."\">".$v["title"]."</a><font>[".mydate("m-d H:i",$v["pubdate"])."]</font></li>";}echo "</ul>";?>';
        } else {
            return '<?php $arr = ' . var_export($param_arr, true) . '; ' . $addstr . ' $__arclist=get_arclist($arr,"arclist");?><!-- foreach $__arclist -->';
        }
    }

    public static function arclistsg($string = '')
    {
        return '<?php if(false){ ?>';
    }

    public static function _list($string = '')
    {
        $param_arr = getTagAttr($string);
        $param_arr['listtype'] = '';
        $addstr = '';
        if (empty($param_arr['typeid'])) {
            $addstr = '$arr["typeid"] = $current;';
        }
        if (M == 'tags') {
            $addstr .= '$arr["tagid"] = $tagid;';
        }
        if (M == 'plus' and A == 'search') {
            $addstr .= '$arr["keyword"] = $keyword;';
        }
        $param_arr['tagname'] = 'list';
        return '<?php $arr = ' . var_export($param_arr, true) . '; ' . $addstr . ' $__list = get_arclist($arr,"list"); ?><!-- foreach $__list -->';
    }

    public static function pagelist($string = '')
    {
        $param_arr = getTagAttr($string);
        return '<?php $arr = ' . var_export($param_arr, true) . '; $pagestr=get_pagelist($arr);?>{$pagestr}';
    }

    public static function pagebreak($string = '')
    {
        return '';
    }

    public static function prenext($string = '')
    {
        $param_arr = getTagAttr($string);
        return '<?php $arr = ' . var_export($param_arr, true) . '; $__prenext = get_prenext($article_content, $arr); echo $__prenext; ?>';
    }

    public static function  tag($string)
    {
        $param_arr = getTagAttr($string);
        //如果是自己闭合的标签直接构造HTML，否则返回数据数组
        if (strpos($string, '/') !== false) {
            return '<?php $arr = ' . var_export($param_arr, true) . '; $__tag = get_tag($arr, 1); echo $__tag; ?>';
        } else {
            return '<?php $arr = ' . var_export($param_arr, true) . '; $__tag = get_tag($arr); ?> <!-- foreach $__tag -->';
        }
    }

    public static function  flinktype($string)
    {
        return '<?php $__flinktype[0] = array("id">0, "typeid"=>0,"typename"=>"友情链接"); ?><!-- foreach $__flinktype -->';
    }

    public static function  flink($string = '')
    {
        $param_arr = getTagAttr($string);
        //如果是自己闭合的标签直接构造HTML，否则返回数据数组
        if (strpos($string, '/') !== false) {
            return '<?php $arr = ' . var_export($param_arr, true) . '; $__flink = get_flink($arr, 1); echo $__flink; ?>';
        } else {
            return '<?php $arr = ' . var_export($param_arr, true) . '; $__flink = get_flink($arr, 0);?><!-- foreach $__flink -->';
        }
    }

    public static function adminname()
    {
        return '';
    }

    public static function infoguide($string = '')
    {
        $param_arr = getTagAttr($string);
        return '';
    }

    public static function infolink($string = '')
    {
        $param_arr = getTagAttr($string);
        return '';
    }

    public static function sql($string = '')
    {
        $sql = preg_replace('/\s*sql\s*=\s*([\'"])(.+?)\\1.*?/i', '$2', $string);
        return '<!-- sql ' . $sql . ' -->';
    }

    public static function loop($string = '')
    {
        $param_arr = array(
            'table' => '',
            'sort' => '',
            'row' => '10',
            'if' => '',
            'orderway' => 'desc',
        );
        $param_arr = getTagAttr($string, $param_arr);
        $table = $param_arr['table'];
        $where = empty($param_arr['if']) ? '' : 'where ' . $param_arr['if'];
        $limit = empty($param_arr['row']) ? '' : 'limit ' . $param_arr['row'];
        $sort = empty($param_arr['sort']) ? '' : 'order by ' . $param_arr['sort'] . ' ' . $param_arr['orderway'];
        $sql = "select * from {$table} {$where} {$sort} {$limit}";
        $sql = str_replace('"', "'", $sql);
        return '<!-- sql ' . $sql . ' -->';
    }

    //相关文章	
    public static function likearticle($string = '')
    {
        $string = trim($string);
        $param_arr = array(
            'row' => 10,
            'titlelen' => 30,
            'infolen' => 60,
            'mytypeid' => '',
            'imgwidth' => 120,
            'imgheight' => 90
        );
        $param_arr = getTagAttr($string, $param_arr);
        $col = empty($param_arr['col']) ? 1 : $param_arr['col'];

        $param_string = "'" . implode("', '", $param_arr) . "'";
        if (substr($string, -1) == '/') {        //以“/”结尾，标签自动闭合
            return '<?php $__likearticle=get_likearticle($article_content,' . $param_string . ');echo "<ul class=\"cl_' . $col . '\">\r\n";foreach($__arclist as $v) {echo "<li><a href=\"".$v["arcurl"]."\">".$v["title"]."</a><font>[".mydate("m-d H:i",$v["pubdate"])."]</font></li>";}echo "</ul>";?>';
        } else {
            return '<?php $__likearticle=get_likearticle($article_content,' . $param_string . ');?><!-- foreach $__likearticle -->';
        }
    }
}
