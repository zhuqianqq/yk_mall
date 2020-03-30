<?php
namespace wstmart\admin\model;
use think\Db;
/**
 * 登录日志业务处理
 */
class LogStaffLogins extends Base{
    /**
	 * 分页
	 */
	public function pageQuery(){
		$startDate = input('startDate');
		$endDate = input('endDate');
        $staffName = input('staffName');
        $loginIp = input('loginIp');
		$where = [];
		if($startDate!='')$where[] = ['l.loginTime','>=',$startDate." 00:00:00"];
		if($endDate!='')$where[] = [' l.loginTime','<=',$endDate." 23:59:59"];
        if($staffName!='')$where[] = [' s.staffName','like',"%".$staffName."%"];
        if($loginIp!='')$where[] = [' l.loginIp','like',"%".$loginIp."%"];
		return $mrs = Db::name('log_staff_logins')->alias('l')->join('__STAFFS__ s',' l.staffId=s.staffId','left')
			->where($where)
			->field('l.*,s.staffName')
			->order('l.loginId', 'desc')->paginate(input('limit/d'));
			
	}
}
