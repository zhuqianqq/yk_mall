<?php
/**
 * 工具类
 */
namespace util;

use think\Db;

class Tools
{
    /**
     * 记录业务日志
     * @param string $file 日志文件名称(不带后缀)
     * @param string $msg 日志内容
     * @param array|string $context 上下文信息
     */
    public static function addLog($file, $msg, $context = [])
    {
        $content = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        if(!empty($context)){
            $content .= "context:". (is_array($context) ? json_encode($context,JSON_UNESCAPED_UNICODE) : $context).PHP_EOL;
        }
        $log_name = $file . "_" . date('Ymd') . ".log";
        $log_file = app()->getRuntimePath() . "log/" . ltrim($log_name, "/"); //保存在runtime/log/目录下
        $path = dirname($log_file);
        !is_dir($path) && @mkdir($path, 0755, true); //创建目录

        @file_put_contents($log_file, $content, FILE_APPEND);
    }

    /**
     * 获取分页html
     * @param int $total 总记录数
     * @param int $page_size 每页记录数
     * @return string
     */
    public static function getPageHtml($total, $page_size)
    {
        $request = request();
        $page = intval($request->get('page', 1));
        $page_num = $page_size > 0 ? ceil($total / $page_size) : 0;

        $page_html = Pager::instance(['total' => $total, 'limit' => $page_size])
            ->render($page, $page_num, $request->get());

        return $page_html;
    }

    /*
     * 统一成功输出json
     */
    public static function success($data = [], $code = 200, $msg = 'success')
    {
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /*
     * 统一错误输出json
     */
    public static function error($code = -1, $msg = 'error', $data = [])
    {
        return json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    public static function outJson($code = 0, $msg = '', $data = [])
    {
        if($data === []){
            $data = new \stdClass();
        }
        return [
            "code" => $code,
            "msg" => $msg,
            "data" => $data
        ];
    }

    /**
     * 把数据集转换成Tree
     * @param array $list
     * @param string $pk 主键
     * @param string $pid 父级id
     * @param string $child
     * @param int $root 根节点id
     * @return array
     */
    public static function buildTree(&$list, $pk = 'id', $pid = 'pid', $child = 'child', $root = 0)
    {
        $tree = [];
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = [];
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     *
     * @param array $tree
     * @param array $result
     * @param int $deep
     * @param string $separator
     * @return array
     */
    public static function getTreeDropDownList($tree = [], &$result = [], $deep = 0, $separator = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;')
    {
        $deep++;
        foreach ($tree as $list) {
            $result[$list['id']] = str_repeat($separator, $deep - 1) . $list['name'];
            if (isset($list['child'])) {
                self::getTreeDropDownList($list['child'], $result, $deep);
            }
        }
        return $result;
    }

    /**
     * 获取微秒时间
     * @return float
     */
    public static function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * 获取随机字符串，不包括数字0和字母O因为他们太像了
     * @param int $length
     * @return string
     */
    public static function randStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取随机数字串
     * @param int $length
     * @param bool $includeZero 是否包含0
     * @return string
     */
    public static function randNumber($length = 16, $includeZero = true)
    {
        if ($includeZero) {
            $chars = "012356789";
        } else {
            $chars = "12356789";
        }
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * @param $filename
     * @return mixed
     */
    public static function getExtension($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * 手机号脱敏
     * @param string $str
     * @return string
     */
    public static function maskMobile($str)
    {
        if (empty($str) || strlen($str) < 11) {
            return $str;
        }
        $str1 = substr($str, 0, 3);
        $str2 = substr($str, -4, 4);
        return $str1 . '****' . $str2;
    }

    /**
     * 发送post请求
     * @param $url
     * @param string $data
     * @param bool $is_json
     * @param array $header
     * @return mixed
     */
    public static function curlPost($url, $data = '', $is_json = true, $header = array(),$ret_json=true)
    {
        if ($is_json) {
            array_push($header, "Content-Type:application/json");
            if (is_array($data)) {
                $data = json_encode($data);
            }
        } else {
            array_push($header, "Content-Type: application/x-www-form-urlencoded");
            if (is_array($data)) {
                $data = http_build_query($data);
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置头信息的地方
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $output = curl_exec($ch);
        curl_close($ch);

        if ($ret_json) {
            self::addLog("api_log", "{$url}\nparam:{$data}\nres:{$output}");
            $out = json_decode($output, true);
            return $out;
        }else{
            return $output;
        }
    }

    public static function my_curl($url, $method = null, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        // var_dump($return);
        return $result;
    }

    /**
     * get 请求
     * @param $url
     * @param array $postData
     * @return mixed
     */
    public static function curlGet($url, $data = [])
    {
        $ch = curl_init();
        if(!empty($data)){
            $data = http_build_query($data);
            if(strpos($url,"?") !== false){
                $url .= "&".$data;
            }else{
                $url .= "?".$data;
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);
        self::addLog("api_log", "{$url}\nparam:{$data}\nres:{$output}");
        $out = json_decode($output, true);

        return $out;
    }

    /**
     * 生成唯一值
     * @param string $type 类型前缀
     * @return string
     */
    public static function getGuider($type = "")
    {
        return $type.date("YmdHis").mt_rand(1000,9999);
    }

    /**
     * 获取UTC格式的时间,格式为 YYYY-MM-DDThh:mm:ssZ
     * 北京时间值为 UTC 时间值 + 8 小时，格式按照 ISO 8601 标准表示。
     * @param int $time
     * @return string
     */
    public static function getUtcTime($time = 0)
    {
        $old_tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        if(empty($time)){
            $time = time();
        }
        $timeStr = date("Y-m-d\TH:i:s\Z",$time);

        date_default_timezone_set($old_tz);

        return $timeStr;
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0:返回IP地址 1:返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    public static function getClientIp($type = 0, $adv = true)
    {
        $type = $type ? 1 : 0;
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }else{
                $ip = '';
            }
        } else{
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        if(empty($ip)){
            return '';
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);

        return $ip[$type];
    }
}