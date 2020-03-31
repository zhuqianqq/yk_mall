<?php
namespace wstmart\api\controller;
use wstmart\common\model\UserAddress as M;
use wstmart\common\model\TUserMap;

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

		$mall_user_id = TUserMap::getMallUserId(input('param.mall_user_id'));  //商城用户id
		$m = new M();
		$userId = session('WST_USER.userId');
		$addressList = $m->listQuery($mall_user_id);
		//获取省级地区信息
		$area = model('areas')->listQuery(0);

		return $this->outJson(0, "success", ['addressList'=>$addressList,'area'=>$area]);
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
		$mall_user_id = TUserMap::getMallUserId(input('param.mall_user_id'));  //商城用户id
		$m = new M();
		return $this->outJson(0, "success",$m->setDefault($mall_user_id));
	}
	/**
     * 新增/编辑地址
     */
    public function edits(){
		$mall_user_id = TUserMap::getMallUserId(input('param.mall_user_id'));  //商城用户id

        $m = new M();
        if((int)input('addressId')>0){
        	$rs = $m->edit($mall_user_id);
        }else{
        	$rs = $m->add($mall_user_id);
		} 
		return $this->outJson(0, "success",$rs);
    }
    /**
     * 删除地址
     */
    public function del(){

		$mall_user_id = TUserMap::getMallUserId(input('param.mall_user_id'));  //商城用户id

		$m = new M();
		
		return $this->outJson(0, "success", $m->del($mall_user_id));
    }
}
