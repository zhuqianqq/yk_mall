<?php
namespace wstmart\home\controller;
/**
 * 基础控制器
 */
use think\Controller;
use think\Db;

class Base extends Controller {
	public function __construct(){
		parent::__construct();
        WSTSwitchs();
        $this->assign("v",STATIC_VER); //静态资源版本号
		$this->view->filter(function($content){
            $style = WSTConf('CONF.wsthomeStyle')?WSTConf('CONF.wsthomeStyle'):'default';
            $content = str_replace("__RESOURCE_PATH__",WSTConf('CONF.resourcePath'),$content);
            $content = str_replace("__STYLE__",str_replace('/index.php','',$this->request->root()).'/wstmart/home/view/'.$style,$content);
            return $content;
        });
		hook('homeControllerBase');
		
		if(WSTConf('CONF.seoMallSwitch')==0){
			$this->redirect('home/switchs/index');
			exit;
		}
	}

	protected function fetch($template = '', $vars = [], $config = [])
    {
    	$style = WSTConf('CONF.wsthomeStyle')?WSTConf('CONF.wsthomeStyle'):'default';   
        return $this->view->fetch($style."/".$template, $vars, $config);
    }

	/**
	 * 上传图片
	 */
	public function uploadPic()
    {
        $this->checkAuth();
		//return WSTUploadPic(0);
        return UploadPicToCos();
	}
	/**
    * 编辑器上传文件
    */
    public function editorUpload(){
        $this->checkAuth();
        return WSTEditUpload(0);
    }
	
	/**
	 * 获取验证码
	 */
	public function getVerify(){
		WSTVerify();
	}

	// 登录验证方法--用户
    protected function checkAuth(){
       	$USER = session('WST_USER');
        if(empty($USER)){
        	if(request()->isAjax()){
        		die('{"status":-999,"msg":"您还未登录"}');
        	}else{
        		$this->redirect('home/users/login');
        		exit;
        	}
        }
    }
}