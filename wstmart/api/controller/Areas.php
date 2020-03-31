<?php
namespace wstmart\api\controller;
use wstmart\common\model\Areas as M;
/**
 * 地区控制器
 */
class Areas extends Base{
	/**
	 * 列表查询
	 */
    public function listQuery(){
        $m = new M();
        $rs = $m->listQuery();
		return $this->outJson(0, "success", $rs);
    }
}
