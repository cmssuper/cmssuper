<?php

class loader
{

    public static function init()
    {
        $loader = new loader();
        $loader->environment();
        $loader->router();
        return $loader;
    }

    public function run()
    {
        $file = defined('APPNAME') ? ROOT . '/' . APPNAME . '/control/' . M . '.php' : ROOT . '/control/' . M . '.php';
        if (is_file($file)) {
            require $file;
            $class = M . '_controller';
            $object = new $class;
            if (method_exists($object, A) || method_exists($object, '__call')) {
                call_user_func(array($object, A));
            } else {
                controller::page_404();
                exit;
            }
        } else {
            $file = defined('APPNAME') ? ROOT . '/' . APPNAME . '/control/plus.php' : ROOT . '/control/plus.php';
            require $file;
            $object = new plus_controller;
            if (method_exists($object, M) || method_exists($object, '__call')) {
                call_user_func(array($object, M));
            } else {
                controller::page_404();
                exit;
            }
        }
    }

    public function environment()
    {

        if (!isset($_SERVER['REQUEST_URI'])) {
            if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            } else {
                $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            }
        }
        $_SERVER['REQUEST_URI'] = str_replace('/index.php', '', $_SERVER['REQUEST_URI']);
        $temp = strtoupper(urldecode(urldecode($_SERVER['REQUEST_URI'])));
        if (strpos($temp, '<') !== false || strpos($temp, '"') !== false || strpos($temp, "'") !== false || strpos($temp, "*") !== false || strpos($temp, 'CONTENT-TRANSFER-ENCODING') !== false) {
            controller::page_404();
        }
    }

    public function router()
    {
        if ($request_uri_fixed = trim(preg_replace('#\?.*$#', '', $_SERVER['REQUEST_URI']), '/')) {
            $request_uri_variable = explode('/', $request_uri_fixed);
            foreach ($request_uri_variable as $key => $value) {
                if (defined('APPNAME')) {
                    $keyidx = $key - 1;
                } else {
                    $keyidx = $key;
                }
                if ($keyidx == 0) {
                    $_GET['m'] = $value;
                } elseif ($keyidx == 1) {
                    $_GET['a'] = $value;
                } elseif ($keyidx >= 2 && $keyidx % 2 == 0 && isset($request_uri_variable[$keyidx + 1]) && preg_match('#^[a-z0-9_]*$#', $value)) {
                    $_GET[$value] = $_REQUEST[$value] = $request_uri_variable[$keyidx + 1];
                }
            }
        }
        $m = $a = 'index';
        if (!empty($_GET['m']) && preg_match('#^[a-z0-9_]*#i', $_GET['m'], $mt)) {
            $m = $mt[0];
        }
        define('M', $m);
        if (!empty($_GET['a']) && preg_match('#^[a-z0-9_]*#i', $_GET['a'], $mt)) {
            $a = $mt[0];
        }
        define('A', $a);
    }
}
