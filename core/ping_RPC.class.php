<?php

class ping_RPC
{

	var $url;
	var $arcurls;
	var $xml;

	function baidutui()
	{
		$result = array();
		$site = parse_url($this->arcurls[0], PHP_URL_HOST);
		if (!empty($GLOBALS['G']['baidu_tui_token'])) {
			$api = 'http://data.zz.baidu.com/urls?site=' . $site . '&token=' . $GLOBALS['G']['baidu_tui_token'];
			$ch = curl_init();
			$options =  array(
				CURLOPT_URL => $api,
				CURLOPT_POST => false,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POSTFIELDS => implode("\n", $this->arcurls),
				CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
				CURLOPT_TIMEOUT => 8,
				CURLOPT_CONNECTTIMEOUT => 8,
			);
			curl_setopt_array($ch, $options);
			$content = curl_exec($ch);
			curl_close($ch);
			$content = !empty($content) ? json_decode($content, true) : '';
			if ($content) {
				if (isset($content['error'])) {
					$result['status'] = -1;
					$result['errCode'] = 102; // token错误
				} elseif (!empty($content['not_same_site'])) {
					$result['status'] = -1;
					$result['errCode'] = 103; //not_same_site
				} elseif (!empty($content['not_valid'])) {
					$result['status'] = -1;
					$result['errCode'] = 104; //not_valid
				} else {
					$result['status'] = 1;
					$result['remain'] = $content['remain'];
				}
			} else {
				$result['status'] = -1;
				$result['errCode'] = 101; //百度接口异常
			}
		} else {
			$result['status'] = 0;
			$result['errCode'] = 0;
		}
		return $result;
	}

	function ping()
	{
		$yuming = db::find("select id,name from yuming order by rand()");
		if ($yuming) {
			$list = db::select("select id from content_{$yuming['id']} where ping_status=0 order by addtime desc limit 30");
			if ($list) {
				$update_ids = $arcurls = array();
				foreach ($list as $v) {
					$update_ids[] = $v['id'];
					$arcurls[] = "http://" . $yuming['name'] . "/article/$v[id].html";
				}
				$this->arcurls = $arcurls;
				$errCode = 0;
				$result = $this->baidutui();
				$update_id = implode(',', $update_ids);

				if ($result['status'] == 1) {
					db::query("update content_{$yuming['id']} set ping_status = 1 where id in ($update_id)");
				} else {
					$errCode = $result['errCode'];
					db::query("update content_{$yuming['id']} set ping_status = -1,ping_errMsg='$errCode' where id in ($update_id)");
				}
			}
		}
	}
}
