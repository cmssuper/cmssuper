<?php

if (!defined('IN_SYS')) exit('Access Denied');

class index_controller extends controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        require dirname(__DIR__) . "/templates/index.html";
    }

    public function login()
    {
        $username = gp('username');
        $password = gp('password');
        $result = admincp::login($username, $password);
        if ($result['status']) {
            self::json(
                array(
                    'success' => array(
                        "token" =>  $result['ssid'],
                    ),
                    'tip' => "登陆成功"
                )
            );
        } else {
            self::json(array('error' => "authError", 'tip' => $result['tip']));
        }
    }

    public function logout()
    {
        admincp::logout();
        self::json(array('success' => true));
    }
}
