<?php
namespace wstmart\api\controller;
use think\Db;
use wstmart\common\model\GoodsCats;
use wstmart\common\model\Attributes as AT;
use wstmart\common\model\GoodsShare;
use \wstmart\shop\model\Goods as M;
use wstmart\common\model\TUserMap;
use think\facade\Cache;

/**
 * 商品控制器
 */
class Goods extends Base
{
    //注册中件间
    protected $middleware = [
        'AccessCheck' => ['only' => ['changeSaleStatus','save','del']],
    ];

	/**
	 * 商品详情
	 */
	public function detail()
    {
        $goods_detail_prefix = config('cachekeys.goods_detail_prefix');
        $goods_cache_name = $goods_detail_prefix . input('goodsId/d');
        $goods_cache_name_share = $goods_detail_prefix  . "share:" . input('goodsId/d');
        $goods_cache = Cache::get($goods_cache_name, null);
        $goods_share_cache = Cache::get($goods_cache_name_share, 0);
        if ($goods_cache) {
            $goods_cache['shareNum'] = $goods_share_cache;
            return $this->outJson(0,"查找成功！",$goods_cache);
        }

//        $root = WSTDomain();
        $m = model('goods');
        $goods = $m->getBySale(input('goodsId/d'));

        // 找不到商品记录
        if(empty($goods)) {
            return $this->outJson(1, "找不到此商品！");
        }
        $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $goods['goodsDesc'], $match);
        $goods['descArr'] = $match[1];
        $goods['shareNum'] = $goods_share_cache;
        $goodsAttr = $goods['goodsAttr'];
        if (!empty($goodsAttr)) {
            $goods['goodsAttr'] = json_decode($goodsAttr);
        } else {
            $goods['goodsAttr'] = [];
        }
       
        $goods['goodsDesc'] = str_replace('src=','style="max-width:100%;height:auto;" src=',$goods['goodsDesc']); //适应小程序样式
       
        //hook('mobileControllerGoodsIndex',['getParams'=>input()]);
        // 分类信息
        // $catInfo = Db::name("goods_cats")->field("mobileDetailTheme")->where(['catId'=>$goods['goodsCatId'],'dataFlag'=>1])->find();
        // $rule = '/<img src="\/.*?(upload.*?)"/';
        // preg_match_all($rule, $goods['goodsDesc'], $images);
        // foreach($images[0] as $k=>$v){
        //     $goods['goodsDesc'] = str_replace(WSTConf('CONF.resourcePath').'/'.$images[1][$k],$root.'/'.WSTConf("CONF.goodsLogo") . "\"  data-echo=\"".$root."/".WSTImg($images[1][$k],2), $goods['goodsDesc']);
        // }
        //暂不提供功能
        //$goods['consult'] = model('GoodsConsult')->firstQuery($goods['goodsId']);
        //$goods['appraises'] = model('GoodsAppraises')->getGoodsEachApprNum($goods['goodsId']);
        //$goods['appraise'] = model('GoodsAppraises')->getGoodsFirstAppraise($goods['goodsId']);
        
        Cache::set($goods_cache_name, $goods); //永久缓存

        return $this->outJson(0,"查找成功！",$goods);
      
	}

    /**
     * 查询成功
     * @return array
     */
	public function guessLike()
    {

        $page = input('param.page',1);
        $page_size = input('param.page_size',10); 

        $m = model('goods');
       
        list($list, $total, $has_next) = $m->getGuessLike($page,$page_size);
        
        $data = [
            "list" => $list,
            "current_page" => (int)$page,
            "total" => (int)$total,
            "has_next" => (int)$has_next, //是否有下一页
        ];
        return $this->outJson(0, "success", $data);
    }


    /**
     * 商品新增&编辑
     */
    public function save()
    {
        $user_id = $this->request->param("user_id",0,"intval"); //直播用户id
        $goodsId = $this->request->param("goodsId",0,"intval");

        if($user_id <= 0){
            return $this->outJson(100,"user_id参数不能为空");
        }

        $mall_user_id = TUserMap::getMallUserId($user_id);
        $goodModel = new \wstmart\api\model\Goods();

        if($goodsId > 0){
            //编辑
            $result = $goodModel->editGoods($goodsId,$mall_user_id); //店铺id
            return json($result);
        }else{
            //新增
            $result = $goodModel->addGoods($mall_user_id); //店铺id
            return json($result);
        }
    }

    /**
     * 删除商品
     */
    public function del()
    {
        $m = new M();
        $ret = $m->del();
        if($ret["status"] == 1){
            $goods_cache_name = config('cachekeys.goods_detail_prefix') . input('post.id/d');;
            Cache::delete($goods_cache_name);
            return $this->outJson(0,"删除成功");
        }

        return $this->outJson(-1,'删除失败');
    }

    /**
     * 获取商品规格属性
     */
    public function getSpecAttrs()
    {
        $m = new M();
        return $m->getSpecAttrs();
    }


    /**
     * 设置商品上下架状态
     */
    public function changeSaleStatus()
    {
        $good_id = input('post.good_id','',"trim");
        $is_sale = input('post.is_sale');

        if(empty($good_id)){
            return $this->outJson(100,"good_id参数不能为空");
        }
        if($is_sale === null || !in_array($is_sale,[0,1])){
            return $this->outJson(100,"is_sale参数值不正确");
        }

        if(empty($this->request->mall_user_id)){
            return $this->outJson(100,"商城用户id为空");
        }

        $m = new M();
        $shop_id = $m->getShopIdfromUserId($this->request->user_id);

        if($shop_id <= 0){
            return $this->outJson(100,"未找到店铺数据");
        }

        $good_id = explode(",",$good_id);
        
        $ret = $m->changeSaleStatus($shop_id,$good_id,$is_sale);

        if($ret["status"] == 1){
            return $this->outJson(0,"操作成功");
        }
        return $this->outJson(500,"操作失败");
    }

    /**
	 * 推荐图片列表
	 */
	public function recommendImgs(){
        $page = input('param.page',1);
        $page_size = input('param.page_size',10); 

        $m = model('goods');
       
        list($list, $total, $has_next) = $m->getRecommendImgs($page,$page_size);

        $data = [
            "list" => $list,
            "current_page" => (int)$page,
            "total" => (int)$total,
            "has_next" => (int)$has_next, //是否有下一页
        ];
        return $this->outJson(0, "success", $data);

    }

    /**
     * 商品分享
     * @return array
     */
    public function share()
    {
        $userid = $this->user_id;
        if (empty($userid)) {
            $userid = 0;
//            return $this->outJson(100, "缺少参数");
        }
        $goodsId = input('param.goodsId/d');
        $goodsName = input('param.goodsName/s');
        $shopPrice = input('param.shopPrice/s');
        $gallery = input('param.gallery/s'); // 轮播图，如果有，分享出去的详情就用这个
        $imgContent = input('param.imgContent/s'); // 详情图，如果有，分享出去的详情就用这个
        if (empty($goodsId) || empty($goodsName) || empty($shopPrice)) {
            return $this->outJson(100, "缺少参数");
        }
        $goods = Db::table("mall_goods")->get($goodsId);
        if (empty($goods)) {
            return $this->outJson(100, "没有数据");
        }
        $goodsPrice = $goods['shopPrice'];
        if (bcsub($shopPrice, $goodsPrice) < 0) {
            return $this->outJson(100, "价格不能低于该商品价格");
        }

        $goods_cache_name = config('cachekeys.goods_detail_prefix') . "share:" . $goodsId;

        $shareData = [];
        $shareData['goodsId'] = $goodsId;
        $shareData['goodsName'] = $goodsName;
        $shareData['shopPrice'] = $shopPrice;
        $shareData['userId'] = $userid;
        $shareData['createTime'] = date('Y-m-d H:i:s');
        $shareData['gallery'] = $gallery;
        $shareData['imgContent'] = $imgContent;
        $id = Db::name('goods_share')->insertGetId($shareData);
        if(!$id){
            // 失败则返回错误
            return $this->outJson(100, "分享失败");
        }else{
            $data['shareId'] = $id;
            Cache::inc($goods_cache_name);
            return $this->outJson(0, "success", $data);
        }
    }


      /**
     * 清理对应商品缓存数据
     */
    public function delGoodsCache(){
        $goodsId = input('goodsId/d','');
        if(!$goodsId){ return $this->outJson(100, "缺少参数"); }
        $goods_cache_name = config('cachekeys.goods_detail_prefix') . $goodsId;
        Cache::rm($goods_cache_name); 
    	return WSTReturn("", 1);
    }
}
