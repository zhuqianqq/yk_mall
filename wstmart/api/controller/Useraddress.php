<?php
namespace wstmart\api\controller;
use wstmart\common\model\UserAddress as M;
use wstmart\common\model\TUserMap;
use think\Db;

/**
 * 用户地址控制器
 */
class UserAddress extends Base{
	// 前置方法执行列表
    // protected $beforeActionList = [
    //     'checkAuth'
    // ];
	/**
	 * 地址管理
	 */
	public function index(){

		$m = new M();
		$where = ['userId'=>(int)input('param.user_id'),'dataFlag'=>1];
		$addressList = Db::name('user_address')->order('isDefault asc, addressId desc')->where($where)->select();
		return $this->outJson(0, "success", ['addressList'=>$addressList??[]]);

		//获取省级地区信息
		//$addressList = $m->listQuery(input('param.user_id'));
		//$area = model('areas')->listQuery(0);
		//return $this->outJson(0, "success", ['addressList'=>$addressList,'area'=>$area]);
	}
	/**
	 * 获取地址信息
	 */
	public function getById(){
		$mall_user_id = TUserMap::getMallUserId(input('param.mall_user_id'));  //商城用户id
		$m = new M();
		return $this->outJson(0, "success", $m->getById(input('post.addressId/d'),$mall_user_id));
	}
	/**
	 * 设置为默认地址
	 */
	public function setDefault(){
		$m = new M();
		return $this->outJson(0, "success",$m->setDefault(input('param.user_id')));
	}
	/**
     * 新增/编辑地址
     */
    public function edits(){
		
        $m = new M();
        if((int)input('addressId')>0){
        	$rs = $m->edit(input('param.user_id'));
        }else{
        	$rs = $m->add(input('param.user_id'));
		} 
		return $this->outJson(0, "success",$rs);
    }
    /**
     * 删除地址
     */
    public function del(){

		$m = new M();
		
		return $this->outJson(0, "success", $m->del(input('param.user_id')));
    }
}
