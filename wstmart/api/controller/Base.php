<?php
namespace wstmart\api\controller;
use think\Controller;
use think\Db;
use util\Tools;


/**
 * 基础控制器
 */
class Base extends Controller {
	public function __construct(){
		parent::__construct();
		hook('initConfigHook',['getParams'=>input()]);
		WSTConf('CONF',WSTConfig());
		//WSTSwitchs();
        $this->assign("v",STATIC_VER); //静态资源版本号
		$this->view->filter(function($content){
            $style = WSTConf('CONF.wstmobileStyle')?WSTConf('CONF.wstmobileStyle'):'default';
            $content = str_replace("__RESOURCE_PATH__",WSTConf('CONF.resourcePath'),$content);
            $content = str_replace("__MOBILE__",str_replace('/index.php','',$this->request->root()).'/wstmart/mobile/view/'.$style,$content);
            return $content;
        });
		if(WSTConf('CONF.seoMallSwitch')==0){
			$this->redirect('mobile/switchs/index');
			exit;
		}
	}
    // 权限验证方法
    protected function checkAuth(){
       	$USER = session('WST_USER');
        if(empty($USER)){
        	if(request()->isAjax()){
        		die('{"status":-999,"msg":"您还未登录"}');
        	}else{
        		$this->redirect('mobile/users/login');
        		exit;
        	}
        }
    }

    // 店铺权限验证方法
    protected function checkShopAuth($opt){
       	$shopMenus = WSTShopOrderMenus();
       	if($opt=="list"){
       		if(count($shopMenus)==0){
       			session('moshoporder','对不起,您无权进行该操作');
       			$this->redirect('mobile/error/message',['code'=>'moshoporder']);
		    	exit;
       		}
       	}else{
       		if(!array_key_exists($opt,$shopMenus)){
	       		if(request()->isAjax()){
		    		die('{"status":-1,"msg":"您无权进行该操作"}');
		    	}else{
		    		session('moshoporder','对不起,您无权进行该操作');
		    		$this->redirect('mobile/error/message',['code'=>'moshoporder']);
		    		exit;
		    	}
	       	}
       	}
    }
	protected function fetch($template = '', $vars = [], $config = []){
		$style = WSTConf('CONF.wstmobileStyle')?WSTConf('CONF.wstmobileStyle'):'default';
		return $this->view->fetch($style."/".$template, $vars, $config);
		
	}
	/**
	 * 上传图片
	 */
	public function uploadPic()
    {
        return UploadPicToCos();
	}

	/**
	 * 获取验证码
	 */
	public function getVerify(){
		WSTVerify();
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