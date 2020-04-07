<?php
/**
 * 腾讯云直播服务
 */
namespace wstmart\api\service;



use think\facade\Config;
use util\Tools;

class TenCloudLiveService extends BaseService
{
    /**
     * @var mixed sdk配置参数
     */
    protected $config;

    /**
     * @var string 日志名
     */
    protected $logName = "ten_cloud_live";

    /**
     * @var string 您用来推流的域名
     */
    protected $push_domain;

    public function __construct()
    {
        $this->config = Config::get('tencent_cloud');
        $this->push_domain = $this->config['push_domain'];
    }

    /**
     * @return Credential
     */
    public function getCredential()
    {
        return new Credential($this->config["secretId"], $this->config["secretKey"]);
    }

    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param domain 您用来推流的域名
     *        streamName 您用来区别不同推流地址的唯一流名称
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url
     */
    public function getPushUrl($stream_name, $key = null, $time = null)
    {
        if ($key && $time) {
            $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
            $txSecret = md5($key . $stream_name . $txTime);
            $ext_str = "?" . http_build_query(array(
                    "txSecret" => $txSecret,
                    "txTime" => $txTime
                ));
        }
        return $this->push_domain . "/live/" . $stream_name . (isset($ext_str) ? $ext_str : "");
    }

    /**
     * 断开直播流  https://cloud.tencent.com/document/api/267/20469
     * @param string $streamName 流名称
     * @param string $appName 推流路径，与推流和播放地址中的AppName保持一致，默认为 live。
     * @param string $domain 您的加速域名
     * https://live.tencentcloudapi.com/?Action=DropLiveStream&DomainName=5000.livepush.myqcloud.com
     * &AppName=live&StreamName=stream1&<公共请求参数>
     */
    public function dropLiveStream($streamName, $appName = 'live', $domain = '')
    {
        $cred = $this->getCredential();
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("live.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new LiveClient($cred, "", $clientProfile);

        $req = new DropLiveStreamRequest(); //请求类
        $req->StreamName = $streamName;
        $req->DomainName = !empty($domain) ? $domain : $this->config['push_domain_cdn'];  //您的加速域名
        $req->AppName = $appName;

        $resp = $client->DropLiveStream($req);
        $this->log("droplive streamName:$streamName,domain:{$domain},appname:{$appName},res:" . $resp->toJsonString());

        $res = $resp->serialize();

        if (isset($res["Error"])) { //Error 的出现代表着该请求调用失败
            return Tools::outJson(500, "调用接口失败 code:" . $res["Error"]["Code"]);
        }
        return Tools::outJson(0, "下播成功");
    }

    /**
     * 禁推直播流  https://cloud.tencent.com/document/api/267/20468
     * @param string $streamName 流名称
     * @param string $appName 推流路径，与推流和播放地址中的AppName保持一致，默认为 live。
     * @param string $domain 您的推流域名。
     * @param string $resumeTime 恢复流的时间。UTC 格式，例如：2018-11-29T19:00:00Z。 注意： 1. 默认禁播7天，且最长支持禁播90天。。
     * @param string $reason 禁推原因。
     */
    public function forbidLiveStream($streamName, $appName = 'live', $domain = '', $resumeTime = '', $reason = '')
    {
        $cred = $this->getCredential();
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("live.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new LiveClient($cred, "", $clientProfile);

        $req = new ForbidLiveStreamRequest(); //请求类
        $req->StreamName = $streamName;
        $req->DomainName = !empty($domain) ? $domain : $this->config['push_domain_cdn'];  //您的加速域名
        $req->AppName = $appName;
        //$resumeTime 恢复流的时间。UTC 格式，例如：2018-11-29T19:00:00Z。 注意： 1. 默认禁播7天，且最长支持禁播90天。
        $req->ResumeTime = $resumeTime;
        $req->Reason = $reason;

        $resp = $client->ForbidLiveStream($req);
        $this->log("forbid_live streamName:{$streamName},domain:{$domain},appname:{$appName},resumeTime:{$resumeTime},reason:{$reason},res:" . $resp->toJsonString());

        $res = $resp->serialize();

        if (isset($res["Error"])) { //Error 的出现代表着该请求调用失败
            return Tools::outJson(500, "调用接口失败 code:" . $res["Error"]["Code"]);
        }
        return Tools::outJson(0, "下播成功");
    }

    /**
     * 恢复直播流  https://cloud.tencent.com/document/api/267/20467
     * @param string $streamName 流名称
     * @param string $appName 推流路径，与推流和播放地址中的AppName保持一致，默认为 live。
     * @param string $domain 您的推流域名。
     */
    public function resumeLiveStream($streamName, $appName = 'live', $domain = '')
    {
        $cred = $this->getCredential();
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("live.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new LiveClient($cred, "", $clientProfile);

        $req = new ResumeLiveStreamRequest(); //请求类
        $req->DomainName = !empty($domain) ? $domain : $this->config['push_domain_cdn'];  //您的加速域名
        $req->AppName = $appName;
        $req->StreamName = $streamName;

        $resp = $client->ResumeLiveStream($req);
        $this->log("resume streamName:{$streamName},domain:{$domain},appname:{$appName},res:" . $resp->toJsonString());

        $res = $resp->serialize();

        if (isset($res["Error"])) { //Error 的出现代表着该请求调用失败
            return Tools::outJson(500, "调用接口失败 code:" . $res["Error"]["Code"]);
        }
        return Tools::outJson(0, "恢复成功");
    }

    /**
     * App 管理员可以通过该接口在群组中发送系统通知。
     * https://cloud.tencent.com/document/product/269/1630
     * @param $group_id 向哪个群组发送系统通知
     * @param $content 系统通知的内容
     */
    public function sendGroupSystemNotification($room_id, $content)
    {
        $api = new TLSSigAPIv2($this->config["IM_SDKAPPID"], $this->config["IM_SECRETKEY"]);
        $admin_user = "admin"; //管理员账号，在云平台后台配置
        $user_sign = $api->genSig($admin_user); //UserSig 是用户登录即时通信 IM 的密码

        $param = [
            "sdkappid" => $this->config["IM_SDKAPPID"],
            "identifier" => $admin_user,
            "usersig" => $user_sign,
            "random" => time(),
            "contenttype" => "json",
        ];

        $url = "https://console.tim.qq.com/v4/group_open_http_svc/send_group_system_notification?" . http_build_query($param);
        $post_data = [
            "GroupId" => $room_id,
            "Content" => $content,
        ];
        $res = Tools::curlPost($url, json_encode($post_data));
        $this->log("send_notification {$url},param:".json_encode($post_data).",res:" . json_encode($res,JSON_UNESCAPED_UNICODE));

        if (isset($res["ErrorCode"]) && $res["ErrorCode"] == 0) { //错误码，0表示成功，非0表示失败
            return Tools::outJson(0, "发送成功");
        }
        return Tools::outJson(500, "调用接口失败" . $res["ErrorInfo"]."[code:".$res["ErrorCode"]."]");
    }

    /**
     * App 在群组中发送普通消息。
     * https://cloud.tencent.com/document/product/269/1629
     * @param $group_id 向哪个群组发送系统通知
     * @param $content 系统通知的内容
     */
    public function sendGroupMsg($room_id, $content)
    {
        $api = new TLSSigAPIv2($this->config["IM_SDKAPPID"], $this->config["IM_SECRETKEY"]);
        $admin_user = "admin"; //管理员账号，在云平台后台配置
        $user_sign = $api->genSig($admin_user); //UserSig 是用户登录即时通信 IM 的密码

        $param = [
            "sdkappid" => $this->config["IM_SDKAPPID"],
            "identifier" => $admin_user,
            "usersig" => $user_sign,
            "random" => time(),
            "contenttype" => "json",
        ];

        $url = "https://console.tim.qq.com/v4/group_open_http_svc/send_group_msg?" . http_build_query($param);
        $post_data = [
            "GroupId" => $room_id,
            "Random" => time(),
            "MsgBody" => [
                [
                    "MsgType" => "TIMTextElem", // 文本
                    "MsgContent" => [
                        "Text" => $content
                    ]
                ]
            ],
        ];
        $res = Tools::curlPost($url, json_encode($post_data));
        $this->log("send_group_msg {$url},param:".json_encode($post_data).",res:" . json_encode($res,JSON_UNESCAPED_UNICODE));

        if (isset($res["ErrorCode"]) && $res["ErrorCode"] == 0) { //错误码，0表示成功，非0表示失败
            return Tools::outJson(0, "发送成功");
        }
        return Tools::outJson(500, "调用接口失败" . $res["ErrorInfo"]."[code:".$res["ErrorCode"]."]");
    }

    /**
     * App 管理员通过该接口解散群。
     *  https://cloud.tencent.com/document/product/269/1624
     * @param $room_id
     */
    public function destroyGroup($room_id)
    {
        $api = new TLSSigAPIv2($this->config["IM_SDKAPPID"], $this->config["IM_SECRETKEY"]);
        $admin_user = "admin"; //管理员账号，在云平台后台配置
        $user_sign = $api->genSig($admin_user); //UserSig 是用户登录即时通信 IM 的密码

        $param = [
            "sdkappid" => $this->config["IM_SDKAPPID"],
            "identifier" => $admin_user,
            "usersig" => $user_sign,
            "random" => time(),
            "contenttype" => "json",
        ];

        $url = "https://console.tim.qq.com/v4/group_open_http_svc/destroy_group?" . http_build_query($param);
        $post_data = [
            "GroupId" => $room_id,
        ];
        $res = Tools::curlPost($url, json_encode($post_data));
        $this->log("destroy_group {$url},param:".json_encode($post_data).",res:" . json_encode($res,JSON_UNESCAPED_UNICODE));

        if (isset($res["ErrorCode"]) && $res["ErrorCode"] == 0) { //错误码，0表示成功，非0表示失败
            return Tools::outJson(0, "操作成功");
        }

        return Tools::outJson(500, "调用接口失败" . $res["ErrorInfo"]."[code:".$res["ErrorCode"]."]");
    }

    /**
     * 解析domain,appname,stream_name
     * @param $push_url
     * rtmp://push.laotouge.cn/live/1400319314_101162?txSecret=702052ac8d2c3633cf53d43fb6bcbbc6&txTime=5E685262
     */
    public static function parsePushUrl($push_url)
    {
        if (empty($push_url)) {
            return '';
        }
        $arr = explode("?", $push_url);
        $arr2 = explode("/", str_replace('rtmp://', '', $arr[0]));

        return $arr2;
    }
}