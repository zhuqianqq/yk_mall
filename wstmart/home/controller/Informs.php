<?php
namespace wstmart\home\controller;
use wstmart\common\model\Informs as M;
/**
 * 订单投诉控制器
 */
class Informs extends Base{
    protected $beforeActionList = ['checkAuth'];
    /******************************** 用户 ******************************************/
    /**
    * 查看举报列表
    */
	public function index(){
        $this->assign('p',(int)input('p'));
		return $this->fetch('users/informs/list_inform');
	}
    /**
    * 获取用户举报列表
    */    
    public function queryUserInformPage(){
        $m = model('Informs');
        return $m->queryUserInformByPage();
        
    }
    /**
     * 商品举报页面
     */
    public function inform(){
    	$m = new M();
        $data = $m->inform();
        if($data['status'] == 1){
        $this->assign("data",$data);
        return $this->fetch("users/informs/informs");
        }else{
        $this->assign("message",$data['msg']);
        return $this->fetch("error_msg");
        }
    }
    /**
     * 保存举报信息
     */
    public function saveInform(){
        return model('Informs')->saveInform();
    }
    /**
     * 用户查举报详情
     */
    public function getUserInformDetail(){
        $data = model('Informs')->getUserInformDetail(0);
        $this->assign("data",$data);
        $this->assign("p",(int)input('p'));
        return $this->fetch("users/informs/inform_detail");
    }

}
