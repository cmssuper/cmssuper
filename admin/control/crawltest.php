<?php

if (!defined('IN_SYS')) exit('Access Denied');

class crawltest_controller extends admincp
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$gp = gp('listurl,articlerule,titlerule,contentrule,norule,testIdx', false);
		$gp = filter::stripslashes($gp);
		$testIdx = (int)$gp['testIdx'];
		$temp_urllist = array();
		if (preg_match("#\[([1-9]\d*)\-([1-9]\d*)\]#", $gp['listurl'], $mt1)) {
			$min = $mt1[1];
			$max = $mt1[2];
			$temp_urllist = array();
			for ($i = $min; $i <= $max; $i++) {
				$temp_urllist[] = preg_replace("#\[([1-9]\d*)\-([1-9]\d*)\]#", $i, $gp['listurl']);
			}
			$url = preg_replace("#\[([1-9]\d*)\-([1-9]\d*)\]#", $min, $gp['listurl']);
		} else {
			$temp_urllist[] = $gp['listurl'];
			$url = $gp['listurl'];
		}
		$result = httpUnit::get($url);

		if ($result['httpcode'] == 200) {
			$data = $result['html'];
		} else {
			echo "列表抓取失败";
			exit;
		}

		//判断编码
		$unicode = htmlBase::get_charset($data);
		//转换编码
		if (!empty($unicode) && $unicode !== "utf-8") {
			$data = mb_convert_encoding($data, "utf-8", $unicode);
		}
		$html2 = htmlHelper::matchcontent($data, $gp['articlerule']);

		$listUrls_arr = array();
		if (preg_match_all("#href=[\"'\s]*([^\s\"'\\\\]*)[\"'\s]#", $html2, $mt3)) {
			$listUrls_arr = $mt3[1];
		}
		if (!empty($gp['norule'])) {
			$norule = explode("\n", $gp['norule']);
			foreach ($norule as  $k => $value) {
				$norule[$k] = trim($value);
			}
			$norule = array_filter($norule);
			foreach ($listUrls_arr as $k => $value) {
				foreach ($norule as $x => $y) {
					if (stripos($value, $y) !== false) {
						unset($listUrls_arr[$k]);
					}
				}
			}
		}

		$title = "";
		$body = "";
		$res_un = array_unique($listUrls_arr);
		$res_val = array_values($res_un);
		if (!empty($res_val[$testIdx])) {
			$res = $this->getBody($res_val[$testIdx], $gp);
			$title = $res['title'];
			$body = $res['body'];
		}
?>
		<div style="font-weight:bold;font-size:16px;background: #ccc; line-height: 30px;">◇定向采集::采集规则测试匹配到的列表地址（前10个）</div>
		<?php foreach ($temp_urllist as  $v) : ?>
			<div style="padding-left:20px;"><?php echo $v ?></div>
		<?php endforeach ?>
		<div style="color:red;padding-left:20px;"><?php if (empty($temp_urllist)) echo '匹配不到列表地址'; ?></div>
		<div style="margin-top:50px;font-weight:bold;font-size:16px;background: #ccc; line-height: 30px;">◇第一个列表页匹配到的文章地址（前10个）</div>
		<?php foreach ($res_val as $key => $v) : ?>
			<?php if ($key <= 10) : ?>
				<div style="padding-left:20px;">
					<?php echo $v ?>
					<a href="javascript:void(0)" onclick="mainApp.testRule(<?php echo $key ?>)" target="_self">[测试该页]</a>
				</div>
			<?php endif ?>
		<?php endforeach ?>
		<div style="color:red;padding-left:20px;"><?php if (empty($res_val)) echo '匹配不到文章地址'; ?></div>
		<?php if (!empty($res_val[$testIdx])) : ?>
			<div style="margin-top:50px;font-weight:bold;font-size:16px;background: #ccc; line-height: 30px;">◇测试采集文章:(<?php echo $res_val[$testIdx] ?>)</div>
			<div style=" margin:0 auto;">
				<div align="center" style="font-size:16px;font-weight:bold;"><?php echo $title ?></div>
				<div style="color:red;padding-left:20px;"><?php if (empty($title)) echo '匹配不到文章标题'; ?></div>
				<div style="padding:20px;"><?php echo $body ?></div>
				<div style="color:red;padding-left:20px;"><?php if (empty($body)) echo '匹配不到文章内容'; ?></div>
			</div>
		<?php endif ?>
<?php
	}

	public function getBody($url, $res)
	{
		$url = htmlHelper::fix_path($res['listurl'], $url);
		$result = httpUnit::get($url);
		if ($result['httpcode'] == 200) {
			$data = $result['html'];
		} else {
			echo "无法采集此文章";
			exit;
		}

		//判断编码
		$unicode = htmlBase::get_charset($data);
		//转换编码
		if (!empty($unicode) && $unicode !== "utf-8") {
			$data = mb_convert_encoding($data, "utf-8", $unicode);
		}
		if (empty($unicode)) {
			echo "无法识别编码：$url";
			exit;
		}

		//匹配文章标题
		$title = htmlHelper::matchcontent($data, $res['titlerule']);
		$title = str_replace(array("\r\n", "\r", "\n"), "", $title);

		//匹配文章内容
		$body = htmlHelper::matchcontent($data, $res['contentrule']);

		//文章内容处理
		$filecontent = preg_replace('#<[/]?a[^>]*?>#', "", $body);
		$filecontent = preg_replace('#<[/]?div[^>]*?>#', "", $filecontent);
		$filecontent = preg_replace('#<([a-z]*?)\s[^>]*></\\1>#', "", $filecontent);

		//图片处理
		$filecontent = helper::convert_images_patch($filecontent, '', $res['listurl']);

		//过滤文章内容
		$filecontent = htmlBase::tag_filter($filecontent);
		//过滤data-original
		// $filecontent = preg_replace('/data-original=\"(.*?)\"/is', '',$filecontent);
		$filecontent = str_replace("</p></p>", "</p>", $filecontent);
		$filecontent = str_replace("<p><p align=\"center\">", "<p align=\"center\">", $filecontent);

		$result['title'] = $title;
		$result['body'] = $filecontent;
		return $result;
	}
}
