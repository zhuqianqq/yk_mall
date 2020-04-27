<?php

namespace wstmart\api\model;

use util\Tools;
use wstmart\common\model\Goods as CGoods;
use think\Db;
use wstmart\common\model\TUserMap;
use wstmart\shop\model\Goods as M;

/**
 * 商品类
 */
class Goods extends CGoods{
	/**
	 * 获取列表
	 */
	public function pageQuery($goodsCatIds = []){
		//查询条件
		$keyword = input('keyword');
		$brandId = input('brandId/d');
		$isFreeShipping = input('isFreeShipping/d');
		$words = $where2 = $where3 = $where4 = [];
		$where[] = ['goodsStatus','=',1];
		$where[] =['g.dataFlag','=',1];
		$where[] =['isSale','=',1];
		if($keyword!='')$where2 = $this->getKeyWords($keyword);
		if($brandId>0)$where[] = ['g.brandId','=',$brandId];
		//排序条件
		$orderBy = input('condition/d',0);
		$orderBy = ($orderBy>=0 && $orderBy<=4)?$orderBy:0;
		$order = (input('desc/d',0)==1)?1:0;
		$pageBy = ['saleNum','shopPrice','visitNum','saleTime'];
		$pageOrder = ['desc','asc'];
		if($isFreeShipping==1)$where[] = ['isFreeShipping','=',1];
		//属性筛选
		$goodsIds = $this->filterByAttributes();
		if(!empty($goodsIds))$where[] = ['g.goodsId','in',$goodsIds];
		//处理价格
		$minPrice = input("param.minPrice/d");//开始价格
		$maxPrice = input("param.maxPrice/d");//结束价格
		if($minPrice!="")$where3 = "g.shopPrice >= ".(float)$minPrice;
		if($maxPrice!="")$where4 = "g.shopPrice <= ".(float)$maxPrice;
		if(!empty($goodsCatIds))$where[] = ['goodsCatIdPath','like',implode('_',$goodsCatIds).'_%'];
		$list = Db::name('goods')->alias('g')->join("__SHOPS__ s","g.shopId = s.shopId")->join('__GOODS_SCORES__ gs','gs.goodsId=g.goodsId')
				->where($where)->where($where2)->where($where3)->where($where4)
				->field('g.goodsId,goodsName,saleNum,shopPrice,marketPrice,isSpec,goodsImg,appraiseNum,visitNum,s.shopId,shopName,isSelf,isFreeShipping,gallery,gs.totalScore,gs.totalUsers')
				->order($pageBy[$orderBy]." ".$pageOrder[$order].",goodsId asc")
				->paginate(input('pagesize/d'))->toArray();
		return $list;
	}
    
	/**
	 * 关键字
	 */
	public function getKeyWords($name){
		$words = WSTAnalysis($name);
		if(!empty($words)){
			$str = [];
			if(count($words)==1){
				$str[] = ['g.goodsSerachKeywords','LIKE','%'.$words[0].'%'];
			}else{
				foreach ($words as $v){
					$str[] = ['g.goodsSerachKeywords','LIKE','%'.$v.'%'];
				}
			}
			return $str;
		}
		return "";
	}
	/**
	 * 获取商品资料在前台展示
	 */
	public function getBySale($goodsId)
    {
        $key = input('key',true);
        // 浏览量
        $this->where('goodsId', $goodsId)->setInc('visitNum', 1);
        $rs = Db::name('goods')->where(['goodsId' => $goodsId, 'dataFlag' => 1])->find();
        if (!empty($rs)) {
            $rs['read'] = false;
            $rs['goodsDesc'] = htmlspecialchars_decode($rs['goodsDesc']);
            $rs['goodsDesc'] = str_replace('${DOMAIN}', WSTConf('CONF.resourcePath'), $rs['goodsDesc']);
            //判断是否可以公开查看
            //$viKey = WSTShopEncrypt($rs['shopId']);
            $viKey = true;//暂时不做该判断
            if (($rs['isSale'] == 0 || $rs['goodsStatus'] == 0) && $viKey != $key) return [];
            if ($key != '') $rs['read'] = true;
            //获取店铺信息
            $rs['shop'] = model('shops')->getShopInfo((int)$rs['shopId']);
         
            if (empty($rs['shop'])) return [];
            $goodsCats = Db::name('cat_shops')->alias('cs')->join('__GOODS_CATS__ gc', 'cs.catId=gc.catId and gc.dataFlag=1', 'left')->join('__SHOPS__ s', 's.shopId = cs.shopId', 'left')
                ->where('cs.shopId', $rs['shopId'])->field('cs.shopId,s.shopTel,gc.catId,gc.catName')->select();
            
            $rs['shop']['catId'] = $goodsCats[0]['catId']??'';
            $rs['shop']['shopTel'] = $goodsCats[0]['shopTel']??'';

            $cat = [];
            foreach ($goodsCats as $v) {
                $cat[] = $v['catName'];
            }
            $rs['shop']['cat'] = implode('，', $cat);
            if (empty($rs['shop'])) {
                return [];
            }

            $gallery = [];
            if ($rs['gallery'] != '') {
                $gallery = explode(',', $rs['gallery']);
            }
            $rs['gallery'] = $gallery;
            //获取规格值
            $specs = Db::name('spec_cats')->alias('gc')->join('__SPEC_ITEMS__ sit', 'gc.catId=sit.catId', 'inner')
                ->where(['sit.goodsId' => $goodsId, 'gc.isShow' => 1, 'sit.dataFlag' => 1])
                ->field('gc.isAllowImg,gc.catName,sit.catId,sit.itemId,sit.itemName,sit.itemImg')
                ->order('sit.itemId asc,gc.isAllowImg desc,gc.catSort asc,gc.catId asc')->select();
        
            $rs['spec'] = array();
            foreach ($specs as $key => $v) {
				//$rs['spec'][$v['catId']]['name'] = $v['catName'];
				//$rs['spec'][$v['catId']]['list'][] = $v;
                array_push($rs['spec'], $v);
            }
            //获取销售规格
            $sales = Db::name('goods_specs')->where('goodsId', $goodsId)->field('id,isDefault,productNo,specIds,marketPrice,specPrice,specStock')->select();
            if (!empty($sales)) {
                $rs['saleSpec'] = array();
                foreach ($sales as $key => $v) {
					// $str = explode(':',$v['specIds']);
					// sort($str);
					// unset($v['specIds']);
					// $rs['saleSpec'][implode(':',$str)] = $v;
                    array_push($rs['saleSpec'], $v);
                }
            }
            //获取商品属性
            $rs['attrs'] = Db::name('attributes')->alias('a')->join('goods_attributes ga', 'a.attrId=ga.attrId', 'inner')
                ->where(['a.isShow' => 1, 'dataFlag' => 1, 'goodsId' => $goodsId])->field('a.attrName,ga.attrVal')
                ->order('attrSort asc')->select();
            //获取商品评分
            $rs['scores'] = Db::name('goods_scores')->where('goodsId', $goodsId)->field('totalScore,totalUsers')->find();
            $rs['scores']['totalScores'] = ($rs['scores']['totalScore'] == 0) ? 5 : WSTScore($rs['scores']['totalScore'], $rs['scores']['totalUsers'], 5, 0, 3);
            WSTUnset($rs, 'totalUsers');
            //关注
            $f = model('Favorites');
            $rs['favShop'] = $f->checkFavorite($rs['shopId'], 1);
            $rs['favGood'] = $f->checkFavorite($goodsId, 0);
        }
        return $rs;
    }


    /**
	 * 获取商品资料在前台展示
	 */
	// public function getGoodsStock($goodsId)
    // {

    //     $goodStock = Db::name('goods')->field('goodStock')->where(['goodsId' => $goodsId, 'dataFlag' => 1])->find();

    //     //获取销售规格
    //     $sales = Db::name('goods_specs')->where('goodsId', $goodsId)->field('id,isDefault,productNo,specIds,marketPrice,specPrice,specStock')->select();
    //     if (!empty($sales)) {
    //         $rs['saleSpec'] = array();
    //         foreach ($sales as $key => $v) {
    //             // $str = explode(':',$v['specIds']);
    //             // sort($str);
    //             // unset($v['specIds']);
    //             // $rs['saleSpec'][implode(':',$str)] = $v;
    //             array_push($rs['saleSpec'], $v);
    //         }
    //     }

    //     dd($goodStock);
    // }


	public function historyQuery(){
		$ids = cookie("wx_history_goods");
		if(empty($ids))return [];
	    $where = [];
	    $where[] = ['isSale','=',1];
	    $where[] = ['goodsStatus','=',1]; 
	    $where[] = ['dataFlag','=',1]; 
	    $where[] = ['goodsId','in',$ids];
	    $orderBy = "field(`goodsId`,".implode(',',$ids).")";
        return Db::name('goods')
                   ->where($where)->field('goodsId,goodsName,goodsImg,saleNum,shopPrice')
                   ->orderRaw($orderBy)
                   ->paginate((int)input('pagesize'))->toArray();
	}
    /**
     * 获取符合筛选条件的商品ID
     */
    public function filterByAttributes(){
    	$vs = input('vs');
    	if($vs=='')return [];
    	$vs = explode(',',$vs);
    	$goodsIds = [];
    	$prefix = config('database.prefix');
		//循环遍历每个属性相关的商品ID
	    foreach ($vs as $v){
	    	$goodsIds2 = [];
	    	$attrVal = input('v_'.(int)$v);
	    	if($attrVal=='')continue;
		    	$sql = "select goodsId from ".$prefix."goods_attributes 
		    	where attrId=".(int)$v." and find_in_set('".$attrVal."',attrVal) ";
				$rs = Db::query($sql);
				if(!empty($rs)){
					foreach ($rs as $vg){
						$goodsIds2[] = $vg['goodsId'];
					}
				}
			//如果有一个属性是没有商品的话就不需要查了
			if(empty($goodsIds2))return [-1];
			//第一次比较就先过滤，第二次以后的就找集合
			$goodsIds2[] = -1;
			if(empty($goodsIds)){
				$goodsIds = $goodsIds2;
			}else{
				$goodsIds = array_intersect($goodsIds,$goodsIds2);
			}
		}
		return $goodsIds;
    }

    /**
     * 手机端新增商品
     * @param int $user_id 商城用户id
     * @return array
     */
    public function addGoods($user_id)
    {
        $post = input('post.');
        Tools::addLog("save_goods","post_data:".json_encode($post,JSON_UNESCAPED_UNICODE));

        $shopId = $this->getShopIdfromUserId($user_id); //店铺id
        if($shopId <= 0){
            return Tools::outJson(100,"未找到用户店铺，不能添加商品");
        }
        $data['shopId'] = $shopId;
        $data['goodsName'] = isset($post['goodsName']) ? trim($post['goodsName']) : '';
        if(empty($data['goodsName'])){
            return Tools::outJson(100,"商品名称不能为空");
        }
        if(mb_strlen($data['goodsName']) > 30){
            return Tools::outJson(100,"商品名称不能越过30个字");
        }
        $gallery = isset($post['gallery']) ? json_decode($post['gallery'],true) : '';
        if(empty($gallery)){
            return Tools::outJson(100,"商品图片不能为空");
        }
        $data['goodsImg'] = $gallery ? $gallery[0] : ''; //第一张图
        $data['gallery'] = implode(",",$gallery); //商品图片

        $data['shopPrice'] = floatval($post['shopPrice']); //价格
        if($data['shopPrice'] <= 0){
            return Tools::outJson(100,"商品价格不能小于0");
        }
        $data['marketPrice'] = $data['shopPrice'];  //市场价
        $data['costPrice'] = $data['shopPrice'];
        $data['goodsStock'] = $post['goodsStock'] ?? 1000; //库存

        $data['wechat'] = $post['wechat'] ?? '';
        $data['weight'] = $post['weight'] ?? 0; //权重
        $goodsDesc = htmlspecialchars_decode($post['goodsDesc']);
        $data['goodsDesc'] = WSTRichEditorFilter($goodsDesc); //商品详情
        $goods_no = WSTGoodsNo();
        $product_no = WSTGoodsNo();
        $data['goodsSn'] = $goods_no; //商品编号
        $data['productNo'] = $product_no; //商品货号
        $data['isSale'] = 1; //上架
        $data['saleTime'] = date('Y-m-d H:i:s');
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['goodsCatId'] = M::DEFAULT_CAT_ID; //商品分类给默认值
        $data['goodsCatIdPath'] = M::DEFAULT_CAT_ID."_";
        $data['goodsType'] = 0; //商品类型 0：实物商品，1:虚拟商品
        $data['isFreeShipping'] = 0; //是否包邮 1:包邮，0:买家承担运费
        $data['shopExpressId'] = 6; //顺丰速递
        $data['goodsStatus'] = 1; //已审核
        $data['isSpec'] = empty($specList)?0:1; //是否有规格

        $specList = isset($post['specList']) ? json_decode($post['specList'],true) : ''; //规格列表

        Db::startTrans();
        try{
            $ret = $this->allowField(true)->save($data);
            if(!$ret){
                return Tools::outJson(-1,'新增保存失败');
            }
            $goodsId = $this->goodsId;
            //建立商品评分记录
            $gs = [];
            $gs['goodsId'] = $goodsId;
            $gs['shopId'] = $shopId;
            Db::name('goods_scores')->insert($gs);

            if(!empty($specList)){
                $itemIdArr = [];
                foreach($specList as &$spec){
                    $catItems = $spec['catItems'];
                    $tmp_ids = [];
                    foreach($catItems as $k => &$v) {
                        $sitem = [];
                        $sitem['shopId'] = $shopId;
                        $sitem['catId'] = (int)$spec['catId']; //分类id
                        $sitem['goodsId'] = $goodsId;
                        $sitem['itemName'] = $v['itemName']; //名称
                        $sitem['itemImg'] = '';
                        $sitem['dataFlag'] = 1;
                        $sitem['createTime'] = date('Y-m-d H:i:s');

                        $itemId = Db::name('spec_items')->insertGetId($sitem);
                        $v['itemId'] = $itemId;
                        $tmp_ids[] = $itemId;
                    }
                    $spec['catItems'] = $catItems; //更新对象
                    $itemIdArr[] = $tmp_ids; //将itemId存起来
                }

                //保存销售规格
                $defaultPrice = 0;
                $totalStock = 0;//总库存
                $gspecArray = [];
                $specsIdArr = $this->generateSpecIdGroup($itemIdArr);
                Tools::addLog("save_goods","itemIdArr:".json_encode($itemIdArr).",specsIdArr:".json_encode($specsIdArr),$specList);
                
                foreach($specsIdArr as $k => $specIds){
                    $gspec = [];
                    $gspec['specIds'] = $specIds; //95:97:103
                    $gspec['shopId'] = $shopId;
                    $gspec['goodsId'] = $goodsId;
                    $gspec['productNo'] = $product_no ."-" . ($k + 1); //货号
                    $gspec['marketPrice'] = $data['shopPrice']; //市场价
                    $gspec['specPrice'] = $data['shopPrice'];   //本店价
                    $gspec['specStock'] = $data['goodsStock'];  //库存
                    $gspec['warnStock'] = 1;    //预警库存
                    $gspec['specWeight'] = 0;   //商品重量
                    $gspec['specVolume'] = 0;   //商品体积

                    //设置默认规格
                    if ($k == 0) {
                        $defaultPrice = $gspec['specPrice'];
                        $gspec['isDefault'] = 1; //推荐规则
                    } else {
                        $gspec['isDefault'] = 0;
                    }
                    $gspecArray[] = $gspec;
                    $totalStock += $gspec['specStock'];  //获取总库存
                }

                if (count($gspecArray) > 0) {
                    Db::name('goods_specs')->insertAll($gspecArray);
                    //更新默认价格和总库存
                    $this->where('goodsId', $goodsId)->update(['isSpec' => 1,'costPrice' => $defaultPrice,  'shopPrice' => $defaultPrice, 'goodsStock' => $totalStock]);
                }else {
                    $this->where('goodsId', $goodsId)->update(['isSpec' => 0]);
                }
            }

            //保存关键字
            $searchKeys = WSTGroupGoodsSearchKey($goodsId);
            $this->where('goodsId', $goodsId)->update(['goodsSerachKeywords' => implode(',', $searchKeys)]);

            Db::commit();
            Tools::addLog("save_goods","success goodsId:".$goodsId);

            return Tools::outJson(0,'新增成功',["id" => $goodsId]);
        } catch (\Exception $ex) {
            Db::rollback();
            Tools::addLog("save_goods","error:".$ex->getFile().",line:".$ex->getLine().',message:'.$ex->getMessage());
            return Tools::outJson(500,"新增失败：".$ex->getMessage());
        }
    }


    /**
     * 编辑商品资料
     * @param int $goodsId
     * @param int $userId
     */
    public function editGoods($goodsId,$userId)
    {
        $post = input('post.');
        Tools::addLog("save_goods","post_data:".json_encode($post,JSON_UNESCAPED_UNICODE));

        $shopId = $this->getShopIdfromUserId($userId); //店铺id
        $objGoods = $this->where(['goodsId' => $goodsId, 'shopId' => $shopId, 'dataFlag' => 1])->field('goodsSn,productNo,goodsType,goodsStock')->find();
        if (empty($objGoods)) {
            return Tools::outJson(100,"商品不存在");
        }

        $data['goodsName'] = isset($post['goodsName']) ? trim($post['goodsName']) : '';
        if(empty($data['goodsName'])){
            return Tools::outJson(100,"商品名称不能为空");
        }
        if(mb_strlen($data['goodsName']) > 30){
            return Tools::outJson(100,"商品名称不能越过30个字");
        }
        $gallery = isset($post['gallery']) ? json_decode($post['gallery'],true) : '';
        if(empty($gallery)){
            return Tools::outJson(100,"商品图片不能为空");
        }
        $data['goodsImg'] = $gallery ? $gallery[0] : ''; //第一张图
        $data['gallery'] = implode(",",$gallery); //商品图片

        $data['shopPrice'] = floatval($post['shopPrice']); //价格
        if($data['shopPrice'] <= 0){
            return Tools::outJson(100,"商品价格不能小于0");
        }
        $data['marketPrice'] = $data['shopPrice'];  //市场价
        $data['costPrice'] = $data['shopPrice'];
        $data['goodsStock'] = $post['goodsStock'] ?? 1000; //库存

        $data['wechat'] = $post['wechat'] ?? '';
        $data['weight'] = $post['weight'] ?? 0; //权重
        $goodsDesc = htmlspecialchars_decode($post['goodsDesc']);
        $data['goodsDesc'] = WSTRichEditorFilter($goodsDesc); //商品详情

        Db::startTrans();
        try {
            //商品图片
            //WSTUseResource(0, $goodsId, $data['goodsImg'], 'goods', 'goodsImg');
            //商品相册
            //WSTUseResource(0, $goodsId, $data['gallery'], 'goods', 'gallery');

            // 商品描述图片
            //$desc = $this->where('goodsId', $goodsId)->value('goodsDesc');
            //WSTEditorImageRocord(0, $goodsId, $desc, $data['goodsDesc']);

            $ret = $this->allowField(true)->save($data, ['goodsId' => $goodsId]);
            if(!$ret){
                return Tools::outJson(-1,'编辑保存失败');
            }

            $specList = isset($post['specList']) ? json_decode($post['specList'],true) : ''; //规格列表

            if (!empty($specList)) {
                $itemIdArr = []; //将itemId存起来
                //先标记作废之前的规格值
                Db::name('spec_items')->where(['shopId' => $shopId, 'goodsId' => $goodsId])->update(['dataFlag' => -1]);
                foreach($specList as &$spec){
                    $catItems = $spec['catItems'];
                    $tmp_ids = [];
                    foreach($catItems as $k => &$v) {
                        $itemId = isset($v['itemId']) ? intval($v['itemId']) : 0;
                        $sitem = [];
                        $sitem['shopId'] = $shopId;
                        $sitem['catId'] = (int)$spec['catId']; //分类id
                        $sitem['goodsId'] = $goodsId;
                        $sitem['itemName'] = $v['itemName']; //名称
                        $sitem['dataFlag'] = 1;

                        if($itemId > 0){
                            Db::name('spec_items')->where(['shopId' => $shopId, 'itemId' => $itemId])->update($sitem);
                        }else{
                            $sitem['itemImg'] = '';
                            $sitem['createTime'] = date('Y-m-d H:i:s');
                            $itemId = Db::name('spec_items')->insertGetId($sitem);
                            $v['itemId'] = $itemId;
                        }
                        $tmp_ids[] = $itemId;
                    }
                    $spec['catItems'] = $catItems; //更新对象
                    $itemIdArr[] = $tmp_ids; //将itemId存起来
                }
                //删除已经作废的规格值
                Db::name('spec_items')->where(['shopId' => $shopId, 'goodsId' => $goodsId, 'dataFlag' => -1])->delete();

                //保存销售规格
                $defaultPrice = 0;
                $totalStock = 0;//总库存
                $specsIdArr = $this->generateSpecIdGroup($itemIdArr); //生成sku组合
                Tools::addLog("save_goods","itemIdArr:".json_encode($itemIdArr).",specsIdArr:".json_encode($specsIdArr),$specList);
                $product_no = $objGoods['productNo']; //货号

                //把之前的销售规格值标记删除
                Db::name('goods_specs')->where(['goodsId' => $goodsId, 'shopId' => $shopId])->update(['dataFlag' => -1]);

                foreach($specsIdArr as $k => $specIds){
                    $gspec = [];
                    $gspec['shopId'] = $shopId;
                    $gspec['goodsId'] = $goodsId;
                    $gspec['productNo'] = $product_no ."-" . ($k + 1); //货号
                    $gspec['marketPrice'] = $data['shopPrice']; //市场价
                    $gspec['specPrice'] = $data['shopPrice'];   //本店价

                    //规格库存判断：如果玩家未修改库存  总库存按照之前的
                    if($objGoods['goodsStock'] == $data['goodsStock'] ){
                        Tools::addLog("save_goods","规格的数量",count($specsIdArr));
                        $gspec['specStock']  = $data['goodsStock']/count($specsIdArr);
                    }else{
                        $gspec['specStock'] = $data['goodsStock'];  
                    }

                    $gspec['warnStock'] = 1;    //预警库存
                    $gspec['specWeight'] = 0;   //商品重量
                    $gspec['specVolume'] = 0;   //商品体积
                    $gspec['dataFlag'] = 1;

                    //设置默认规格
                    if ($k == 0) {
                        $defaultPrice = $gspec['specPrice'];
                        $gspec['isDefault'] = 1; //推荐规则
                    } else {
                        $gspec['isDefault'] = 0;
                    }
                    //如果是已经存在的值就修改内容，否则新增
                    $spec_obj = Db::name('goods_specs')->where(['goodsId' => $goodsId, 'specIds' => $specIds])->find();
                    if (!empty($spec_obj)) {
                        Db::name('goods_specs')->where(['goodsId' => $goodsId, 'specIds' => $specIds])->update($gspec);
                    } else {
                        $gspec['specIds'] = $specIds; //95:97:103
                        Db::name('goods_specs')->insertGetId($gspec); //新增
                    }
                    $totalStock += $gspec['specStock'];  //总库存
                }

                //更新默认价格和总库存
                $this->where('goodsId', $goodsId)->update(['isSpec' => 1,'costPrice' => $defaultPrice, 'shopPrice' => $defaultPrice, 'goodsStock' => $totalStock]);
                //删除作废的销售规格值
                Db::name('goods_specs')->where(['goodsId' => $goodsId, 'shopId' => $shopId, 'dataFlag' => -1])->delete();
            } else {
                Db::name('spec_items')->where(['goodsId' => $goodsId, 'shopId' => $shopId])->delete();
                Db::name('goods_specs')->where(['goodsId' => $goodsId, 'shopId' => $shopId])->delete();
            }

            //保存关键字
            $searchKeys = WSTGroupGoodsSearchKey($goodsId);
            $this->where('goodsId', $goodsId)->update(['goodsSerachKeywords' => implode(',', $searchKeys)]);

            //删除购物车里的商品
            model('common/carts')->delCartByUpdate($goodsId);

            Db::commit();
            return Tools::outJson(0,"编辑成功",['id' => $goodsId]);
        } catch (\Exception $ex) {
            Db::rollback();
            Tools::addLog("save_goods","edit_goods error:".$ex->getFile().",line:".$ex->getLine().',message:'.$ex->getMessage());
            return Tools::outJson(500,"编辑失败：".$ex->getMessage());
        }
    }

    /**
	 * 推荐图片列表
	 */
    public function getRecommendImgs($page,$page_size){
        
        $where = ["isRecom" => 1, "isSale" => 1, 'dataFlag' => 1];
        $order = ['shopId'=>'asc','createTime' => 'desc'];

        $query = $this->field("goodsId,goodsImg,goodsName,shopPrice")
        ->where($where)->order($order);

        $total = $query->count(); //总记录条数

        $list = [];
        $has_next = 0; //是否有下一页 0:无，1：有
        if ($total > 0) {
            $offset = ($page - 1) * $page_size;
            $list = $query->limit($offset, $page_size + 1)//多查一条
                    ->order($order)   //已上架商品需排在未上架商品之前
                    ->select();
            self::checkHasNextPage($list, $page_size, $has_next);
        }
        //图片url处理
        // foreach($list as $k => $v){
           
        //     if(!stristr($v->goodsImg,'http')){
          
        //         $list[$k]->goodsImg ='http://'. $_SERVER['HTTP_HOST']. '/' . $v->goodsImg;
        //     }

        //     $list[$k]->goodsDetailUrl ='http://'. $_SERVER['HTTP_HOST']. '/mgoods-' . $v->goodsId . '.html';

        // }

        return [$list, $total, $has_next];
    }


    /**
     * 
     * 猜你喜欢
     * 
     */
    public function getGuessLike($page,$page_size){
        
        $where = ["isSale" => 1, "goodsStatus" => 1, 'dataFlag' => 1];
       
        $goods_group = Db::name('goods')
            ->where($where)
            ->group('goodsCatId')
            ->column('goodsCatId');

        $query = $this->field('goodsId,goodsName,goodsImg,goodsSn,goodsStock,saleNum,shopPrice,marketPrice,isSpec,appraiseNum,visitNum,isNew')
            ->where($where)
            ->where([['goodsCatId', 'in', $goods_group]]);

        $total = $query->count(); //总记录条数

        $list = [];
        $has_next = 0; //是否有下一页 0:无，1：有
        if ($total > 0) {
            $offset = ($page - 1) * $page_size;
            $list = $query->limit($offset, $page_size + 1)//多查一条
                    ->orderRaw('rand()')   
                    ->select();
            self::checkHasNextPage($list, $page_size, $has_next);
        }
     
        return [$list, $total, $has_next];

    }
    

    /**
     * 是否有下一页记录
     * @param Collection $list
     * @param int $page_size 每页记录条数
     * @param int $next 是否有下一页 0-无，1-有
     */
    public static function checkHasNextPage(&$list,$page_size,&$next)
    {
        $next = 0;
        if (count($list) > $page_size){
            $list = $list->slice(0,$page_size);
            $next = 1;
        }
    }


    
    /**
	 * 首页热卖商品
	 */
	public function api_index_hotgoods(){
        
		$where[] =['g.dataFlag','=',1];
		$where[] =['g.isSale','=',1];
        $where[] =['g.isHot','=',1];
        $where[] = ['g.mIsIndex', '=', 1];
        $where[] = ['s.shopStatus', '=', 1];
        

		$list = Db::name('goods')->alias('g')->join("__SHOPS__ s","g.shopId = s.shopId")
				->where($where)
				->field('g.goodsId,goodsName,shopPrice,goodsImg,goodsSeoDesc,commissionFee')
				->order('g.weight desc,g.createTime desc')
				->limit(3)->select();
		return $list;

    }
}
