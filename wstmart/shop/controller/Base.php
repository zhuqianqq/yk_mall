<?php
namespace wstmart\shop\controller;
/**
 * 基础控制器
 */
use think\Controller;
use think\Db;
use util\Tools;

class Base extends Controller {
	public function __construct(){
		parent::__construct();
		$this->assign("v",STATIC_VER); //静态资源版本号

		$this->view->filter(function($content){
			$content = str_replace("__SHOP__",str_replace('/index.php','',$this->request->root()).'/wstmart/shop/view/default',$content);
            $content = str_replace("__RESOURCE_PATH__",WSTConf('CONF.resourcePath'),$content);
            return $content;
        });
	}
    protected function fetch($template = '', $vars = [], $config = []){
        return $this->view->fetch("default/".$template, $vars, $config);
    }

	public function getVerify(){
		WSTVerify();
	}
	/**
     * 上传图
     */
	public function uploadPic()
    {
        $this->checkAuth();
		//return WSTUploadPic(0);
        return UploadPicToCos();
	}
    /**
     * 上传视频
     */
    public function uploadVideo(){
        $this->checkAuth();
        return WSTUploadVideo();
    }

	/**
    * 编辑器上传文件
    */
    public function editorUpload(){
        $this->checkAuth();
        return WSTEditUpload(0);
    }

    //登录验证方法--商家
    protected function checkAuth(){
       	$USER = session('WST_USER');
        if(!empty($USER) && $USER['userType']==1){
            //如果是店主就跳转，不是店主的话，就判断请求是否有权限。
            if(!$USER['SHOP_MASTER']){
                $request = request();
                $visit = strtolower($request->module()."/".$request->controller()."/".$request->action());
                if(!in_array($visit,$USER['visitPrivilegeUrls'])){
                    if(request()->isAjax()){
                        die('{"status":-998,"msg":"您没有操作权限"}');
                    }else{
                        die('您没有操作权限');
                    }
                }
            }
        }else{
            if(request()->isAjax()){
                die('{"status":-999,"msg":"您还未登录"}');
            }else{
                $this->redirect('shop/index/login');
                exit;
            }
        }
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
}