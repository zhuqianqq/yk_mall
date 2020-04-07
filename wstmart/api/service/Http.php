<?php
namespace wstmart\api\service;

use think\facade\Log;
use util\Tools;

Class Http
{
    const FORMAT_JSON = "json";//json格式化
    const FORMAT_XML = "xml";//xml格式化
    const FORMAT_TEXT = "text";//text格式化

    private $headers;
    private $options;
    private $format;
    private $timeout;
    private $retries;
    private $cacert;  //https 证书路径
    protected $urls;

    /**
     * 初始化baseService，
     * @param string $modelName 模型名称，
     * @param string $config url配置文件路径
     */
    public function __construct()
    {
        $this->timeout = 10;
        $this->retries = 3;
        $this->format = self::FORMAT_JSON;
    }

    /**
     * 封装了GET请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @return mixed
     */
    public function get($url, $data = "")
    {
        if (is_array($data)) {
            $urlParam = http_build_query($data);
            if (strpos($url, "?") === false) {
                $url .= "?" . $urlParam;
            } else {
                $url .= "&" . $urlParam;
            }
        }

        return $this->http_request($url);
    }

    /**
     * 封装了post请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @return mixed
     */
    public function post($url, $data = "")
    {
        return $this->http_request($url, $data, "post");
    }

    /**
     * @param $url 请求地址
     * @param string $data 可传递数组，也可直接传数据如1=2&2=3
     * @param string $type 请求类型
     * @return mixed 数组或xml或字符串
     * @throws \Exception 超时后异常
     */
    public function http_request($url, $data = "", $type = "")
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($type == "post") {  //post请求
            curl_setopt($ch, CURLOPT_POST, 1);
            $post_str = is_array($data) ? http_build_query($data) : $data;
            if ($post_str) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
            }
        }

        if (is_array($this->options)) {  //set请求地址配置参数
            foreach ($this->options as $k => $v) {
                curl_setopt($ch, $k, $v);
            }
        }

        if (is_array($this->headers)) {    //set请求header信息
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        $SSL = substr($url, 0, 8) == "https://" ? true : false;

        if ($this->cacert && $SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   // 只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $this->cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $request_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        if (($http_code != 404 && $http_code >= 400) || $curl_errno > 0) {
            curl_close($ch);

            if (($http_code == 0 || $http_code == 502) && $this->retries > 0) {
                $this->retries = $this->retries - 1;
                return $this->http_request($url, $data, $type);
            }

            $msg = "请求 {$url}  " . json_encode($data, JSON_UNESCAPED_UNICODE) . "返回异常，HTTP状态码：{$http_code},错误码:{$curl_errno},错误信息:{$curl_error}, " . substr($request_data, 0, 1000);
            Log::error($msg);
        } else {
            curl_close($ch);
        }

        $this->setOptions(null);
        $this->setHeaders(null);

        Tools::addLog("http_log", $url . "|" . stripslashes(json_encode($data, JSON_UNESCAPED_UNICODE)) . "|" . substr($request_data, 0, 1000));

        if ($request_data) {
            if ($this->format == self::FORMAT_JSON) { //解析json
                return json_decode($request_data, true);
            }

            if ($this->format == self::FORMAT_XML) {//解析xml
                return simplexml_load_string($request_data);
            }
        }

        return $request_data;
    }


    /**
     * set请求头
     * @param $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * set curl请求参数
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * set数据格式化类型
     * @param $format
     * @internal param $formatType
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * 设置超时时间  默认5s
     * @param $timeout
     */
    public function setTimtout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * set https证书路径
     * @param $cacert
     */
    public function setCacert($cacert)
    {
        $this->cacert = getcwd() . $cacert; //CA根证书
    }
}
