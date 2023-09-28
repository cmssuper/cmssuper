<?php

define('DS', DIRECTORY_SEPARATOR);
define('REMOTEAPI', "http://api.yumingcms.com");
define('VERSION', '4.3.0');

include __DIR__ . "/functions.php";
include __DIR__ . "/dede.func.php";
spl_autoload_register('super__autoload', true, true);

if (version_compare(PHP_VERSION, '5.3.0', '<')) throw new Exception("Worning: PHP VERSION NOT SUPPORT!", 0);
define("IS_POST", isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' || gp('callback') != '');
define("IS_AJAX", isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    throw new Exception("php version not supported", 0);
}

$_GET = filter::input($_GET);
$_POST = filter::input($_POST);
$_COOKIE = filter::input($_COOKIE);
$_REQUEST = filter::input($_REQUEST);

$basenameUri = $_SERVER['REQUEST_URI'];
if (strpos($basenameUri, '?') !== false) {
    $sp = '?';
} else {
    $sp = '/';
}

// 伪静态未设置提示
if (strpos($basenameUri, '/systest') !== false) {
    echo 'success';
    exit;
}

// 文件名
$basename = str_replace('.min.', '.', substr($basenameUri, strrpos($basenameUri, $sp) + 1));

// 兼容dedecms cdn文件
$javascriptMap = array(
    'jquery.js' => 'https://cdn.staticfile.org/jquery/1.9.1/jquery.min.js',
    'jquery.lazyload.js' => 'https://cdn.staticfile.org/jquery.lazyload/1.9.1/jquery.lazyload.min.js',
    'jquery.pjax.js' => 'https://cdn.staticfile.org/jquery.pjax/2.0.1/jquery.pjax.min.js',
    'font-awesome.css' => 'https://cdn.staticfile.org/font-awesome/3.2.1/css/font-awesome.min.css',
    'favicon.ico' => '/static/common/images/favicon.ico',
    'favicon.ico' => '/static/common/images/favicon.ico',
);
if (isset($javascriptMap[$basename])) {
    header("Location: $javascriptMap[$basename]");
    exit;
}

if (gp('m') != 'sysInit') {
    include DATA . DS . 'config.php';
}
if (empty($dbname)) {
    if (gp('m') != 'sysInit') {
        header("Location: /?m=sysInit");
    } else {
        sysInit::start();
    }
    exit;
}

class db
{
    static $db, $sqls;

    static function esc($string)
    {
        return self::$db->esc($string);
    }

    static function setquery($sql)
    {
        return self::$db->setquery($sql);
    }

    static function query($sql)
    {
        return self::$db->query($sql);
    }

    static function fetch($queryID = false, $type = MYSQLI_ASSOC)
    {
        return self::$db->fetch($queryID, $type);
    }

    static function select($sql, $key = '')
    {
        return self::$db->select($sql, $key);
    }

    static function find($sql, $limit = true)
    {
        return self::$db->find($sql, $limit);
    }

    static function getfield($sql, $limit = true)
    {
        return self::$db->getfield($sql, $limit);
    }

    static function sqls()
    {
        return self::$db->sqls;
    }

    static function getlastsql()
    {
        return self::$db->getlastsql();
    }

    static function insert_id()
    {
        return self::$db->insert_id();
    }

    static function affected_rows()
    {
        return self::$db->affected_rows();
    }

    static function close()
    {
        return self::$db->close();
    }

    static function free_result($queryID = false)
    {
        return self::$db->free_result($queryID);
    }

    static function version()
    {
        return self::$db->version();
    }

    static function error()
    {
        return self::$db->error();
    }

    static function errno()
    {
        return self::$db->errno();
    }
}

class e
{
    public static $error = array();

    public static function loader_handler($n)
    {
        require $n . '.' . pathinfo(__FILE__, PATHINFO_EXTENSION);
    }

    public static function exception_handler($e)
    {
        $errstr = $e->getMessage();
        $errfile = $e->getFile();
        $errline = $e->getLine();
        self::exception_error_handler(0, $errstr, $errfile, $errline);
    }

    public static function exception_shutdown_handler()
    {
        if (self::$error) self::display(join("<br/>", self::$error));
    }

    public static function exception_error_handler($errno, $errstr, $errfile, $errline)
    {
        if (isset($_SERVER['DEBUG'])) {
            self::$error[] = "$errstr in <b>$errfile</b> on line <b>$errline</b>";
        } else {
            if (in_array($errno, array(0, E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
                controller::page_503();
                self::$error[] = $errstr;
            }
        }
    }

    public static function display($string)
    {
        echo '<style>html,body{margin:0;}</style><div style="background:rgb(18, 107, 174);color:#FFF;text-align:center;font-size:24px;padding:100px;">', $string, "</div>";
    }
}

db::$db = new mysql($dbhost, $dbname, $dbuser, $dbpassword);
