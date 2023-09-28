<?php

class helper
{
	public static function delDir($dir)
	{
		if (!is_dir($dir)) return;
		$dh = opendir($dir);
		while (false !== $file = readdir($dh)) {
			if ($file != "." && $file != "..") {
				$fullpath = $dir . "/" . $file;
				if (!is_dir($fullpath)) {
					unlink($fullpath);
				} else {
					self::delDir($fullpath);
				}
			}
		}
		closedir($dh);
		rmdir($dir);
	}

	//转换文章内图片地址
	public static function convert_images_patch($body, $alt = '', $baseUrl = '', $yuming_id = '')
	{
		$GLOBALS['__tmp_link'] = $baseUrl;
		$GLOBALS['img_ym_id'] = $yuming_id;
		if ($GLOBALS['G']['downPicture'] == 0) {
			$body = preg_replace("#<img\s[^>]*>#", "", $body);
		} else {
			$body = preg_replace_callback('/src=\"(.*?)\"/is', function ($data) {
				if ($GLOBALS['G']['downPicture'] == 1) {
					$str_img = convert_images_url_path($data[1], $GLOBALS['__tmp_link'], $GLOBALS['img_ym_id']);
					if ($str_img) {
						return "src=\"{$str_img}\" ";
					} else {
						return "";
					}
				} elseif ($GLOBALS['G']['downPicture'] == 2) {
					$data[1] = htmlHelper::fix_path($GLOBALS['__tmp_link'], $data[1]);
					return "src=\"{$data[1]}\" ";
				} else {
					return "";
				}
			}, $body);
		}
		return $body;
	}

	//同义词替换
	public static function replace_word($body, $yuming_id)
	{
		$list_rword = db::select("select * from reword where yuming_id='$yuming_id' or yuming_id=0");
		$GLOBALS['protecttag'] = array();
		$GLOBALS['protecttagIndex'] = 0;
		$body = preg_replace_callback(
			'#<a.*?>.*?</a>|<img.*?>|\[img\].*?\[/img\]|\[url\].*?\[/url\]#is',
			function ($data) {
				$GLOBALS['protecttagIndex']++;
				$GLOBALS['protecttag'][$GLOBALS['protecttagIndex']] = $data[0];
				return '~' . $GLOBALS['protecttagIndex'] . '~';
			},
			$body
		);

		#inline-ignore
		$body = replace_word($list_rword, $body);
		//释放保护
		return preg_replace_callback(
			'#~([0-9]{0,4})~#is',
			function ($data) {
				if (isset($GLOBALS['protecttag'][$data[1]])) {
					return $GLOBALS['protecttag'][$data[1]];
				}
				return $data[0];
			},
			$body
		);
	}

	//关键词优化
	public static function seoword($body, $yuming_id)
	{
		$seo = db::find("select * from seoconfig where yuming_id='$yuming_id'");
		//未设置优化
		if (!$seo) return $body;
		$seo['seoWordNum'] = intval($seo['seoWordNum']);
		if (empty($seo['seoWord']) || !$seo['seoWordNum']) {
			return $body;
		}
		//伪原创比例
		$ys = crc32($body['title']) % 100;
		$seoWordScale = intval($seo['seoWordScale']);
		if ($seoWordScale < $ys) {
			return $body;
		}
		//取出词语
		$sw_arr = str_replace(array(',', '，'), "\n", $seo['seoWord']);
		$sw_arr = explode("\n", $sw_arr);
		foreach ($sw_arr as  $k => $value) {
			$sw_arr[$k] = trim($value);
		}
		$sw_arr = array_filter($sw_arr);
		$idx = mt_rand(0, max(0, count($sw_arr) - $seo['seoWordNum']));
		$sw_arr = array_slice($sw_arr, $idx, $seo['seoWordNum']);
		if (!empty($sw_arr)) {
			//插入文章
			$sw_arr_num = count($sw_arr);
			$body_array = explode('，', $body['body']);
			$body_s_count = count($body_array);
			$snum = ceil(($body_s_count / 2) / $sw_arr_num);
			#inline-ignore
			foreach ($sw_arr as $key => $value) {
				$k = $snum * $key + 1;
				if (isset($body_array[$k])) {
					$body_array[$k] = $value . $body_array[$k];
				}
			}
			$body['body'] = join("，", $body_array);
			//插入标题
			if ($seo['seoTitle'] == '1') {
				if ($seo['seoTitlex'] == '1') {
					$body['title'] = strip_tags($sw_arr[0]) . $body['title'];
				} elseif ($seo['seoTitlex'] == '2') {
					$body['title'] = $body['title'] . strip_tags($sw_arr[0]);
				} elseif ($seo['seoTitlex'] == '3') {
					$tlen = mb_strlen($body['title'], 'utf-8');
					$blen = mb_strlen($body['body'], 'utf-8');
					$sp = $blen % $tlen;
					$body['title'] = mb_substr($body['title'], 0, $sp, 'utf-8') . strip_tags($sw_arr[0]) . mb_substr($body['title'], $sp, $tlen - $sp, 'utf-8');
				}
			}
		}
		return $body;
	}
}
