<?php 
namespace wstmart\store\validate;
use think\Validate;
use think\Db;
/**
 * 职员验证器
 */
class Stores extends Validate{
	protected $rule = [
	    'loginName'  => 'require|min:3|max:20|checkLoginName:1',
	    'loginPwd'  => 'require|min:6',
        'storeName' => 'require|max:50',
		'areaId' => 'require',
		'storeAddress' => 'require|max:100',
		'longitude' => 'checkLocation',
        'latitude' => 'checkLocation',
        'mapLevel' => 'checkLocation',
        'storeImg' => 'require',
        'staffStatus' => 'require|in:0,1',
    ];
    
    protected $message = [
        'loginName.require' => '请输入登录账号',
		'loginName.max' => '门店账号不能小于3个字符',
        'loginName.max' => '门店账号不能超过20个字符',
        'loginPwd.require' => '请输入登录密码',
        'loginPwd.min' => '登录密码不能少于6个字符',
        'storeName.require' => '请输入门店名称',
        'storeName.max' => '门店名称不能超过50个字符',
		'areaId.require' => '请选择门店所在地',
		'longitude.checkLocation' => '请选择经纬度',
        'latitude.checkLocation' => '请选择经纬度',
        'mapLevel.checkLocation' => '请选择经纬度',
        'staffStatus.require' => '请选择门店状态',
        'storeImg.require' => '请上传门店实景图',
        'staffStatus.in' => '无效的门店状态',
    ];
    
    protected $scene = [
        'add'   =>  ['loginName','loginPwd','storeName','areaId','storeAddress','longitude','latitude','mapLevel','storeImg','storeAddress'],
        'edit'  =>  ['storeName','areaId','storeAddress','longitude','latitude','mapLevel','storeImg','storeAddress']
    ]; 
    
    protected function checkLoginName($value){
    	$where = [];
    	$where['dataFlag'] = 1;
    	$where['loginName'] = $value;
    	$rs = Db::name('staffs')->where($where)->count();
    	return ($rs==0)?true:'该登录账号已存在';
    }
	
	protected function checkLocation($value){
        $longitude = input('post.longitude',0);
        $latitude = input('post.latitude',0);
        $mapLevel = input('post.mapLevel',0);
        if(WSTConf('CONF.mapKey') == ''){
            return true;
        }else{
            return ($longitude==0 ||  $latitude==0 || $mapLevel==0)?'请选择经纬度':true;
        }

    }
}