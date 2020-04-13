<?php
namespace wstmart\api\controller;
use think\App;
use think\Controller;
use think\Db;
use util\Tools;


/**
 * 基础控制器
 */
class Base extends Controller {
    /**
     * @var int 每页记录条数
     */
    public static $pageSize = 20;

    /**
     * @var bool 是否检测登录
     */
    protected $checkLogin = false;

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 是否开启跨域 默认开启
     * @var bool
     */
    protected $cors = true;

    /**
     * @var 应用渠道
     */
    protected $channel;

    /**
     * @var 当前用户id
     */
    protected $user_id = 0;

    protected $logfile = '';

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        if ($this->cors) {
            //header("Access-Control-Allow-Origin:*");
//            header("Access-Control-Allow-Credentials: true");
//            header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
//            header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With,user-id,access-key");
//            header("Content-Type: application/json; charset=utf-8");
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            header("HTTP/1.1 204 No Content"); //跨域options请求
            exit;
        }

        $user_id = $this->request->header('user_id') ?? $this->request->param('user_id');
        $this->user_id = intval($user_id);
        $this->channel = $this->request->header('channel', '');
    }

	 /**
     * 输出json数组
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function outJson($code = 0, $msg = '', $data = [])
    {
        return json(Tools::outJson($code, $msg, $data));
    }

    protected function outFail($code = 500, $msg = '操作失败')
    {
        return json(Tools::outJson($code, $msg, []));
    }

    protected function outSuccess($data = [])
    {
        return json(Tools::outJson(0, '操作成功', $data));
    }
}