<?php
namespace wstmart\admin\model;
use wstmart\admin\validate\GoodsCats as validate;
/**
 * 商品分类业务处理
 */
use think\Db;
class GoodsCats extends Base{
	protected $pk = 'catId';
	/**
	 * 获取树形分类
	 */
	public function pageQuery(){
		return $this->where(['dataFlag'=>1,'parentId'=>input('catId/d',0)])->order('catSort asc,catId desc')->paginate(1000)->toArray();
	}
	/**
	 * 获取列表
	 */
	public function listQuery($parentId){
		return $this->where(['dataFlag'=>1,'parentId'=>$parentId])->order('catSort asc,catName asc')->select();
	}
	
	/**
	 *获取商品分类名值对
	 */
	public function listKeyAll(){
		$rs = $this->field("catId,catName")->where(['dataFlag'=>1])->order('catSort asc,catName asc')->select();
		$data = array();
		foreach ($rs as $key => $cat) {
			$data[$cat["catId"]] = $cat["catName"];
		}
		return $data;
	}
	
	/**
	 *	获取树形分类
	 */
	public function getTree($data, $parentId=0){
		$arr = array();
		foreach($data as $k=>$v)
		{
			if($v['parentId']==$parentId && $v['dataFlag']==1)
			{
				//再查找该分类下是否还有子分类
				$v['child'] = $this->getTree($data, $v['catId']);
				//统计child
				$v['childNum'] = count($v['child']);
				//将找到的分类放回该数组中
				$arr[]=$v;
			}
		}
		return $arr;
	}
	
	/**
	 * 迭代获取下级
	 * 获取一个分类下的所有子级分类id
	 */
	public function getChild($pid){
		$data = $this->where("dataFlag=1")->select();
		//获取该分类id下的所有子级分类id
		$ids = $this->_getChild($data, $pid, true);//每次调用都清空一次数组
		//把自己也放进来
		array_unshift($ids, $pid);
		return $ids;
	}
	
	public function _getChild($data, $pid, $isClear=false){
		static $ids = array();
		if($isClear)//是否清空数组
			$ids = array();
		foreach($data as $k=>$v)
		{
			if($v['parentId']==$pid && $v['dataFlag']==1)
			{
				$ids[] = $v['catId'];//将找到的下级分类id放入静态数组
				//再找下当前id是否还存在下级id
				$this->_getChild($data, $v['catId']);
			}
		}
		return $ids;
	}
	
	/**
	 * 获取指定对象
	 */
	public function getGoodscats($id){
		return $this->where(['catId'=>$id])->find();
	}
	 
	 /**
	  * 显示是否推荐/不推荐
	  */
	 public function editiIsFloor(){
	    $ids = array();
		$id = input('post.id/d');
		$ids = $this->getChild($id);
	 	$isFloor = input('post.isFloor/d')?1:0;
	 	$result = $this->where("catId in(".implode(',',$ids).")")->update(['isFloor' => $isFloor]);
	 	if(false !== $result){
	 		WSTClearAllCache();
	 		return WSTReturn("操作成功", 1);
	 	}else{
	 		return WSTReturn($this->getError(),-1);
	 	}
	 }

	/**
	 * 修改分类名称
	 */
	public function editName(){
		$catName = input('catName');
		if($catName=='')return WSTReturn("操作失败，商品分类名称不能为空");
		if(mb_strlen($catName)>20)return WSTReturn('商品分类名称不能超过20个字'.mb_strlen($catName));
		$id = (int)input('id');
		$result = $this->where("catId = ".$id)->update(['catName' => $catName]);
		if(false !== $result){
			WSTClearAllCache();
			return WSTReturn("操作成功", 1);
		}else{
			return WSTReturn($this->getError(),-1);
		}

	}
	/**
	 * 修改分类简称
	 */
	public function editsimpleName(){
		$catName = input('simpleName');
		if($catName=='')return WSTReturn("操作失败，商品分类名缩写不能为空");
		if(mb_strlen($catName)>4)return WSTReturn('商品分类名缩写不能超过4个字');
		$id = (int)input('id');
		$result = $this->where("catId = ".$id)->update(['simpleName' => $catName]);
		if(false !== $result){
			WSTClearAllCache();
			return WSTReturn("操作成功", 1);
		}else{
			return WSTReturn($this->getError(),-1);
		}
	
	}
	/**
	 * 修改分类排序
	 */
	public function editOrder(){
		$id = (int)input('id');
		$result = $this->where("catId = ".$id)->update(['catSort' => (int)input('catSort')]);
		if(false !== $result){
			WSTClearAllCache();
			return WSTReturn("操作成功", 1);
		}else{
			return WSTReturn($this->getError(),-1);
		}

	}
	
	/**
	 * 显示是否显示/隐藏
	 */
	public function editiIsShow(){
		$ids = array();
		$id = input('post.id/d');
		$ids = $this->getChild($id);
		$isShow = input('post.isShow/d')?1:0;
		Db::startTrans();
        try{
			$result = $this->where("catId in(".implode(',',$ids).")")->update(['isShow' => $isShow]);
			if(false !== $result){
				if($isShow==0){
					//删除购物车里的相关商品
					$goods = Db::name('goods')->where([["goodsCatId",'in',$ids],['isSale','=',1]])->field('goodsId')->select();
					if(count($goods)>0){
						$goodsIds = [];
						foreach ($goods as $key =>$v){
							$goodsIds[] = $v['goodsId'];
						}
						Db::name('carts')->where([['goodsId','in',$goodsIds]])->delete();
					}
					//把相关的商品下架了
					Db::name('goods')->where("goodsCatId in(".implode(',',$ids).")")->update(['isSale' => 0]);
					WSTClearAllCache();
				}
		    }
		    Db::commit();
	        return WSTReturn("操作成功", 1);
        }catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('操作失败',-1);
        }
			
	}
	
	/**
	 * 新增
	 */
	public function add(){
		$parentId = input('post.parentId/d');
		$data = input('post.');
		WSTUnset($data,'catId,dataFlag');
		$data['parentId'] = $parentId;
		$data['createTime'] = date('Y-m-d H:i:s');
	    $validate = new validate();
		if(!$validate->scene('add')->check($data))return WSTReturn($validate->getError());
		if(WSTDatas('ADS_TYPE',3)!=''){
			if($data['mobileCatListTheme']=='')return WSTReturn('请输入手机端商品列模板名');
			if($data['mobileDetailTheme']=='')return WSTReturn('请输入手机端商品详情模板名');
		}
	    if(WSTDatas('ADS_TYPE',2)!=''){
			if($data['wechatCatListTheme']=='')return WSTReturn('请输入微信端商品列模板名');
			if($data['wechatDetailTheme']=='')return WSTReturn('请输入微信端商品详情模板名');
		}
		$result = $this->allowField(true)->save($data);
		if(false !== $result){
			WSTClearAllCache();
			return WSTReturn("新增成功", 1);
		}else{
			return WSTReturn($this->getError(),-1);
		}
	}

	/**
	 * 编辑
	 */
	public function edit(){
		$catId = input('post.id/d');
		$data = input('post.');
		WSTUnset($data,'catId,dataFlag,createTime');
		$validate = new validate();
		if(!$validate->scene('edit')->check($data))return WSTReturn($validate->getError());
		if(WSTDatas('ADS_TYPE',3)!=''){
			if($data['mobileCatListTheme']=='')return WSTReturn('请输入手机端商品列模板名');
			if($data['mobileDetailTheme']=='')return WSTReturn('请输入手机端商品详情模板名');
		}
	    if(WSTDatas('ADS_TYPE',2)!=''){
			if($data['wechatCatListTheme']=='')return WSTReturn('请输入微信端商品列模板名');
			if($data['wechatDetailTheme']=='')return WSTReturn('请输入微信端商品详情模板名');
		}
		$result = $this->allowField(true)->save($data,['catId'=>$catId]);
		$ids = array();
		$ids = $this->getChild($catId);
		$this->where("catId in(".implode(',',$ids).")")->update(['isShow' => (int)$data['isShow'],'isFloor'=> $data['isFloor'],'commissionRate'=>(float)$data['commissionRate'],'showWay'=>$data['showWay']]);
		if(false !== $result){
			//修改其下的所有风格模板
			if((int)$data['isForce']==1){
				$theme['catListTheme'] = $data['catListTheme'];
				$theme['detailTheme'] = $data['detailTheme'];
				if(WSTDatas('ADS_TYPE',3)!=''){
					$theme['mobileCatListTheme'] = $data['mobileCatListTheme'];
				    $theme['mobileDetailTheme'] = $data['mobileDetailTheme'];
				}
				if(WSTDatas('ADS_TYPE',3)!=''){
					$theme['wechatCatListTheme'] = $data['wechatCatListTheme'];
				    $theme['wechatDetailTheme'] = $data['wechatDetailTheme'];
				}
                $this->where("catId in(".implode(',',$ids).")")->update($theme);
			}
			if($data['isShow']==0){
				//删除购物车里的相关商品
				$goods = Db::name('goods')->where([["goodsCatId",'in',$ids],['isSale','=',1]])->field('goodsId')->select();
				if(count($goods)>0){
					$goodsIds = [];
					foreach ($goods as $key =>$v){
							$goodsIds[] = $v['goodsId'];
					}
					Db::name('carts')->where([['goodsId','in',$goodsIds]])->delete();
				}
		    	//把相关的商品下架了
		        Db::name('goods')->where("goodsCatId in(".implode(',',$ids).")")->update(['isSale' => 0]);
		        WSTClearAllCache();
			}
			return WSTReturn("修改成功", 1);
		}else{
			return WSTReturn($this->getError(),-1);
		}
	}
	
	/**
	 * 删除
	 */
	public function del(){
		$ids = array();
		$id = input('post.id/d');
		$ids = $this->getChild($id);
		Db::startTrans();
        try{
		    $data = [];
		    $data['dataFlag'] = -1;
		    $result = $this->where([['catId','in',$ids]])->update($data);
		    if(false !== $result){
		        //删除购物车里的相关商品
				$goods = Db::name('goods')->where([["goodsCatId",'in',$ids],['isSale','=',1]])->field('goodsId')->select();
				if(count($goods)>0){
					$goodsIds = [];
					foreach ($goods as $key =>$v){
							$goodsIds[] = $v['goodsId'];
					}
					Db::name('carts')->where([['goodsId','in',$goodsIds]])->delete();
				}
				//删除商品属性
				Db::name('attributes')->where("goodsCatId in(".implode(',',$ids).")")->update(['dataFlag'=>-1]);
		    	//删除商品规格
				Db::name('spec_cats')->where("goodsCatId in(".implode(',',$ids).")")->update(['dataFlag'=>-1]);
		    	//把相关的商品下架了
		        Db::name('goods')->where("goodsCatId in(".implode(',',$ids).")")->update(['isSale' => 0]);
		        WSTClearAllCache();
		    }
            Db::commit();
	        return WSTReturn("删除成功", 1);
        }catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('删除失败',-1);
        }
	}
	
    /**
	 * 根据子分类获取其父级分类
	 */
	public function getParentIs($id,$data = array()){
		$data[] = $id;
		$parentId = $this->where('catId',$id)->value('parentId');
		if($parentId==0){
			krsort($data);
			return $data;
		}else{
			return $this->getParentIs($parentId, $data);
		}
	}
}