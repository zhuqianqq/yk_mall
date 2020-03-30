<?php
namespace wstmart\admin\model;
use think\Db;
/**
 * 用户登录日志业务处理
 */
class LogUserLogins extends Base{
    /**
	 * 分页
	 */
	public function pageQuery(){
		$startDate = input('startDate');
		$endDate = input('endDate');
        $loginName = input('loginName');
        $loginIp = input('loginIp');
        $loginSrc = input('loginSrc');
		$where = [];
		if($startDate!='')$where[] = ['l.loginTime','>=',$startDate." 00:00:00"];
		if($endDate!='')$where[] = [' l.loginTime','<=',$endDate." 23:59:59"];
        if($loginName!='')$where[] = [' u.loginName','like',"%".$loginName."%"];
        if($loginIp!='')$where[] = [' l.loginIp','like',"%".$loginIp."%"];
        if($loginSrc>-1)$where[] = [' l.loginSrc','=',$loginSrc];
        $mrs = Db::name('log_user_logins')->alias('l')->join('__USERS__ u',' l.userId=u.userId','left')
			->where($where)
			->field('l.*,u.loginName')
			->order('l.loginId', 'desc')->paginate(input('limit/d'))->toArray();
        if(count($mrs['data'])>0){
            foreach ($mrs['data'] as $key => $v){
                $mrs['data'][$key]['loginSrc'] = WSTLangLoginSrc($v['loginSrc']);
            }
        }
		return $mrs;
	}
}
