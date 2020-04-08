<?php
namespace wstmart\api\controller;

use think\Db;
use wstmart\common\model\GoodsCats;
use wstmart\api\model\Goods;
use wstmart\common\model\TUserMap;
use wstmart\common\model\TProductRecommend;

/**
 * 门店控制器
 */
class Shops extends Base
{
    /**
     * 店铺街
     */
    public function shopStreet()
    {
        $gc = new GoodsCats();
        $goodsCats = $gc->listQuery(0);
        $this->assign('goodscats', $goodsCats);
        $this->assign("keyword", input('keyword'));
        return $this->fetch('shop_street');
    }
    /**
     * 店铺首页
     */
    public function index()
    {
        $s = model('shops');
        $shopId = (int)input('shopId', 1);
        $data = [];
        $data['shop'] = $s->getShopInfo($shopId);
        $this->assign('data', $data);
        $this->assign("goodsName", input('goodsName'));
        $this->assign('ct1', (int)input("param.ct1/d", 0));//一级分类
        $this->assign('ct2', (int)input("param.ct2/d", 0));//二级分类
        $this->assign('shopId', $shopId);//店铺id
        
        $sm = model("common/ShopCats");
        $goodsCat = $sm->listQuery($shopId, 0);
        $this->assign('goodsCat', $goodsCat);
        
        return $this->fetch($data['shop']["mobileShopHomeTheme"]);
    }
    /**
    * 店铺详情
    */
    public function view()
    {
        $s = model('shops');
        $shopId = (int)input("param.shopId/d", 1);
        $data = [];
        $data['shop'] = $s->getShopInfo($shopId);
        $this->assign('data', $data);
        $cart = model('carts')->getCartInfo();
        $this->assign('cart', $cart);
        return $this->fetch('shop_view');
    }
    /**
    * 店铺商品列表
    */
    public function goods()
    {
        $s = model('shops');
        $shopId = (int)input("param.shopId/d", 1);
        $ct1 = input("param.ct1/d", 0);
        $ct2 = input("param.ct2/d", 0);
        $goodsName = input("param.goodsName");
        $gcModel = model('ShopCats');
        $data = [];
        $data['shop'] = $s->getShopInfo($shopId);
        $data['shopcats'] = $gcModel->getShopCats($shopId);
        $this->assign('shopId', $shopId);//店铺id
        $this->assign('ct1', $ct1);//一级分类
        $this->assign('ct2', $ct2);//二级分类
        $this->assign('goodsName', urldecode($goodsName));//搜索
        $this->assign('data', $data);

        return $this->fetch('shop_goods_list');
    }
    /**
    * 获取店铺商品
    */
    public function getShopGoods()
    {
        $shopId = (int)input('shopId', 1);
        $g = model('goods');
        $rs = $g->shopGoods($shopId);
        return $this->outSuccess($rs);
    }

    /**
    * 获取店铺信息与精选商品
    */
    public function getShopRecommend()
    {
        $shopId = (int)input('shopId', 1);
        $data = [];
        $s = model('shops');
        $data['shop'] = $s->getShopInfo($shopId);
        $data['recom'] = $s->api_shop_recommend($shopId) ?? [];
        
        return $this->outSuccess($data);
    }


    /**
     * 获取店铺商品
     */
    public function getShopGoodsByUserId()
    {
        $user_id = (int)input('user_id');
        $is_sale =(int)input('is_sale', -1);

        $shops = Db::name('shops')->where(["userId"=>$user_id])->find();

        if (!$shops) {
            return $this->outFail(500, '商城未开通');
        }
        $g = model('goods');
        $rs = $g->shopGoods($shops['shopId'], $is_sale);
   
        // $goodsId = TProductRecommend::getProductRec($user_id);


        // //规则排序的新数组 (推荐了后在最前面)
        // $new_arr = $temp =[];

        // foreach ($rs['data'] as $k=>$v) {
        //     //$rs['data'][$k]['goodsImg'] = $this->request->domain().'/'.WSTImg($v['goodsImg'],3,'goodsLogo');
        //     $rs['data'][$k]['url'] = url('/mgoods-'.$v['goodsId'], '', true, true);

        //     if ($goodsId && in_array($v['goodsId'], $goodsId)) {
        //         $rs['data'][$k]['rec'] = 1;
        //         array_unshift($new_arr, $rs['data'][$k]);
        //     } else {
        //         $rs['data'][$k]['rec'] = 0;
        //         $new_arr[] = $rs['data'][$k];
        //     }
        // }
        // //若推荐两个商品 谁先推荐谁排序在前
        // if(count($goodsId) == 2){
            
        //     if ($new_arr[0]['goodsId'] != $goodsId[0]) {

        //         $temp = $new_arr[0];
        //         $new_arr[0] = $new_arr[1];
        //         $new_arr[1] = $temp;

        //     }
        // }
        
        
        // $rs['data'] = $new_arr;

        return $this->outSuccess($rs);
    }


    public function getFloorData()
    {
        $m = model("common/Goods");
        $rs = $m->shopCatPageQuery(3);
        return $rs;
    }

    /**
     * 店铺街列表
     */
    public function pageQuery()
    {
        $m = model('shops');
        $rs = $m->pageQuery(input('pagesize/d'));
        foreach ($rs['data'] as $key =>$v) {
            $rs['data'][$key]['shopImg'] = WSTImg($v['shopImg'], 3, 'shopLogo');
        }
        return $rs;
    }
}
