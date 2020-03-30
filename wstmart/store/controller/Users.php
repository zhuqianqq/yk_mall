<?php
namespace wstmart\store\controller;
use wstmart\common\model\Users as MUsers;
use wstmart\common\model\LogSms;
/**
 * 用户控制器
 */
class Users extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 跳去修改个人资料
     */
    public function edit(){
        $m = new MUsers();
        //获取用户信息
        $userId = (int)session('WST_STORE.userId');
        $data = $m->getById($userId);
        $this->assign('data',$data);
        return $this->fetch('users/user_edit');
    }
    /**
     * 修改
     */
    public function toEdit(){
        $m = new MUsers();
        $rs = $m->edit();
        return $rs;
    }
    /**
     * 判断手机或邮箱是否存在
     */
    public function checkLoginKey(){
        $m = new MUsers();
        if(input("post.loginName"))$val=input("post.loginName");
        if(input("post.userPhone"))$val=input("post.userPhone");
        if(input("post.userEmail"))$val=input("post.userEmail");
        $userId = (int)session('WST_STORE.userId');
        $rs = WSTCheckLoginKey($val,$userId);
        if($rs["status"]==1){
            return array("ok"=>"");
        }else{
            return array("error"=>$rs["msg"]);
        }
    }
    
    /**
     * 修改密码
     */
    public function passedit(){
        $userId = (int)session('WST_STORE.userId');
        $m = new MUsers();
        $rs = $m->editPass($userId);
        return $rs;
    }
}

