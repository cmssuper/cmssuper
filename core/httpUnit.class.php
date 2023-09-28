<?php

class httpUnit
{
	public static function get($url)
	{
		return self::post($url);
	}

	public static function post($url, $post_data = array(), $signkey = '')
	{
		if (!empty($post_data)) {
			$post_method = 'POST';
			$post_type = 1;
		} else {
			$post_method = 'GET';
			$post_type = 0;
		}
		if (!empty($signkey)) {
			$post_data['sign'] = self::sign($post_data, $signkey);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, self::userAgent());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $post_method);
		curl_setopt($ch, CURLOPT_REFERER, self::get_referer($url));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, $post_type);
		if (!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
		}
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

		if (strpos($url, 'https://') !== false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
			curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
			curl_setopt($ch, CURLOPT_CAINFO, DATA . '/resource/cacert.pem');
		}
		defined('CURLOPT_TIMEOUT_MS') && curl_setopt($ch, CURLOPT_TIMEOUT_MS, 60000);

		if (strpos($url, 'bing.com') !== false) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Accept-Language: zh-cn",
				"Cookie: MUID=1F59FA33ED2B6DDF2151F4C0EC546C0D; MUIDB=1F59FA33ED2B6DDF2151F4C0EC546C0D;"
			));
		}

		$result = curl_exec($ch);
		if ($result !== false) {
			$return['httpcode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$return['charset'] = self::get_charset(curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$return['header'] = substr($result, 0, $headerSize);
			$return['html'] = substr($result, $headerSize);
			if ($return['httpcode'] == "301" || $return['httpcode'] == "302") {
				if (preg_match("#Location: (.*)#", $return['header'], $mt)) {
					return self::post(trim($mt[1]), $post_data, $signkey);
				}
			}
		} else {
			$err = curl_error($ch);
			$return['httpcode'] = 0;
			$return['errMsg'] = $err;
		}
		curl_close($ch);
		return $return;
	}

	private static function sign($para = array(), $signkey)
	{
		ksort($para);
		reset($para);
		$arg  = "";
		foreach ($para as $key => $val) {
			if ($key == "sign" || $key == "m" || $key == "a" || is_array($val)) continue;
			$arg .= $key . "=" . $val . "&";
		}
		$arg = substr($arg, 0, strlen($arg) - 1);
		return md5($arg . $signkey);
	}

	private static function userAgent()
	{
		$uas = array(
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0) Gecko/20100101 Firefox/6.0',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/605.1.15',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.163 Safari/535.1'
		);
		return $uas[array_rand($uas)];
	}

	private static function get_charset($string)
	{
		if ($string) {
			if (preg_match("#charset=(gb2312|gbk|utf-8)#si", $string, $mt)) {
				return strtolower($mt[1]);
			}
			if (preg_match("#charset=\"(gb2312|gbk|utf-8)\"#si", $string, $mt)) {
				return strtolower($mt[1]);
			}
		}
		return false;
	}

	private static function get_referer($url)
	{
		$host = 'http://' . parse_url($url, PHP_URL_HOST);
		return $host;
	}
}
