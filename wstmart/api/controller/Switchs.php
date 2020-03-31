<?php
namespace wstmart\api\controller;
/**
 * 关闭提示处理控制器
 */
use think\Controller;
class Switchs extends Controller{
	public function __construct(){
		parent::__construct();
		WSTConf('CONF',WSTConfig());
		$style = WSTConf('CONF.wstmobileStyle')?WSTConf('CONF.wstmobileStyle'):'default';
		$this->view->filter(function($content){
            $style = WSTConf('CONF.wstmobileStyle')?WSTConf('CONF.wstmobileStyle'):'default';
            $content = str_replace("__RESOURCE_PATH__",WSTConf('CONF.resourcePath'),$content);
            $content = str_replace("__MOBILE__",str_replace('/index.php','',$this->request->root()).'/wstmart/mobile/view/'.$style,$content);
            return $content;
        });
		$this->assign("v",WSTConf('CONF.wstVersion')."_".WSTConf('CONF.wstPcStyleId'));
	}
	protected function fetch($template = '', $vars = [], $replace = [], $config = []){
		$style = WSTConf('CONF.wstmobileStyle')?WSTConf('CONF.wstmobileStyle'):'default';
		return $this->view->fetch($style."/".$template, $vars, $replace, $config);
	}
    public function index(){
        return $this->fetch('error_switch');
    }
}
