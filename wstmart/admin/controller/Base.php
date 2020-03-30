<?php
namespace wstmart\admin\controller;
/**
 * 基础控制器
 */
use think\Controller;
class Base extends Controller {
	public function __construct(){
		parent::__construct();
        $this->assign("v",STATIC_VER); //静态资源版本号

		$this->view->filter(function($content){
			$content = str_replace("__ADMIN__",str_replace('/index.php','',$this->request->root()).'/wstmart/admin/view',$content);
            $content = str_replace("__RESOURCE_PATH__",WSTConf('CONF.resourcePath'),$content);
            return $content;
        });
	}
    protected function fetch($template = '', $vars = [], $config = [])
    {
        return $this->view->fetch($template, $vars, $config);
    }

	public function getVerify(){
		WSTVerify();
	}
	
	public function uploadPic(){
		//return WSTUploadPic(1);
        return UploadPicToCos();
	}

	/**
    * 编辑器上传文件
    */
    public function editorUpload(){
        return WSTEditUpload(1);
    }
}