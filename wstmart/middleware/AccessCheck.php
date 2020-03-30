<?php
/**
 * AccessKey 中间件
 */
namespace wstmart\middleware;

use util\AccessKeyHelper;
use util\Tools;
use wstmart\common\model\TUserMap;

class AccessCheck
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $user_id = intval($request->header('user-id') ?? $request->param('user_id')); //主播用户id
        $access_key = $request->header('access-key','');

        if($user_id <= 0 || empty($access_key)){
            return json(Tools::outJson(9001,"缺少access-key和user-id请求头"));
        }

        $check = AccessKeyHelper::validateAccessKey($user_id,$access_key);
        if(!$check){
            return json(Tools::outJson(9002,"access-key无效，请重新登录"));
        }

        $mall_user_id = TUserMap::getMallUserId($user_id);
        if($mall_user_id <= 0){
            return json(Tools::outJson(9003,"未找到商城用户id"));
        }

        $request->user_id = $user_id;  //主播用户id
        $request->mall_user_id = $mall_user_id; //商城用户id

        return $next($request);
    }
}
