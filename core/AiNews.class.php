<?php

class AiNews
{

    public $dat, $name, $title;

    function __construct()
    {
        $file = DATA . '/resource/AIdata.dat';
        $fp = fopen($file, 'r');
        $this->dat = array();
        while (!feof($fp)) {
            $line = trim(fgets($fp, 512 * 1024));
            $line = trim($line);
            if ($line == "") {
                continue;
            }
            $pos = strpos($line, ']');
            if ($pos) {
                $i = substr($line, 1, $pos - 1);
                $e = substr($line, $pos + 1);
                $this->dat[$i][] = $e;
            }
        }
        fclose($fp);
        $this->dat['A'] = explode(',', $this->dat['A'][0]);
        $this->dat['B'] = explode(',', $this->dat['B'][0]);
        $this->dat['C'] = explode(',', $this->dat['C'][0]);
    }

    function title($word, $titleBetter)
    {
        $w = $this->line('T', $word);
        if ($titleBetter) {
            $qz = $this->line('QZ', $word);
            $hz = $this->line('HZ', $word);
            $this->title = $qz . $w . '，' . $hz;
        } else {
            $this->title = $w;
        }
        return $this->title;
    }

    function body($word, $bodyLength, $bodyBetter)
    {
        $tmp = "<p>";
        $p = true;
        while (mb_strlen($tmp, 'utf-8') < $bodyLength) {
            $rand = mt_rand(0, 100);
            if ($rand < 5 && $p == false) {
                $p = true;
                $tmp .= "。</p>\r\n<p>";
            } elseif ($rand < 20) {
                if ($p == false) {
                    $tmp .= '。';
                }
                $tmp .= $this->line('M', $word, $bodyBetter);
                $p = false;
            } else {
                if ($p == false) {
                    $tmp .= mt_rand(0, 5) == 0 ? '。' : '，';
                }
                $p = false;
                if($rand<25){
                    $tmp .= $this->title;
                }else{
                    $tmp .= $this->line('L', $word, $bodyBetter);
                }
            }
        }
        $line = $this->line('J', $word);
        $tmp .= "。</p>\r\n<p>" . str_replace('{x}', $word, $line) . "。</p>";
        return $tmp;
    }

    function line($type, $word = false, $bodyBetter = false)
    {
        $line = $this->dat[$type][mt_rand(0, count($this->dat[$type]) - 1)];
        if (strpos($line, '{d}') !== false) {
            $num = mt_rand(1, 99);
            $line = str_replace('{d}', $num, $line);
        }
        if (strpos($line, '{r}') !== false) {
            $a = array('：', '，');
            $r = $this->name() . $this->line('R') . $a[mt_rand(0, 1)];
            $line = str_replace('{r}', $r, $line);
        }
        if ($type == 'M') {
            $line .= "。" . $this->line('H');
        }
        if ($bodyBetter) {
            $cCount = count($this->dat['C']) - 1;
            foreach ($this->dat['C'] as $v) {
                $line = str_replace($v, $this->dat['C'][mt_rand(0, $cCount)], $line);
            }
        }
        if ($word && strpos($line, '{x}') !== false) {
            $line = str_replace('{x}', $word, $line);
        }
        return $line;
    }

    function name()
    {
        $a = $this->line('A');
        $b = $this->line('B');
        return $a . $b . $b;
    }
}
