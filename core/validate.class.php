<?php

/**
 * @version    $Id: validate.class.php 1 2016-07-19 08:45:53Z tic $
 */

class validate
{
    
    /**
     * IP地址
     * @param string $str
     * @return boolean
     */
    public static function ip($ip)
    {
        if(preg_match('/((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)(\.((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]\d)|\d)){3}/',$ip) === false){
          return false;
        }
        return true;
    }

    /**
     * 手机号码
     *
     * @param string $str
     * @return boolean
     */
    public static function mobile($str)
    {
        return preg_match("/^[0]?(18[0-9]|13[0-9]{1}|15[0-9]{1}+)(\d{8})$/", $str);
    }

    
    /**
     * 电话号码
     *
     * @param string $phone
     * @return boolean
     */
    public static function phone($phone)
    {
        $regex = "/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/";
        return preg_match($regex,$phone);
    }
    
    /**
     * 邮政编码
     *
     * @param string $str
     * @return boolean
     */
    public static function zip($str)
    {
        return preg_match("/^[1-9]\d{5}$/", $str);
    }

    /**
     * 邮件地址
     * @param string $str
     * @return boolean
     */
    public static function email($str)
    {
        return preg_match('/^[a-z0-9]+([\+_\-\.]?[a-z0-9]+)*@([a-z0-9]+[\-]?[a-z0-9]+\.)+[a-z]{2,6}$/i', $str);
    }

    /**
     * QQ号码
     * @param <type> $str
     * @return <type>
     */
    public static function qq($str)
    {
        return preg_match("/^[1-9]{1}[0-9]{4,13}$/i", $str);
    }
    
    /**
     * 验证URL地址
     *
     * @param string $str
     * @return boolean
     */
    public static function url($str)
    {
        return preg_match("|^http://[_=&/?\.a-z0-9-]+$|i", $str);
    }

    /**
     * 全英文字母
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function alpha($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([a-z]{".$len."})$/i", $str);
        } else {
            return preg_match("/^([a-z])+$/i", $str);
        }
    }

    /**
     * 全数字
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function number($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([0-9]{".$len."})$/", $str);
        } else {
            return preg_match("/^([0-9])+$/", $str);
        }
    }

    /**
     * 数字或字母
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function num_alpha($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([a-z0-9]{".$len."})$/i", $str);
        } else {
            return preg_match("/^([a-z0-9])+$/i", $str);
        }
    }

    /**
     * 数字和字母或上划线,下划线
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function dash($str, $len = 0)
    {
        if(is_int($len) && ($len > 0)) {
            return preg_match("/^([_a-z0-9-]{".$len."})$/i", $str);
        } else {
            return preg_match("/^([_a-z0-9-])+$/i", $str);
        }
    }

    /**
     * 浮点数
     *
     * @param string $str
     * @return boolean
     */
     
    public static function float($str)
    {
        return preg_match("/^[0-9]+\.[0-9]+$/", $str);
    }

    /**
     * 中文
     *
     * @param string $str
     * @param integer $len
     * @return boolean
     */
    public static function chinese($str)
    {
        return preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $str);
    }

    /**
     * 域名
     *
     * @param string $domain
     * @return boolean
     */
    public static function domain($domain)
    {
        return preg_match('#^([0-9a-z]+(\-?[0-9a-z]+)*\.)+[a-z]{2,6}$#i', $domain);
    }

    /**
     * 用户名
     *
     * @param string $user_name
     * @return bool
     */
    public static function user_name($user_name)
    {
        return preg_match('/^[a-z0-9\x{4e00}-\x{9fa5}]+(_*[a-z0-9\x{4e00}-\x{9fa5}]+)*$/iu', $user_name)
        && strlen($user_name) >= 0 && mb_strlen($user_name, 'UTF-8') <= 30;
    }
    
    /**
     * 自定义正则验证
     *
     * @param string $str
     * @param string $type
     * type为正则表达示格式，如 /[a-z]+[\d]{3,5}/i
     * @return boolean
     */
    public static function custom($str, $type)
    {
         return preg_match($type, $str);
    }

}

?>