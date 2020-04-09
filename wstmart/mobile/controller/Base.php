<?php
namespace wstmart\mobile\controller;

use think\Controller;
use util\AccessKeyHelper;
use wstmart\common\model\TUserMap;
use wstmart\common\model\Users;
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

        //自动登录
        $user_id = input("get.user_id",0);
        $access_key = input("get.access_key",'');


		
        // if(!session('WST_USER') && $user_id && $access_key){
		// 	$ret = AccessKeyHelper::validateAccessKey($user_id, $access_key);
			
        //     if(!$ret){
        //         //exit(json_encode(Tools::outJson(9002,"access-key无效，请重新登录"),JSON_UNESCAPED_UNICODE);
        //     }
		
        //     $user = new Users();
        //     $user->autoLogin($user_id);
		// }

	
		if($user_id && $access_key){
			
			if(!session('WST_USER') || session('WST_USER')['userId']!=$user_id){

				$ret = AccessKeyHelper::validateAccessKey($user_id, $access_key);
				
				if(!$ret){
					//exit(json_encode(Tools::outJson(9002,"access-key无效，请重新登录"),JSON_UNESCAPED_UNICODE);
				}

				$user = new Users();
				$user->autoLogin($user_id);
			}
		}
		
		
		//WSTSwitchs();
		$this->assign("v",WSTConf('CONF.wstVersion')."_".WSTConf('CONF.wsthomeStyleId'));
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
/*       	$USER = session('WST_USER');
        if(empty($USER)){
        	if(request()->isAjax()){
        		die('{"status":-999,"msg":"您还未登录"}');
        	}else{
        		$this->redirect('mobile/users/login');
        		exit;
        	}
        }*/
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
		//return WSTUploadPic(0);
        return UploadPicToCos();
	}
	/**
	 * 获取验证码
	 */
	public function getVerify(){
		WSTVerify();
	}
}