<?php

/**
 * @version    $Id: image.class.php 1 2016-07-19 08:45:53Z tic $
 */

class image
{
	public $source,
	       $thumb_width = null,
	       $thumb_height = null,
	       $thumb_quality = 100,
	       $watermark,
	       $watermark_ext,
	       $watermark_im,
	       $watermark_width,
	       $watermark_height,
	       $watermark_minwidth = 100,
	       $watermark_minheight = 80,
	       $watermark_position = 9,
	       $watermark_trans = 65,
	       $watermark_quality = 100;
	       
	private $imginfo,
	        $imagecreatefromfunc,
	        $imagefunc,
	        $animatedgif = 0;
	
	/*
	 * $fromstring 是否从二进制流读取
	 */
	function set_source($source, $fromstring = false)
	{
		$this->source = $source;
		if ($fromstring) {
			if (!function_exists('getimagesizefromstring')) {
				$this->imginfo = @getimagesize('data://application/octet-stream;base64,'  . base64_encode($this->source));
			} else {
				$this->imginfo = @getimagesizefromstring($this->source);
			}
		} else {
			if (!file_exists($source)) {
				return false;
			} 
			$this->imginfo = @getimagesize($this->source);
		}

		$this->animatedgif = false;
		switch($this->imginfo['mime'])
		{
			case 'image/jpeg':
				$this->imagecreatefromfunc = function_exists('imagecreatefromjpeg') ? 'imagecreatefromjpeg' : '';
				$this->imagefunc = (imagetypes() & IMG_JPG) ? 'imagejpeg' : '';
				break;
			case 'image/gif':
				$this->imagecreatefromfunc = function_exists('imagecreatefromgif') ? 'imagecreatefromgif' : '';
				$this->imagefunc = (imagetypes() & IMG_GIF) ? 'imagegif' : '';
				break;
			case 'image/png':
				$this->imagecreatefromfunc = function_exists('imagecreatefrompng') ? 'imagecreatefrompng' : '';
				$this->imagefunc = (imagetypes() & IMG_PNG) ? 'imagepng' : '';
				break;
		}
		if ($fromstring) {
			$this->imagecreatefromfunc = function_exists('imagecreatefromstring') ? 'imagecreatefromstring' : '';
		} else if($this->imginfo['mime'] == 'image/gif') 
		{
			if($this->imagecreatefromfunc && !@imagecreatefromgif($this->source)) 
			{
				$this->errno = 1;
				$this->imagecreatefromfunc = $this->imagefunc = '';
				return false;
			}
			$this->animatedgif = strpos(file_get_contents($this->source), 'NETSCAPE2.0') === false ? false : true;
		}
		return !$this->animatedgif;
	}
	
	function set_thumb($width = null, $height = null, $quality = 100)
	{
		$this->thumb_width = intval($width);
		$this->thumb_height = intval($height);
		$this->thumb_quality = min(100, intval($quality));
	}
	
	function thumb($source, $target = null, $border=false, $fromstring = false)
	{
		if(!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') || !function_exists('imagejpeg') || !$this->set_source($source, $fromstring) || !$this->imagecreatefromfunc) return false;

		if (is_null($target) && !$fromstring) {
			$target = $this->source;
		}

		list($img_w, $img_h) = $this->imginfo;
		if(!$this->thumb_width && !$this->thumb_height) return false;
		$thumb_w = $this->thumb_width ? $this->thumb_width : (int)($this->thumb_height*($img_w/$img_h));
		$thumb_h = $this->thumb_height ? $this->thumb_height : (int)($this->thumb_width*($img_h/$img_w));
		//引入原图
		$imagecreatefromfunc = $this->imagecreatefromfunc;
		$img_photo = $imagecreatefromfunc($this->source);

		if($img_w < $thumb_w || $img_h < $thumb_h){
			$thumb_photo = imagecreatetruecolor($thumb_w, $thumb_h);
			$background_color = imagecolorallocate($thumb_photo, 255, 255, 255);
			imagefill($thumb_photo, 0, 0, $background_color);
			$dx = min(intval(($thumb_w-$img_w)/2), 0);
			$dy = min(intval(($thumb_h-$img_h)/2), 0);
			$w = max($img_w, $thumb_w);
			$h = max($img_h, $thumb_h);
			imagecopyresampled($thumb_photo, $img_photo , $dx, $dy, 0, 0, $w, $h, $img_w ,$img_h);
		}else{
			//创建缩略图画布
			$thumb_photo = imagecreatetruecolor($thumb_w, $thumb_h);
			$background_color = imagecolorallocate($thumb_photo, 255, 255, 255);
			imagefill($thumb_photo, 0, 0, $background_color);
			$radio_w = $img_w/$thumb_w;
			$radio_h = $img_h/$thumb_h;
			$radio = min($radio_w,$radio_h);
			$resize_w = intval($img_w/$radio);
			$resize_h = intval($img_h/$radio);	
			$sx = intval(($resize_w - $thumb_w)/2*$radio);
			$sy = intval(($resize_h - $thumb_h)/2*$radio);
			imagecopyresampled($thumb_photo, $img_photo, 0, 0, $sx, $sy, $resize_w, $resize_h, $img_w, $img_h);
		}
		if($border){
			$bordercolor = imagecolorallocate($thumb_photo, 150, 150, 150);
			for($x=0; $x<$thumb_w; $x++) {
				imagesetpixel($thumb_photo, $x, 0, $bordercolor);
				imagesetpixel($thumb_photo, $x, $thumb_h-1, $bordercolor);
			}
			for($x=0; $x<$thumb_h; $x++) {
				imagesetpixel($thumb_photo, 0, $x, $bordercolor);
				imagesetpixel($thumb_photo, $thumb_w-1, $x, $bordercolor);
			}
		}

		clearstatcache();
		$imagefunc = $this->imagefunc;
		if ($target) {
			$result = $this->imginfo['mime'] == 'image/jpeg' ? $imagefunc($thumb_photo, $target, $this->thumb_quality) : $imagefunc($thumb_photo, $target);
		} else {
			$result = $this->imginfo['mime'] == 'image/jpeg' ? $imagefunc($thumb_photo, null, $this->thumb_quality) : $imagefunc($thumb_photo);
		}
		@imagedestroy($thumb_photo);
		@imagedestroy($img_photo);

		return $result;
	}

	function set_border($source)
	{
		if(!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled') || !function_exists('imagejpeg') || !$this->set_source($source) || !$this->imagecreatefromfunc) return false;

		list($img_w, $img_h) = $this->imginfo;

		//引入原图
		$imagecreatefromfunc = $this->imagecreatefromfunc;
		$img_photo = $imagecreatefromfunc($this->source);

		$bordercolor = imagecolorallocate($img_photo, 150, 150, 150);
		for($x=0; $x<$img_w; $x++) {
			imagesetpixel($img_photo, $x, 0, $bordercolor);
			imagesetpixel($img_photo, $x, $img_h-1, $bordercolor);
		}
		for($x=0; $x<$img_h; $x++) {
			imagesetpixel($img_photo, 0, $x, $bordercolor);
			imagesetpixel($img_photo, $img_w-1, $x, $bordercolor);
		}

		clearstatcache();
		$imagefunc = $this->imagefunc;
		$result = $this->imginfo['mime'] == 'image/jpeg' ? $imagefunc($img_photo, $source, 85) : $imagefunc($img_photo, $source);
		imagedestroy($img_photo);
		return $result;
	}

	function set_watermark($watermark, $minwidth = null, $minheight = null, $position = null, $trans = null, $quality = null)
	{
		if (!file_exists($watermark)) return false;
		
		$this->watermark = $watermark;
		$this->watermark_ext = strtolower(pathinfo($watermark, PATHINFO_EXTENSION));
		if (!in_array($this->watermark_ext, array('gif', 'png')) || !is_readable($this->watermark)) return false;
		
		$this->watermark_im	= $this->watermark_ext == 'png' ? @imagecreatefrompng($this->watermark) : @imagecreatefromgif($this->watermark);
		if(!$this->watermark_im) return false;
		
		$watermarkinfo	= @getimagesize($this->watermark);
		$this->watermark_width	= $watermarkinfo[0];
		$this->watermark_height	= $watermarkinfo[1];
		
		if (!is_null($minwidth)) $this->watermark_minwidth = intval($minwidth);
		if (!is_null($minheight)) $this->watermark_minheight = intval($minheight);
		if (!is_null($position)) $this->watermark_position = intval($position);
		if (!is_null($trans)) $this->watermark_trans = min(100, intval($trans));
		if (!is_null($quality)) $this->watermark_quality = min(100, intval($quality));
	}
	
	function watermark($source, $target = null)
	{
		if (!$this->set_source($source) || ($this->watermark_minwidth && $this->imginfo[0] <= $this->watermark_minwidth) || ($this->watermark_minheight && $this->imginfo[1] <= $this->watermark_minheight) || !function_exists('imagecopy') || !function_exists('imagealphablending') || !function_exists('imagecopymerge')) return false;
	
		if (is_null($target)) $target = $source;

		list($img_w, $img_h) = $this->imginfo;

		$wmwidth = $img_w - $this->watermark_width;
		$wmheight = $img_h - $this->watermark_height;
		if($wmwidth < 10 || $wmheight < 10) return false;
		switch($this->watermark_position)
		{
			case 1:
				$x = +5;
				$y = +5;
				break;
			case 2:
				$x = $wmwidth / 2;
				$y = +5;
				break;
			case 3:
				$x = $wmwidth - 5;
				$y = +5;
				break;
			case 4:
				$x = +5;
				$y = $wmheight / 2;
				break;
			case 5:
				$x = $wmwidth / 2;
				$y = $wmheight / 2;
				break;
			case 6:
				$x = $wmwidth;
				$y = $wmheight / 2;
				break;
			case 7:
				$x = +5;
				$y = $wmheight - 5;
				break;
			case 8:
				$x = $wmwidth / 2;
				$y = $wmheight - 5;
				break;
			default:
				$x = $wmwidth - 5;
				$y = $wmheight - 5;
		}
		$im = imagecreatetruecolor($img_w, $img_h);
		$imagecreatefromfunc = $this->imagecreatefromfunc;
		$source_im = @$imagecreatefromfunc($this->source);
		imagecopy($im, $source_im, 0, 0, 0, 0, $img_w, $img_h);
			
		if($this->watermark_ext == 'png')
		{
			imagecopy($im, $this->watermark_im, $x, $y, 0, 0, $this->watermark_width, $this->watermark_height);
		}
		else
		{
			imagealphablending($this->watermark_im, true);
			imagecopymerge($im, $this->watermark_im, $x, $y, 0, 0, $this->watermark_width, $this->watermark_height, $this->watermark_trans);
		}
		clearstatcache();
		
		$imagefunc = $this->imagefunc;
		$result = $this->imginfo['mime'] == 'image/jpeg' ? $imagefunc($im, $target, $this->watermark_quality) : $imagefunc($im, $target);
		@imagedestroy($im);
		return $result;
	}
}