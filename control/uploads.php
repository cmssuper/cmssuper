<?php

class uploads_controller extends controller
{

	public function __construct()
	{
		parent::__construct();
		if ($this->conf['downPicture'] == 0) {
			header("Content-type: image/jpeg");
			echo $this->nopic();
			exit;
		}
	}

	public function __call($method, $ages)
	{
		$request_url = preg_replace('#\?.*$#', '', $_SERVER['REQUEST_URI']);
		$request_url = str_replace('..', '', $request_url);
		$small = strpos($request_url, '_small') !== false;
		if ($small) {
			$ext = strtolower(pathinfo($request_url, PATHINFO_EXTENSION));
			if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'img'))) {
				$this->page_404();
			}

			$file = ROOT . $request_url;
			$bigfile = ROOT . preg_replace("#_small#", "", $request_url);
			if (is_file($bigfile)) {
				$this->thumb($bigfile, $file);
				header("Location: {$request_url}?" . time());
			} else {
				$arr = explode('/', substr($request_url, 0, strrpos($request_url, '.')));
				if (is_numeric($arr[2])) {
					$this->attachment();
				} else {
					$this->page_404();
				}
			}
		} else {
			$rpos = strrpos($request_url, '.');
			if ($rpos) {
				$arr = explode('/', substr($request_url, 0, $rpos));
			} else {
				$arr = explode('/', $request_url);
			}
			if (is_numeric($arr[2])) {
				$this->attachment();
			} else {
				$this->page_404();
			}
		}
	}

	public function attachment()
	{

		$request_url = preg_replace('#\?.*$#', '', $_SERVER['REQUEST_URI']);
		$small = strpos($request_url, '_small') !== false;
		$rpos = strrpos($request_url, '.');
		if ($rpos) {
			$arr = explode('/', substr($request_url, 0, strrpos($request_url, '.')));
		} else {
			$arr = explode('/', $request_url);
		}
		$arr5 = str_replace('_small', '', $arr[5]);
		$str_md5 = addslashes($arr[3] . $arr[4] . $arr5);
		$ext = strtolower(pathinfo($request_url, PATHINFO_EXTENSION));
		if (!in_array($ext, array('', 'jpg', 'jpeg', 'png', 'gif', 'img'))) {
			$this->page_404();
		}

		$ext = empty($ext) ? '' : '.' . $ext;
		$file = ROOT . "/uploads/" . $arr[2] . "/" . $arr[3] . "/" . $arr[4] . "/" . $arr[5] . $ext;
		$bigfile = ROOT . "/uploads/" . $arr[2] . "/" . $arr[3] . "/" . $arr[4] . "/" . $arr5 . $ext;

		if (!is_file($file)) {
			if (!is_file($bigfile)) {
				$img = db::find("select * from attachment where hash='$str_md5'");
				if (!$img) {
					$this->page_404();
				}
				$this->downfile($img['url'], $bigfile);
			}
			if ($small) {
				$this->thumb($bigfile, $file);
			}
		}
		header("Location: $request_url?" . time());
	}

	private function thumb($origfile, $thumb)
	{
		$img = new image();
		$img->set_thumb(400, 300, 50);
		$res = $img->thumb($origfile, $thumb, false, false);	//原文件，生成新文件，边框，原文件是否二进制流
		if (!$res) {
			@copy($origfile, $thumb);
		}
	}

	private function downfile($url, $file)
	{
		if (!is_dir(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}
		//部分跳转的地址
		if (strpos($url, '=http://') !== false) {
			$url = urldecode(substr($url, strpos($url, '=http://') + 1));
		}
		$result = httpUnit::get($url);
		$nopic = $this->nopic();

		if ($result['httpcode'] != 200 || empty($result['html'])) {
			file_put_contents($file, $nopic);
		} else {
			file_put_contents($file, $result['html']);
		}
	}

	private function nopic(){
		return base64_decode('/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAAA8AAD/7gAOQWRvYmUAZMAAAAAB/9sAhAAGBAQEBQQGBQUGCQYFBgkLCAYGCAsMCgoLCgoMEAwMDAwMDBAMDg8QDw4MExMUFBMTHBsbGxwfHx8fHx8fHx8fAQcHBw0MDRgQEBgaFREVGh8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx//wAARCAABAAEDAREAAhEBAxEB/8QBogAAAAcBAQEBAQAAAAAAAAAABAUDAgYBAAcICQoLAQACAgMBAQEBAQAAAAAAAAABAAIDBAUGBwgJCgsQAAIBAwMCBAIGBwMEAgYCcwECAxEEAAUhEjFBUQYTYSJxgRQykaEHFbFCI8FS0eEzFmLwJHKC8SVDNFOSorJjc8I1RCeTo7M2F1RkdMPS4ggmgwkKGBmElEVGpLRW01UoGvLj88TU5PRldYWVpbXF1eX1ZnaGlqa2xtbm9jdHV2d3h5ent8fX5/c4SFhoeIiYqLjI2Oj4KTlJWWl5iZmpucnZ6fkqOkpaanqKmqq6ytrq+hEAAgIBAgMFBQQFBgQIAwNtAQACEQMEIRIxQQVRE2EiBnGBkTKhsfAUwdHhI0IVUmJy8TMkNEOCFpJTJaJjssIHc9I14kSDF1STCAkKGBkmNkUaJ2R0VTfyo7PDKCnT4/OElKS0xNTk9GV1hZWltcXV5fVGVmZ2hpamtsbW5vZHV2d3h5ent8fX5/c4SFhoeIiYqLjI2Oj4OUlZaXmJmam5ydnp+So6SlpqeoqaqrrK2ur6/9oADAMBAAIRAxEAPwD1Tir/AP/Z');
	}
}
