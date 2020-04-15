<?php
/**
 * 通用控制器
 */

namespace wstmart\api\controller;

use image\Image;
use think\facade\Cache;
use util\CosHelper;
use util\JSSDK;
use util\SmsHelper;
use util\Tools;
use util\WechatHelper;

class Common extends Base
{
    protected $checkLogin = false;

    /**
     * 图片上传
     */
    public function upload()
    {
        if ($this->request->isPost()) {
            set_time_limit(0);

            $upload_type = $this->request->param("upload_type",'',"trim");
            $zip_size = 500 * 1024;
            $max_size = 10 * 1024 * 1024;
            $type_arr = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'];
            if($upload_type == "base64"){
                $base64_data = $this->request->post('file','',"trim");

                if (!preg_match('/^(data:\s*(image\/\w+);base64,)/', $base64_data, $result)) {
                    return $this->outJson(100,"图片数据不是base64格式");
                }
                $file_type = $result[2];
                if (!in_array($file_type, $type_arr)) {
                    return $this->outJson(100, '图片格式不正确，只允许jpg,jpeg,png或gif格式');
                }

                $file_data = base64_decode(str_replace($result[1],'', $base64_data)); //去掉data:image/jpeg;base64,
                if ($file_data === false) {//解码失败
                    return $this->outJson(100,"解码base64图片数据失败");
                }
                if (strlen($file_data) <= 0) {
                    return $this->outJson(100, '文件大小不能为空');
                }
                if (strlen($file_data) > $max_size) {
                    return $this->outJson(100, '图片大小不能超过10Mb');
                }

                $file_path = $this->getSavePath();
                $len = file_put_contents($file_path,$file_data);

                if (!$len) {
                    return $this->outJson(200,"写入图片数据失败");
                }

                $ret = CosHelper::upload($file_path);
                @unlink($file_path);

                return json($ret);
            } else {
                $file = isset($_FILES["file"]) ? $_FILES["file"] : null;
                if (empty($file)) {
                    return $this->outJson(100, '请选择要上传的图片');
                }
                $file_type = strtolower($file['type']);
                if (!in_array($file_type, $type_arr)) {
                    return $this->outJson(100, '图片格式不正确，只允许jpg,jpeg,png或gif格式');
                }
                if ($file['size'] <= 0) {
                    return $this->outJson(100, '文件大小不能为空');
                }
                if ($file['size'] > $max_size) {
                    return $this->outJson(100, '图片大小不能超过10Mb');
                }

                if ($file["size"] > $zip_size) {
                    //进行图片压缩
                    $file_path = $this->getSavePath($file["tmp_name"]);
                    $image = Image::open($file["tmp_name"]);
                    $image->thumb(640,972)->save($file_path);

                    $ret = CosHelper::upload($file_path);
                    //@unlink($file_path);
                } else {
                    $file_path = $this->getSavePath($file["tmp_name"]);
                    if(!move_uploaded_file($file["tmp_name"],$file_path)){
                        return $this->outJson(200, '保存文件失败');
                    }
                    $ret = CosHelper::upload($file_path);
                    //@unlink($file_path);
                }
                Tools::addLog("upload","file:".json_encode($file,JSON_UNESCAPED_UNICODE).",res:".json_encode($ret,JSON_UNESCAPED_UNICODE));

                return json($ret);
            }
        }

        return $this->outJson(-1, "非法请求");
    }

    /**
     * 发送短信验证码
     */
    public function smsCode()
    {
        if(!$this->request->isPost()){
            return $this->outJson(500,"非法请求");
        }

        $mobile = $this->request->post("mobile",'',"trim");
        $type = $this->request->post("type",'login',"trim");

        if(empty($mobile)){
            return $this->outJson(100,"手机号不能为空");
        }

        $cache_key = SmsHelper::getCacheKey($mobile,$type);
        $vcode = Cache::get($cache_key);
        $mask_mobile = Tools::maskMobile($mobile);
        //判断是否已发送过
        if ($vcode) {
            return $this->outJson(0,"验证码已发送到{$mask_mobile}，请注意查收");
        }

        $vcode = '';
        for ($i = 0; $i < 6; $i ++) {
            $vcode .= mt_rand(0, 9);
        }
        
        $result = SmsHelper::sendSmsMessage($mobile,"短信验证码：{$vcode}，有效期5分钟");
        if ($result['code'] != 0) {
            return $this->outJson($result["code"],$result['msg']);
        }
        $expire = 5 * 60; //5分钟有效
        Cache::set($cache_key,$vcode, $expire);

        return $this->outJson(0,"验证码已发送到{$mask_mobile}，请注意查收");
    }

    /**
     * 生成小程序二维码
     */
    public function genMiniQr()
    {
        $page = $this->request->param("page", '', "trim");
        $scene = $this->request->param("scene", '', "trim");
        $width =  $this->request->param("width", '430', "int");
        $accessToken = WechatHelper::getAccessToken();
        $res = WechatHelper::getMiniQr($page, $scene, $width, $accessToken);

        return response($res, 200, ['Content-Length' => strlen($res)])->contentType('image/png');
    }

    /*
     * 启动接口，返回小程序是否已经上线，
     */
    public function startInfo()
    {
        //小程序已上线为1 ，未上线为0
        $data["MiniWechatIsPublish"] = 0;
        $data["defaultPrebroadNickName"] = "映播XXX";
        $data["baseURL"] = env("APP_URL");
        return $this->outJson(0, "", $data);
    }

    /**
     * 微信配置选项
     */
    public function wxconfig()
    {

        $appid = 'wxc0e579df306a9447';
        $appsecret = 'dc136c3a1fbb60b52d91d00e65df75e4';
        $jssdk = new JSSDK($appid, $appsecret);
        $url = $this->request->param("url", '', "trim");
        $signPackage = $jssdk->getSignPackage($url);
        $config = array(
            'debug' => true,
            'appId' => $signPackage['appId'],
            'timestamp' => $signPackage['timestamp'],
            'nonceStr' => $signPackage['nonceStr'],
            'signature' => $signPackage['signature'],
            'jsApiList' => array(
                'uploadImage',
                'checkJsApi',
                'updateTimelineShareData',
                'hideOptionMenu',
                'updateAppMessageShareData',
                'hideMenuItems',
                'showMenuItems'
            )
        );
//        echo json_encode(['code' => 0,'data' => $config ,'msg' => 'ok']);
        return $this->outJson(0, "配置成功！", $config);
    }

    /**
     * 返回图片保存地址
     * @return string
     */
    private function getSavePath($file_name = '')
    {
        $save_path = $_SERVER["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "upload".DIRECTORY_SEPARATOR.date("Ymd").DIRECTORY_SEPARATOR; //保存目录
        if (!file_exists($save_path)) {
            @mkdir($save_path, 0755, true); //创建目录
        }

        if(!empty($file_name) && file_exists($file_name)){
            $info = @getimagesize($file_name);
            $img_type = image_type_to_extension($info[2], true); //图片类型

            return $save_path . Tools::getGuider("image").$img_type;
        }else{
            return $save_path . Tools::getGuider("image").".jpg";
        }
    }
}
