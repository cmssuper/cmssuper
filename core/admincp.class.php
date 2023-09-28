<?php

class admincp extends controller
{
    public $user, $sites, $siteId;

    public function __construct()
    {
        parent::__construct();
        $this->user = self::adminInfo();
        $this->siteId = gp('siteId');
        $sites_array = db::select("select * from yuming order by id desc");
        $this->sites = array_column($sites_array, null, 'id');
        if (!empty($this->sites)) {
            if ($this->siteId && !isset($this->sites[$this->siteId])) {
                self::json(array('error' => '-1', 'tip' => '站点不存在，请选择操作站点'));
            }
        } else {
            if (!in_array(M, array('index', 'system', 'database', 'getCommon', 'stats')) && (M != 'sites' && A != 'edit')) {
                self::json(array('error' => '-1', 'errorType' => 'noSite', 'tip' => '请先创建网站'));
            }
        }
    }

    public static function login($username, $password)
    {
        header("P3P:CP=CAO PSA OUR");
        $result['status'] = 0;
        $rs = db::find("select * from admin where username='$username'");
        if ($rs) {
            $password_encode = self::password_encode($username, $password, $rs['salt']);
            if ($rs['status'] != 1) {
                $result['tip'] = "账号已关闭";
            } elseif ($rs['password'] != $password_encode) {
                $result['tip'] = "用户名或密码错误";
            }
        } else {
            $result['tip'] = "用户名或密码错误";
        }
        if (empty($result['tip'])) {
            $result['status'] = 1;
            $result['ssid'] = session_id();
            $_SESSION['cp_islogin'] = true;
            $_SESSION['cp_mid'] = $rs['id'];
        }
        return $result;
    }

    public static function logout()
    {
        unset($_SESSION['cp_islogin']);
        unset($_SESSION['cp_mid']);
    }

    public static function adminInfo()
    {
        if (isset($_SESSION['cp_islogin']) && $_SESSION['cp_islogin'] == true) {
            $result = db::find("select id,username from admin where id='$_SESSION[cp_mid]' AND status=1 ");
            if ($result) {
                return $result;
            }
        }
        self::json(array('error' => '-1', 'errorType' => 'noLogin', 'tip' => '还没有登陆', 'redirect' => '/'));
    }

    public static function password_encode($username, $password, $salt)
    {
        return md5($username . md5($password) . $salt);
    }
}
