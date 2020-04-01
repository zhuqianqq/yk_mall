<?php
/**
 * 格式校验
 */
namespace util;

class ValidateHelper
{
    /**
     * 是否为手机号
     * @param $str
     * @return int
     */
    public static function isMobile($str){
        $pattern = '/^1\d{10}$/';
        return preg_match( $pattern , $str);
    }

    /**
     * 是否为email
     * @param $str
     * @return int
     */
    public static function isEmail($str){
        $pattern = '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/';
        return preg_match ( $pattern , $str);
    }

    /**
     * 是否为url
     * @param $str
     * @return int
     */
    public static function isUrl($str){
        $pattern = '/^http(s)?:\/\/\w+/';
        return preg_match ( $pattern , $str);
    }
}
