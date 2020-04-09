<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\Goods as M;
use think\Db;
use think\facade\Cache;

/**
 * 商品控制器
 */
class Goods extends Base{
   /**
	* 查看上架商品列表
	*/
	public function index(){
	    $this->assign("p",(int)input("p"));
    	$this->assign("areaList",model('areas')->listQuery(0));
		return $this->fetch('list_sale');
	}
   /**
    * 批量删除商品
    */
    public function batchDel(){
        $m = new M();
        return $m->batchDel();
    }

    /**
    * 设置违规商品
    */
    public function illegal(){
        $m = new M();
        return $m->illegal();
    }
    /**
    * 批量设置违规商品
    */
    public function batchIllegal(){
        $m = new M();
        return $m->batchIllegal();
    }
    /**
     * 通过商品审核
     */
    public function allow(){
        $m = new M();
        return $m->allow();
    } 
    /**
     * 批量通过商品审核
     */
    public function batchAllow(){
        $m = new M();
        return $m->batchAllow();
    }
	/**
	 * 获取上架商品列表
	 */
	public function saleByPage(){
		$m = new M();
		$rs = $m->saleByPage();
		$rs['status'] = 1;
		return WSTGrid($rs);
	}
	
    /**
	 * 审核中的商品
	 */
    public function auditIndex(){
        $this->assign("p",(int)input("p"));
    	$this->assign("areaList",model('areas')->listQuery(0));
		return $this->fetch('goods/list_audit');
	}
	/**
	 * 获取审核中的商品
	 */
    public function auditByPage(){
		$m = new M();
		$rs = $m->auditByPage();
		$rs['status'] = 1;
		return WSTGrid($rs);
	}
   /**
	 * 审核中的商品
	 */
    public function illegalIndex(){
        $this->assign("p",(int)input("p"));
    	$this->assign("areaList",model('areas')->listQuery(0));
		return $this->fetch('list_illegal');
	}
    /**
	 * 获取违规商品列表
	 */
	public function illegalByPage(){
		$m = new M();
		$rs = $m->illegalByPage();
		$rs['status'] = 1;
		return WSTGrid($rs);
	}
    
    /**
     * 删除商品
     */
    public function del(){
    	$m = new M();
    	return $m->del();
    }


    /**
     * 推荐到首页热销
     */
    public function toHot(){
        $goodsId = (int)input("param.goodsId");
    	$type = (int)input("param.type"); //type : 0 取消推荐 1 推荐首页
        if ($type==1) {
            $recommend_nums = Db::name('goods')->where(['isHot' => 1,'mIsIndex'=>1])->count();
            if($recommend_nums>=3) return WSTReturn('首页已超过三个热销商品推荐位', -1);
            Db::name('goods')->where(['goodsId' => $goodsId])->update(['isHot' => 1,'mIsIndex'=>1]);
        }else{
            Db::name('goods')->where(['goodsId' => $goodsId])->update(['isHot' => 0,'mIsIndex'=>0]);
        }
        Cache::rm('API_INDEX_HOTGOODS');
    	return WSTReturn("操作成功", 1);
    }
}
