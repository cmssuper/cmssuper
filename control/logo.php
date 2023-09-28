<?php

class logo_controller extends controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __call($method, $ages)
    {
        if ($GLOBALS['G']['site']['logo'] != '') {
            header("Location: " . $GLOBALS['G']['site']['logo']);
            return;
        }
        header("Content-type: image/png");

        $folder = ROOT . '/uploads/logo/';
        $file = $folder . $this->siteId . '.png';

        if (is_file($file) && time() - filemtime($file) < 300) {
            echo file_get_contents($file);
            exit;
        }
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $height = gp('h');
        $w = 260;
        $h = 70;

        if ($height && $height != 70) {
            $w = round($height * 260 / 70);
            $h = $height;
        }

        $sitename = $this->site['sitename'];
        $url = $this->site['name'];
        $ico = mb_substr($sitename, 0, 1, 'utf-8');

        $im = imagecreatetruecolor($w, $h); //图片大小
        imagealphablending($im, false);
        imagesavealpha($im, true);

        $color = imagecolorallocate($im, 255, 255, 255);
        $imbg = imagecreatefrompng(DATA . "/font/logo.png");
        imagesavealpha($imbg, true);

        if ($height && $height != 70) {
            imagecopyresampled($im, $imbg, 0, 0, 0, 0, $w, $h, 260, 70);
        } else {
            $im = $imbg;
        }

        //设置颜色
        $sncolor = ImageColorAllocate($im, 51, 51, 51); //文字颜色
        $urlcolor = ImageColorAllocate($im,  102, 102, 102); //网址颜色
        $icocolor = ImageColorAllocate($im,  50, 157, 204); //图标颜色
        $ttf = DATA . "/font/webname.ttf"; //网站名称字体
        //输出图片
        $bili = $h / 70;

        if (function_exists('ImageTTFText')) {
            ImageTTFText($im, floor(22 * $bili), 0, 75 * $bili, 35 * $bili, $sncolor, $ttf, $sitename); //文字设置
            ImageTTFText($im, floor(12 * $bili), 0, 75 * $bili, 60 * $bili, $urlcolor, $ttf, $url); //网址设置
            ImageTTFText($im, floor(35 * $bili), 0, 15 * $bili, 52 * $bili, $icocolor, $ttf, $ico); //ico设置
        } else {
            imagestring($im, 5, 75 * $bili, 15 * $bili, $sitename, $sncolor);
            imagestring($im, 5, 75 * $bili, 50 * $bili, $url, $urlcolor);
            imagestring($im, 5, 30 * $bili, 30 * $bili, $ico, $icocolor);
        }
        imagepng($im, $file);
        imageDestroy($im);
        echo file_get_contents($file);
    }
}
