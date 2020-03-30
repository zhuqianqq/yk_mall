<?php
/**
 * 腾讯cos上传
 */
namespace util;

use util\Tools;
use think\facade\Config;
use Qcloud\Cos\Exception\ServiceResponseException;

class CosHelper
{
    protected static $logName = "cos_upload";

    /**
     * 上传文件
     * @param string $file_path 本地文件物理地址
     * @param string $file_ext 文件扩展名
     * @return array
     */
    public static function upload($file_path,$file_ext = '')
    {
        $cos_conf = Config::get("cos.");
        if(empty($cos_conf)){
            return Tools::outJson(-1, "缺少cos配置文件");
        }

        $cosClient = new \Qcloud\Cos\Client(
            array(
                'region' => $cos_conf["COSKEY_BUCKET_REGION"],
                'schema' => 'https', //协议头部，默认为http
                'credentials' => [
                    'secretId' => $cos_conf['COS_SECRETID'],
                    'secretKey' => $cos_conf['COSKEY_SECRECTKEY']
                ]));

        $bucket = $cos_conf["COSKEY_BUCKET"]; //存储桶
        $key = self::generateKey($file_path, $file_ext); //对象在存储桶中的位置，即称对象键
        try {
            $handler = fopen($file_path, 'rb');
            //$result = $cosClient->upload($bucket,$key,$body);
            $result = $cosClient->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $handler,
            ]);
            @fclose($handler);

            Tools::addLog(self::$logName, "{$file_path},key:{$key},res:" . json_encode($result, JSON_UNESCAPED_UNICODE));

            if ($result && !empty($result["Location"])) {
                if(stripos($result["Location"],"http") === false){
                    $result["Location"] = "https://".$result["Location"];
                }
                return Tools::outJson(0, "上传成功", [
                    "url" => $result["Location"],
                    "key" => $key,
                ]);
            }
            return Tools::outJson(-1, "上传失败");
        } catch (ServiceResponseException $ex) {
            $statusCode = $ex->getStatusCode(); // 获取错误码
            $message = $ex->getMessage(); // 获取错误信息
            $errorCode = $ex->getCosErrorCode(); // 获取错误名称
            $response = $ex->getResponse(); // 获取完整的响应

            Tools::addLog(self::$logName, "upload_fail:{$file_path},statusCode:{$statusCode},message:{$message},errorCode:{$errorCode},response:".json_encode($response));
            return Tools::outJson(500, "上传失败:" . $ex->getMessage());
        }catch (\Exception $ex) {
            Tools::addLog(self::$logName, "upload_fail:{$file_path},line:" . $ex->getLine() . ",message:" . $ex->getMessage());
            return Tools::outJson(500, "上传失败:" . $ex->getMessage());
        }
    }

    /**
     * 用文件名和扩展名生成一个key
     * @param string $fileName
     * @param string $file_ext
     * @return string
     */
    private static function generateKey($fileName = '', $file_ext = '')
    {
        $fileExt = $file_ext ? $file_ext : Tools::getExtension($fileName);

        return Tools::getGuider("ygzb") .".".$fileExt;
    }
}