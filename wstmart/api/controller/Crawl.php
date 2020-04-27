<?php
namespace wstmart\api\controller;
use think\Db;
use think\Exception;
use util\CosHelper;
use wstmart\common\validate\Goods as Validate;
use wstmart\api\model\Index as M;
use wstmart\admin\model\Ads;
use wstmart\api\model\Shops;
use wstmart\api\model\Goods;
use think\facade\Cache;


/**
 * 抓取数据
 */
class Crawl extends Base{
	/**
     * 首页
     */
    public function index()
    {
        $type = input('type', 1);
        $url = input('url');
        switch ($type) {
            case 1:
                $domain = 'taobao';
                // 1 淘宝天猫
                parse_str(parse_url($url)['query'], $query_arr);
                $id = $query_arr['id'];
                break;
            case 2:
                $domain = '1688';
                $path = rtrim(parse_url($url)['path'], '.html');
                $arr = explode('/', $path);
                $id = end($arr);
                break;
            default:
                $domain = 'taobao';
        }

//        $url = "http://api.onebound.cn/" . $domain . "/api_call.php?num_iid={$id}&is_promotion=1&api_name=item_get&lang=zh-CN&key=QQ1597063760&secret";
        $url = "http://api.onebound.cn/" . $domain . "/api_call.php?num_iid={$id}&is_promotion=1&api_name=item_get&lang=zh-CN&key=tel15701584940&secret=20200426";
        $info = file_get_contents($url);
//        $info = file_get_contents('/www/test.json');
        $arr = json_decode($info, true);

        $goodsInfo = $arr['item'];
        if (empty($goodsInfo)) {
            var_dump($arr);die;
        }
        $isSpec = 0;
        if (!empty($goodsInfo['skus'])) {
            $skus = $goodsInfo['skus']['sku'];//库存
            if (count($skus) > 1) {
                $isSpec = 1;
            }
        }

        $data['isSpec'] = $isSpec;
        $shopId = 119; // 御泥坊 45  绝艺鸭脖 44 天然工坊 43 白又白 118 天门市小潮服装有限公司119
        $data['goodsName'] = $goodsName = $goodsInfo['title'];// 标题
        if (!empty($goodsInfo['props'])) {
            $props = $goodsInfo['props'];
            $data['goodsAttr'] = json_encode($props);
        }

        if ($this->urlParse($goodsInfo['pic_url'])) {
            $picUrl = $goodsInfo['pic_url'];
        } else {
            $picUrl = 'http:' . $goodsInfo['pic_url'];
        }
        $data['goodsImg'] = $goodsImg = $this->getSelfImg($picUrl);// 封面图
        $data['goodsDesc'] = $goodsDesc = $this->getDesc($goodsInfo['desc']);// 描述
        $data['goodsSn'] = $goodsSn = WSTGoodsNo();
        $data['productNo'] = $productNo = WSTGoodsNo();
        $data['productNo'] = $productNo = WSTGoodsNo();
        $data['goodsCatId'] = $goodsCatId = 365;
        $data['goodsStatus'] = 1;
        $data['goodsCatIdPath'] =  '365_';
        $data['goodsType'] = $goodsType = 0;
        $data['weight'] = $weight = 0;
        $data['marketPrice'] = $marketPrice = bcmul($goodsInfo['orginal_price'], 1.5, 2);
        $data['shopPrice'] = $shopPrice = bcmul($goodsInfo['price'], 1.5, 2);

       $img = $goodsInfo['item_imgs'];
       $gallery = '';
       foreach ($img as $v) {
           if ($this->urlParse($v['url'])) {
               $url = $v['url'];
           } else {
               $url = 'http:' . $v['url'];
           }
           $imgUrl = $this->getSelfImg($url);
           $gallery .= $imgUrl . ',';
       }
        $data['gallery'] = $gallery = rtrim($gallery, ','); // 图片
        $data['skus'] = $skus;
        $this->addTo($data, $shopId);
    }

    public function urlParse($url)
    {
        $preg = "/^http(s)?:\\/\\/.+/";
        if(preg_match($preg,$url))
        {
            return true;
        }else
        {
            return false;
        }
    }

    public function addTo($data, $shopId)
    {
        Db::startTrans();
        try {
            $data['goodsDesc'] = htmlspecialchars_decode($data['goodsDesc']);

            $goodsData = [];
            $goodsData['isSpec'] = $data['isSpec'];
            $goodsData['goodsAttr'] = $data['goodsAttr'];
            $goodsData['goodsSn'] = $data['goodsSn'];
            $goodsData['goodsUnit'] = '件';
            $goodsData['productNo'] = $data['productNo'];
            $goodsData['goodsName'] = $data['goodsName'];
            $goodsData['goodsImg'] = $data['goodsImg'];
            $goodsData['shopId'] = $shopId;
            $goodsData['marketPrice'] = $data['marketPrice'];
            $goodsData['shopPrice'] = $data['shopPrice'];
            $goodsData['goodsCatId'] = $data['goodsCatId'];
            $goodsData['shopCatId1'] = 0;
            $goodsData['shopCatId2'] = 0;
            $goodsData['goodsStock'] = 1000;
            $goodsData['goodsDesc'] = $data['goodsDesc'];
            $goodsData['gallery'] = $data['gallery'];
            $goodsData['saleTime'] = date('Y-m-d H:i:s');
            $goodsData['createTime'] = date('Y-m-d H:i:s');
            $goodsId = Db::name('goods')->insertGetId($goodsData);

            if ($goodsId) {
                //建立商品评分记录
                $gs = [];
                $gs['goodsId'] = $goodsId;
                $gs['shopId'] = $shopId;
                Db::name('goods_scores')->insert($gs);

                // 建立分类
                $specCats = [];
                $specCats['goodsCatId'] = 365;
                $specCats['goodsCatPath'] = '365_';

                $specItems = [];
                $specItems['shopId'] = $shopId;
                $specItems['goodsId'] = $goodsId;

                $goodsSpecs = [];
                $goodsSpecs['shopId'] = $shopId;
                $goodsSpecs['goodsId'] = $goodsId;
                $goodsSpecs['productNo'] = $data['productNo'];
                foreach ($data['skus'] as $k => $v) {
                    $catArr = explode(':', $v['properties_name']);
                    $catName = $catArr[2];
                    $itemName = $catArr[3];
                    $goodsSpecs['specPrice'] = $v['price'];
                    $goodsSpecs['marketPrice'] = $v['orginal_price'];
                    $isCat = Db::name('spec_cats')->where("catName = '{$catName}'")->find();
                    if (!empty($isCat)) {
                       $catId = $isCat['catId'];
                       $specItems['catId'] = $catId;
                    } else {
                        $specCats['catName'] = $catName;
                        $specCats['createTime'] = date('Y-m-d H:i:s');
                        $catId = Db::name('spec_cats')->insertGetId($specCats);
                        $specItems['catId'] = $catId;
                    }
                    $specItems['itemName'] = $itemName;
                    $specItems['itemImg'] = '';
                    $specItems['createTime'] = date('Y-m-d H:i:s');
                    $specItemsId = Db::name('spec_items')->insertGetId($specItems);
                    $goodsSpecs['specIds'] = $specItemsId;
                    $goodsSpecs['specStock'] = 1000;
                    if ($k == 0) {
                        $goodsSpecs['isDefault'] = 1;
                    } else {
                        $goodsSpecs['isDefault'] = 0;
                    }
                    Db::name('goods_specs')->insertGetId($goodsSpecs);
                }

                Db::commit();
                echo  'ok';
            } else {
                throw new Exception('增加失败');
            }
        } catch (\Exception $e) {
            Db::rollback();
            var_dump($e->getMessage());die;
        }
    }

    private function getSelfImg($img)
    {
        $file_path = $this->downloadWxImgToLocal($img);
        $ret = CosHelper::upload($file_path);
        return $ret['data']['url'];
    }

    //取得页面所有的图片地址
    function getimages($str)
    {
        $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern,$str,$match);
        return $match[1];
    }

    private function getDesc($desc)
    {
        $gif = "https://boardcast-1257835755.cos.ap-shanghai.myqcloud.com/ygzb202004081630225928.gif";
        $img = $this->getimages($desc);
        $imgData = [];
        foreach ($img as $v) {
            $hz = pathinfo($v);
            if ($hz['extension'] == 'gif') {
                continue;
            }
            if (empty($v)) {
                continue;
            }
            if ($this->urlParse($v)) {
                $url = $v;
            } else {
                $url = 'http:' . $v;
            }
            $selfImg = $this->getSelfImg($url);
            $imgData[] = ['old' => $v, 'new' => $selfImg];
        }
        foreach ($imgData as $v) {
            $desc = str_replace($v['old'], $v['new'], $desc);
        }
        $desc = str_replace('//assets.alicdn.com/kissy/1.0.0/build/imglazyload/spaceball.gif', $gif, $desc);
        return $desc;
    }

        //远程下载微信头像
    private function downloadWxImgToLocal($imgurl,$path = '/www/static/share-image/')
    {
        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate',);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $imgurl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
//        if ($code == 200) {
//            //把URL格式的图片转成base64_encode格式的！
//            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
//        }
        //把URL格式的图片转成base64_encode格式的！
        $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
        $img_content = $imgBase64Code;//图片内容
        //echo $img_content;exit;

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_content, $result)) {
            $type = $result[2];//得到图片类型png?jpg?gif?
            $filename = $path . time().".{$type}";
            if (file_put_contents($filename, base64_decode(str_replace($result[1], '', $img_content)))){
                return $filename;
            }
        }
    }

    /**
     * 新增商品
     * @int $sId shop_id
     */
    public function add($sId = 0)
    {
        $user_id = (int)input('post.user_id', 0); //直播用户id
        $shopId = ($sId == 0) ? (int)session('WST_USER.shopId') : $sId;
        $data = input('post.');
        if(empty($data['isSale'])){
            $data['isSale'] = 1;
        }
        if(empty($data['goodsCatId'])){
            $data['goodsCatId'] = 365; //商品分类给默认值
        }
        if(empty($data['goodsType'])){
            $data['goodsType'] = 0; //商品类型 0：实物商品，1:虚拟商品
        }
        if(empty($data['isFreeShipping'])){
            $data['isFreeShipping'] = 0; //是否包邮 1:包邮，0:买家承担运费
        }
        if(empty($data['shopExpressId'])){
            $data['shopExpressId'] = 6; //顺丰速递
        }
        if(empty($data['weight'])){
            $data['weight'] = 0; //权重默认为零
        }
        $isApp = (int)input('post.isApp', 0);
        $specsIds = input('post.specsIds');
        WSTUnset($data, 'goodsId,statusRemarks,goodsStatus,dataFlag');

        if (WSTConf("CONF.isGoodsVerify") == 1) { //商品是否需要审核
            $data['goodsStatus'] = 0;
        } else {
            $data['goodsStatus'] = 1;
        }
        if (isset($data['goodsName'])) {
            if (!WSTCheckFilterWords($data['goodsName'], WSTConf("CONF.limitWords"))) {
                return WSTReturn("商品名称包含非法字符");
            }
            if (!WSTCheckFilterWords($data['goodsName'], WSTConf("CONF.sensitiveWords"))) {
                $data['goodsStatus'] = 0;
            }
        }
        if (isset($data['goodsTips'])) {
            if (!WSTCheckFilterWords($data['goodsTips'], WSTConf("CONF.limitWords"))) {
                return WSTReturn("商品促销信息包含非法字符");
            }
            if (!WSTCheckFilterWords($data['goodsTips'], WSTConf("CONF.sensitiveWords"))) {
                $data['goodsStatus'] = 0;
            }
        }
        if ($data['isSale'] == 1 && $data['goodsImg'] == '') {
            return WSTReturn("上架商品必须有商品图片");
        }
        if (isset($data['goodsDesc'])) {
            if (!WSTCheckFilterWords($data['goodsDesc'], WSTConf("CONF.limitWords"))) {
                return WSTReturn("商品描述包含非法字符");
            }
            if (!WSTCheckFilterWords($data['goodsDesc'], WSTConf("CONF.sensitiveWords"))) {
                $data['goodsStatus'] = 0;
            }
        }
        if (isset($data['goodsSeoKeywords'])) {
            if (!WSTCheckFilterWords($data['goodsSeoKeywords'], WSTConf("CONF.sensitiveWords"))) {
                $data['goodsStatus'] = 0;
            }
        }
        if (isset($data['goodsSeoDesc'])) {
            if (!WSTCheckFilterWords($data['goodsSeoDesc'], WSTConf("CONF.sensitiveWords"))) {
                $data['goodsStatus'] = 0;
            }
        }
        if ((int)$data['goodsType'] == 0 && (int)$data['isFreeShipping'] == 0 && (int)$data['shopExpressId'] == 0) {
            return WSTReturn("请选择快递公司");
        }
        $data['shopId'] = $shopId;
        $data['saleTime'] = date('Y-m-d H:i:s');
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['wechat']=input('post.wechat');
        $data['weight']=input('post.weight');
        //$goodmodel = model('GoodsCats');
        $goodmodel = new GoodsCats();

        $goodsCats = $goodmodel->getParentIs($data['goodsCatId']);

        //校验商品分类有效性
        $applyCatIds = $goodmodel->getShopApplyGoodsCats($shopId);
        $isApplyCatIds = array_intersect($applyCatIds, $goodsCats);
        if (empty($isApplyCatIds)) {
            return WSTReturn("请选择完整商城分类");
        }
        $data['goodsCatIdPath'] = implode('_', $goodsCats) . "_";
        if ($data['goodsType'] == 0) {
            $data['isSpec'] = ($specsIds != '') ? 1 : 0;
        } else {
            $data['isSpec'] = 0;
        }
        Db::startTrans();
        try {
            //对图片域名进行处理
            $resourceDomain = ($isApp == 1) ? WSTConf('CONF.resourceDomain') : WSTConf('CONF.resourcePath');
            $data['goodsDesc'] = htmlspecialchars_decode($data['goodsDesc']);
            $data['goodsDesc'] = WSTRichEditorFilter($data['goodsDesc']);
            $data['goodsDesc'] = str_replace($resourceDomain . '/upload/', '${DOMAIN}/upload/', $data['goodsDesc']);
            //保存插件数据钩子
            hook('beforeEidtGoods', ['data' => &$data]);
            $shop = model('shops')->get(['shopId' => $shopId]);
            if ($shop['dataFlag'] == -1 || $shop['shopStatus'] != 1){
                $data['isSale'] = 0;
            }

            $validate = new Validate;
            if (!$validate->scene(true)->check($data)) {
                return WSTReturn($validate->getError());
            } else {
                $result = $this->allowField(true)->save($data);
            }

            if (false !== $result) {
                $goodsId = $this->goodsId;
                //建立商品评分记录
                $gs = [];
                $gs['goodsId'] = $goodsId;
                $gs['shopId'] = $shopId;
                Db::name('goods_scores')->insert($gs);

                //如果是实物商品并且有销售规格则保存销售和规格值
                if ($data['goodsType'] == 0 && $specsIds != '') {
                    $specsIds = explode(',', $specsIds);
                    $specsArray = [];
                    foreach ($specsIds as $v) {
                        $vs = explode('-', $v);
                        foreach ($vs as $vv) {
                            if (!in_array($vv, $specsArray)) {
                                $specsArray[] = $vv;
                            }
                        }
                    }
                    //保存规格名称
                    $specMap = [];
                    foreach ($specsArray as $v) {
                        $vv = explode('_', $v);
                        $sitem = [];
                        $sitem['shopId'] = $shopId;
                        $sitem['catId'] = (int)$vv[0];
                        $sitem['goodsId'] = $goodsId;
                        $sitem['itemName'] = input('post.specName_' . $vv[0] . "_" . $vv[1]);
                        $sitem['itemImg'] = input('post.specImg_' . $vv[0] . "_" . $vv[1], '');
                        $sitem['dataFlag'] = 1;
                        $sitem['createTime'] = date('Y-m-d H:i:s');

                        $itemId = Db::name('spec_items')->insertGetId($sitem);

                        if ($sitem['itemImg'] != '') {
                            WSTUseResource(0, $itemId, $sitem['itemImg']);
                        }
                        $specMap[$v] = $itemId;
                    }
                    //保存销售规格
                    $defaultPrice = 0;//最低价
                    $totalStock = 0;//总库存
                    $gspecArray = [];
                    $isFindDefaultSpec = false;
                    $defaultSpec = Input('post.defaultSpec');
                    foreach ($specsIds as $v) {
                        $vs = explode('-', $v);
                        $goodsSpecIds = [];
                        foreach ($vs as $gvs) {
                            $goodsSpecIds[] = $specMap[$gvs];
                        }
                        $gspec = [];
                        $gspec['specIds'] = implode(':', $goodsSpecIds);
                        $gspec['shopId'] = $shopId;
                        $gspec['goodsId'] = $goodsId;
                        $gspec['productNo'] = Input('productNo_' . $v);
                        $gspec['marketPrice'] = (float)Input('marketPrice_' . $v);
                        $gspec['specPrice'] = (float)Input('specPrice_' . $v);
                        $gspec['specStock'] = (int)Input('specStock_' . $v);
                        $gspec['warnStock'] = (int)Input('warnStock_' . $v);
                        $gspec['specWeight'] = (float)Input('specWeight_' . $v);
                        $gspec['specVolume'] = (float)Input('specVolume_' . $v);
                        //设置默认规格
                        if ($defaultSpec == $v) {
                            $isFindDefaultSpec = true;
                            $defaultPrice = $gspec['specPrice'];
                            $gspec['isDefault'] = 1;
                        } else {
                            $gspec['isDefault'] = 0;
                        }
                        $gspecArray[] = $gspec;
                        //获取总库存
                        $totalStock = $totalStock + $gspec['specStock'];
                    }
                    if (!$isFindDefaultSpec) {
                        return WSTReturn("请选择推荐规格");
                    }
                    if (count($gspecArray) > 0) {
                        Db::name('goods_specs')->insertAll($gspecArray);
                        //更新默认价格和总库存
                        $this->where('goodsId', $goodsId)->update(['isSpec' => 1, 'shopPrice' => $defaultPrice, 'goodsStock' => $totalStock]);
                    }
                }

                //保存商品属性
                $attrsArray = [];
                $attrRs = Db::name('attributes')->where([['goodsCatId', 'in', $goodsCats], ['isShow', '=', 1], ['dataFlag', '=', 1]])
                    ->field('attrId')->select();

                foreach ($attrRs as $key => $v) {
                    $attrs = [];
                    $attrs['attrVal'] = input('attr_' . $v['attrId']); //属性值
                    if ($attrs['attrVal'] == '') {
                        continue;
                    }
                    $attrs['shopId'] = $shopId;
                    $attrs['goodsId'] = $goodsId;
                    $attrs['attrId'] = $v['attrId'];
                    $attrs['createTime'] = date('Y-m-d H:i:s');
                    $attrsArray[] = $attrs;
                }
                if (count($attrsArray) > 0) {
                    Db::name('goods_attributes')->insertAll($attrsArray);
                }
                //保存关键字
                $searchKeys = WSTGroupGoodsSearchKey($goodsId);
                $this->where('goodsId', $goodsId)->update(['goodsSerachKeywords' => implode(',', $searchKeys)]);

                hook('afterEditGoods', ['goodsId' => $goodsId]);
                Db::commit();
                return WSTReturn("新增成功", 1, ['id' => $goodsId]);
            } else {
                return WSTReturn($this->getError(), -1);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('新增失败' . $e->getMessage() . $e->getLine(). $this->getLastSql(), -1);
        }
    }

    function curl_get($url)
    {
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 超时设置,以秒为单位
        curl_setopt($curl, CURLOPT_TIMEOUT, 1);

        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

        // 设置请求头
//        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //执行命令
        $data = curl_exec($curl);

        // 显示错误信息
        if (curl_error($curl)) {
            print "Error: " . curl_error($curl);
        } else {
            // 打印返回的内容
            curl_close($curl);
        }
        return $data;
    }
}
