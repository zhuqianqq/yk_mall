<?php
namespace wstmart\common\model;

use think\Db;
use think\Model;

class TProductRecommend extends ApiBaseModel
{
    protected $table = "t_product_recommend";

    /**
     * 获取推荐商品
     * @param int $user_id 主播用户id
     * @return int
     */
    public static function getProductRec($user_id)
    {

        $product_ids = self::where(["user_id"=>$user_id])->order('id asc')->column('product_id');

        return $product_ids ? $product_ids : []; 
    }
}
