<?php
namespace wstmart\api\service;
use util\Tools;

/**
 * Class BaseService
 * @package app\service
 */
Class BaseService
{
    /**
     * @var string 日志名
     */
    protected $logName = "";


    public function __construct()
    {
    }

    /**
     * @param string $url
     * @param string $params
     * @return mixed
     */
    protected function fullUrl($url, $params = "")
    {
        if (!$params) {
            return $url;
        }
        if (!is_array($params)) {
            $params = [$params];
        }
        $params = array_merge(array($url), $params);
        $url = call_user_func_array('sprintf', $params);
        return $url;
    }

    /**
     * 失败返回
     * @param string $msg
     * @param int $code
     * @return array
     */
    protected function failData($msg = '操作失败', $code = 500)
    {
        return [
            'code' => $code,
            'msg' => $msg,
            'data' => ''
        ];
    }

    /**
     * 成功返回
     * @param array $data 返回的数据
     * @return array
     */
    protected function successData($data = [])
    {
        return [
            'code' => 0,
            'msg' => '操作成功',
            'data' => $data
        ];
    }

    /**
     * @param $msg
     */
    public function log($msg,$context = [])
    {
        if($this->logName){
            Tools::addLog($this->logName, $msg,$context);
        }
    }
}
