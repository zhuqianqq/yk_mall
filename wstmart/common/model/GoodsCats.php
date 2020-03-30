<?php
namespace wstmart\common\model;
use think\Db;
/**
 * 商品分类类
 */
class GoodsCats extends Base
{
    protected $pk = 'catId';

    /**
     * 获取列表
     */
    public function listQuery($parentId, $isFloor = -1)
    {
        $dbo = $this->where(['dataFlag' => 1, 'isShow' => 1, 'parentId' => $parentId]);
        if ($isFloor != -1) $dbo->where('isFloor', $isFloor);
        return $dbo->order('catSort asc')->select();
    }


    /**
     * 获取店铺申请的商品分类
     */
    public function getShopApplyGoodsCats($shopId)
    {
        $ids = Db::name('cat_shops')->where(['shopId' => $shopId])->column('catId');
        return $this->getChildIds($ids);
    }

    /**
     * 根据父分类获取其下所有子分类[包括自己]
     */
    public function getChildIds($ids, $data = [])
    {
        $data = array_merge($data, $ids);
        $rs = $this->where([['dataFlag', '=', 1], ['isShow', '=', 1], ['parentId', 'in', $ids]])->column('catId');
        if (count($rs) > 0) {
            return $this->getChildIds($rs, $data);
        } else {
            return $data;
        }
    }

    /**
     * 根据子分类获取其父级分类
     */
    public function getParentIs($id, $data = array())
    {
        $data[] = $id;
        $parentId = $this->where('catId', $id)->value('parentId');
        if ($parentId == 0) {
            krsort($data);
            return $data;
        } else {
            return $this->getParentIs($parentId, $data);
        }
    }

    public function getParentNames($id)
    {
        if ($id <= 0) return [];
        $ids = $this->getParentIs($id);
        $rs = Db::name('goodsCats')->where([['catId', 'in', $ids]])->field('catName')->order('catId desc')->select();
        $names = [];
        foreach ($rs as $v) {
            $names[] = $v['catName'];
        }
        return $names;
    }

    /**
     * 获取首页楼层
     */
    public function getFloors()
    {
        $cats1 = Db::name('goods_cats')->where([['dataFlag', '=', 1], ['isShow', '=', 1], ['parentId', '=', 0], ['isFloor', '=', 1]])
            ->field("catName,catId,subTitle")->order('catSort')->limit(10)->select();
        if (!empty($cats1)) {
            $ids = [];
            foreach ($cats1 as $key => $v) {
                $ids[] = $v['catId'];
            }
            $cats2 = [];
            $rs = Db::name('goods_cats')->where([['dataFlag', '=', 1], ['isShow', '=', 1], ['parentId', 'in', $ids], ['isFloor', '=', 1]])
                ->field("parentId,catName,catId,subTitle")->order('catSort asc')->select();
            foreach ($rs as $key => $v) {
                $cats2[$v['parentId']][] = $v;
            }
            foreach ($cats1 as $key => $v) {
                $cats1[$key]['children'] = (isset($cats2[$v['catId']])) ? $cats2[$v['catId']] : [];
            }
        }
        return $cats1;
    }

    /**
     * 列表
     */
    public function getGoodsCats()
    {
        $list = cache('WST_CACHE_GOODS_CAT_MOB');
        if (!$list) {
            //查询一级分类
            $trs1s = $this->where(["dataFlag" => 1, "isShow" => 1, "parentId" => 0])->field('catId,parentId,simpleName')->order('catSort asc')->select();
            $trs1 = array();
            $list = array();
            $rs2 = array();
            $maprs = array();
            $ids = array();
            foreach ($trs1s as $key => $v) {
                $trs1[$key]['catId'] = $v['catId'];
                $trs1[$key]['parentId'] = $v['parentId'];
                $trs1[$key]['catName'] = $v['simpleName'];
                $ids[] = $v['catId'];
            }
            $ids[] = -1;
            //查询二级分类
            $trs2s = $this->where("dataFlag=1 and isShow=1 and parentId in(" . implode(',', $ids) . ")")->field('catId,parentId,catName')->order('catSort asc')->select();
            $trs2 = array();
            $ids = array();
            foreach ($trs2s as $key => $v) {
                $trs2[$key]['catId'] = $v['catId'];
                $trs2[$key]['parentId'] = $v['parentId'];
                $trs2[$key]['catName'] = $v['catName'];
            }
            foreach ($trs2 as $v2) {
                $ids[] = $v2['catId'];
                $maprs[$v2['parentId']][] = $v2;
            }
            $ids[] = -1;
            //查询三级分类
            $trs3s = $this->where("dataFlag=1 and isShow=1 and parentId in(" . implode(',', $ids) . ")")->field('catId,parentId,catName,catImg')->order('catSort asc')->select();
            $trs3 = array();
            $ids = array();
            foreach ($trs3s as $key => $v) {
                $trs3[$key]['catId'] = $v['catId'];
                $trs3[$key]['parentId'] = $v['parentId'];
                $trs3[$key]['catName'] = $v['catName'];
                $trs3[$key]['catImg'] = strval(WSTImg($v['catImg'], 3, 'goodsLogo'));
            }
            foreach ($trs3 as $v2) {
                $maprs[$v2['parentId']][] = $v2;
            }
            //倒序建立樹形
            foreach ($trs2 as $v2) {
                $v2['childList'] = [];
                if (isset($maprs[$v2['catId']])) $v2['childList'] = $maprs[$v2['catId']];
                $rs2[] = $v2;
            }
            foreach ($trs1 as $v2) {
                foreach ($rs2 as $vv2) {
                    if ($vv2['parentId'] == $v2['catId']) {
                        $v2['childList'][] = $vv2;
                    }
                }
                $list[] = $v2;
            }
            cache('WST_CACHE_GOODS_CAT_MOB', $list, 86400);
        }
        return $list;
    }
}
