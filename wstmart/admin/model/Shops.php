<?php

namespace wstmart\admin\model;

use think\Db;
use wstmart\admin\validate\HomeShopBase as VShopBase;
use think\Loader;

/**
 * 店铺业务处理
 */
class Shops extends Base
{
    protected $pk = 'shopId';

    /**
     * 分页
     */
    public function pageQuery($shopStatus = 1)
    {
        $areaIdPath = input('areaIdPath');
        $shopName = input('shopName');
        $isInvestment = (int)input('isInvestment/d', -1);
        $where = [];
        $where[] = ['s.dataFlag', '=', 1];
        $where[] = ['s.applyStatus', '=', 2];
        $where[] = ['s.shopStatus', '=', $shopStatus];
        if (in_array($isInvestment, [0, 1])) $where[] = ['ss.isInvestment', '=', $isInvestment];
        if ($shopName != '') $where[] = ['shopName', 'like', '%' . $shopName . '%'];
        if ($areaIdPath != '') $where[] = ['areaIdPath', 'like', $areaIdPath . "%"];
        $sort = input('sort');
        $order = [];
        if ($sort != '') {
            $sortArr = explode('.', $sort);
            $order = 's.isIndex desc ,s.sort asc ,';
            $order .= $sortArr[0] . ' ' . $sortArr[1];
        }
        return Db::table('__SHOPS__')->alias('s')->join('__AREAS__ a2', 's.areaId=a2.areaId', 'left')
            ->join('__USERS__ u', 'u.userId=s.userId', 'left')
            ->join('__SHOP_EXTRAS__ ss', 's.shopId=ss.shopId', 'left')
            ->where($where)
            ->field('u.loginName,s.shopId,shopSn,shopName,a2.areaName,shopkeeper,telephone,shopAddress,shopCompany,shopAtive,shopStatus,s.isIndex')
            ->order($order)
            ->paginate(input('limit/d'));
    }

    /**
     * 分页
     */
    public function pageQueryByApply()
    {
        $areaIdPath = input('areaIdPath');
        $shopName = input('shopName');
        $isInvestment = (int)input('isInvestment/d', -1);
        $isApply = (int)input('isApply', -1);
        $where = [];
        $where[] = ['s.dataFlag', '=', 1];
        $where[] = ['s.applyStatus', 'in', [-1, 0, 1]];
        if ($isApply == 1) $where[] = ['s.applyStatus', '=', 1];
        if ($isApply == 0) $where[] = ['s.applyStatus', 'in', [-1, 0]];
        if (in_array($isInvestment, [0, 1])) $where[] = ['ss.isInvestment', '=', $isInvestment];
        if ($shopName != '') $where[] = ['shopName', 'like', '%' . $shopName . '%'];
        if ($areaIdPath != '') $where[] = ['areaIdPath', 'like', $areaIdPath . "%"];
        return Db::table('__SHOPS__')->alias('s')->join('__AREAS__ a2', 's.areaId=a2.areaId', 'left')
            ->join('__SHOP_EXTRAS__ ss', 's.shopId=ss.shopId', 'left')
            ->join('__USERS__ u', 'u.userId=s.userId', 'left')
            ->where($where)
            ->field('u.loginName,s.shopId,applyLinkMan,applyLinkTel,investmentStaff,isInvestment,shopName,a2.areaName,shopAddress,shopCompany,applyTime,applyStatus')
            ->order('s.shopId desc')->paginate(input('limit/d'));
    }

    /**
     * 删除
     */
    public function del()
    {
        $id = input('post.id/d');
        if ($id == 1) return WSTReturn('无法删除自营店铺');
        Db::startTrans();
        try {
            $shop = $this->get($id);
            $shop->dataFlag = -1;
            $result = $shop->save();
            WSTUnuseResource('shops', 'shopImg', $id);
            // 店铺申请表的图片标记为删除
            $imgArr = model('shopExtras')->field('legalCertificateImg,businessLicenceImg,bankAccountPermitImg,organizationCodeImg,taxRegistrationCertificateImg,taxpayerQualificationImg')->where(['shopId' => $id])->find();
            WSTUnuseResource($imgArr->getData());
            if (false !== $result) {
                //删除推荐店铺
                Db::name('recommends')->where(['dataSrc' => 1, 'dataId' => $id])->delete();
                //删除店铺与商品分类的关系
                Db::name('cat_shops')->where(['shopId' => $id])->delete();
                //删除用户店铺身份
                Db::name('users')->where(['userId' => $shop->userId])->update(['dataFlag' => -1]);
                //删除店铺角色
                Db::name('shop_roles')->where(['shopId' => $id])->update(['dataFlag' => -1]);
                //删除店铺职员
                Db::name('shop_users')->where(['shopId' => $id])->update(['dataFlag' => -1]);
                //下架及下架商品
                model('goods')->delByshopId($id);
                //删除店铺钩子事件
                hook('afterChangeShopStatus', ['shopId' => $id]);
                Db::commit();
                return WSTReturn("删除成功", 1);
            }
        } catch (\Exception $e) {
            Db::rollback();
        }
        return WSTReturn('删除失败', -1);
    }

    /**
     * 根据根据userId删除店铺
     */
    public function delByUserId($userId)
    {
        if ($userId == 1) return WSTReturn('无法删除自营店铺');
        $shop = $this->where('userId', $userId)->find();
        if (!$shop) return;
        $shop->dataFlag = -1;
        $result = $shop->save();
        WSTUnuseResource('shops', 'shopImg', $shop->shopId);
        if (false !== $result) {
            //删除推荐店铺
            Db::name('recommends')->where(['dataSrc' => 1, 'dataId' => $shop->shopId])->delete();
            //删除店铺与商品分类的关系
            Db::name('cat_shops')->where(['shopId' => $shop->shopId])->delete();
            //下架及删除商品
            model('goods')->delByshopId($shop->shopId);
            //删除店铺角色
            Db::name('shop_roles')->where(['shopId' => $shop->shopId])->update(['dataFlag' => -1]);
            //删除店铺职员
            Db::name('shop_users')->where(['shopId' => $shop->shopId])->update(['dataFlag' => -1]);
            //删除店铺钩子事件
            hook('afterChangeShopStatus', ['shopId' => $shop->shopId]);
            return WSTReturn("删除成功", 1);
        }
        return WSTReturn('删除失败', -1);
    }

    /**
     * 获取商家入驻资料
     */
    public function getById($id)
    {
        $shop = $this->alias('s')->join('__SHOP_EXTRAS__ ss', 's.shopId=ss.shopId', 'inner')
            ->join('__USERS__ u', 'u.userId=s.userId', 'inner')
            ->where('s.shopId', $id)
            ->find()
            ->toArray();
        //获取认证类型
        $shopAccreds = Db::name('shop_accreds')->where('shopId', $id)->select();
        $shop['accreds'] = [];
        foreach ($shopAccreds as $v) {
            $shop['accreds'][$v['accredId']] = true;
        }
        //获取经营范围
        $goodscats = Db::name('cat_shops')->where('shopId', $id)->select();
        $shop['catshops'] = [];
        foreach ($goodscats as $v) {
            $shop['catshops'][$v['catId']] = true;
        }
        return $shop;
    }

    /**
     * 生成店铺编号
     * @param $key 编号前缀,要控制不要超过int总长度，最好是一两个字母
     */
    public function getShopSn($key = '')
    {
        $rs = $this->max(Db::raw("REPLACE(shopSn,'S','') + ''"));
        if ($rs == '') {
            return $key . '000000001';
        } else {
            for ($i = 0; $i < 1000; $i++) {
                $num = (int)str_replace($key, '', $rs);
                $shopSn = $key . sprintf("%09d", ($num + 1));
                $ischeck = $this->checkShopSn($shopSn);
                if (!$ischeck){
                    return $shopSn;
                }
            }
            return '';//一直都检测到那就不要强行添加了
        }
    }

    /**
     * 检测店铺编号是否存在
     */
    public function checkShopSn($shopSn, $shopId = 0)
    {
        $dbo = $this->where(['shopSn' => $shopSn, 'dataFlag' => 1]);
        if ($shopId > 0) {
            $dbo->where('shopId', '<>', $shopId);
        }
        $num = $dbo->Count();
        if ($num == 0) {
            return false;
        }
        return true;
    }

    /**
     * 处理申请
     */
    public function handleApply()
    {
        $data = input('post.');
        $shopId = (int)$data['shopId'];
        $shops = $this->get($shopId);
        if (empty($shops)) {
            return WSTReturn('操作失败，该入驻申请不存在');
        }
        if ($shops->applyStatus == 2) {
            return WSTReturn('该入驻申请已通过', 1);
        }
        //新增入驻申请
        //先遍历前台传来的data,根据shop_base表判断是属于shops表还是shop_extras表，分别用两个数组保存
        $shopsData = [];
        $shopExtrasData = [];
        // 保存上传图片的路径，用来启用上传图片
        $uploadShopsImgPath = [];
        $uploadShopExtrasImgPath = [];
        $unsetField = [];
        $goodsCats = [];
        foreach ($data as $k => $v) {
            $field = Db::name('shop_bases')->where(['fieldName' => $k, 'dataFlag' => 1])->field('fieldName,fieldType,fieldAttr,isShopsTable,dateRelevance,isShow,isRequire')->find();
            if ($field['isShopsTable'] == 1) {
                // 属于shops表
                $shopsData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaIds = model('Areas')->getParentIs($shopsData[$k]);
                    if (!empty($areaIds)) $shopsData[$k] = implode('_', $areaIds) . "_";
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopsImgPath[] = $data[$k];
                }
            } else {
                // 属于shop_extras表
                $shopExtrasData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaIds = model('Areas')->getParentIs($shopExtrasData[$k]);
                    if (!empty($areaIds)) {
                        $shopExtrasData[$k] = implode('_', $areaIds) . "_";
                    }
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopExtrasImgPath[] = $data[$k];
                }
                // 日期字段入库前处理
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'date') {
                    // 当日期字段不是必填项，需删除该字段
                    if ($field['isRequire'] == 0) {
                        $unsetField[] = $field['fieldName'];
                    }
                    if ($field['dateRelevance']) {
                        $dateRelevance = explode(',', $field['dateRelevance']);
                        // 如果选择了长期，就删除字段的结束日期
                        if ($data[$dateRelevance[1]] == 1) {
                            $unsetField[] = $dateRelevance[0];
                        }
                    }
                }
                //经营范围
                if (!empty($data['goodsCatIds'])) {
                    $goodsCats = explode(',', $data['goodsCatIds']);
                }
            }
        }
        // 删除无需入库的字段
        foreach ($shopExtrasData as $k => $v) {
            if (in_array($k, $unsetField)) {
                unset($shopExtrasData[$k]);
            }
        }

        $validate = new VShopBase();
        $validate->setRuleAndMessage($shopsData);
        $validate->setRuleAndMessage($shopExtrasData);

        //判断经营范围
        $goodsCatIds = input('post.goodsCatIds');
        $accredIds = input('post.accredIds');
        if ($goodsCatIds == '') {
            return WSTReturn('请选择经营范围');
        }

        $data['applyStatus'] = ($data['applyStatus'] == 2) ? 2 : -1; //2：审核通过，-1：审核不通过
        if ($data['applyStatus'] != 2 && $data['applyDesc'] == '') {
            return WSTReturn('请输入审核不通过原因');
        }

        Db::startTrans();
        try {
            //保存店铺基础信息
            $shopsData['shopId'] = $shopId;
            $shopsData['applyStatus'] = $data['applyStatus'];
            $shopsData['applyDesc'] = $data['applyDesc'];
            //检测店铺编号是否存在
            if ($data['shopSn'] == '') {
                $shopsData['shopSn'] = $this->getShopSn('S');
            } else {
                if (!$this->checkShopSn($data['shopSn'], $shopId)) {
                    $shopsData['shopSn'] = $data['shopSn'];
                } else {
                    return WSTReturn('该店铺编号已存在');
                }
            }
            $shopExtrasData['shopId'] = $shopId;

            if (!$validate->scene('add')->check($data)) {
                return WSTReturn($validate->getError());
            }

            WSTUnset($data, 'id,shopId,userId,dataFlag,createTime,goodsCatIds,accredIds,isSelf');
            if ($data['applyStatus'] == 2 && $data['shopSn'] == '') {
                $shopsData['shopSn'] = $this->getShopSn('S');
            }
            $this->allowField(true)->save($shopsData, ['shopId' => $shopId]);
            foreach ($uploadShopsImgPath as $k => $v) {
                //启用上传图片
                WSTUseResource(0, $this->shopId, $v, 'shops');
            }
            //更改用户身份
            if ($data['applyStatus'] == 2) {
                Db::name('users')->where('userId', $shops->userId)->update(['userType' => 1]);
            }
            $seModel = model('ShopExtras');
            $seModel->allowField(true)->save($shopExtrasData, ['shopId' => $shopId]);
            $extraId = $seModel->where(['shopId' => $shopId])->value('id');// 获取主键
            foreach ($uploadShopExtrasImgPath as $k => $v) {
                //启用上传图片
                WSTUseResource(0, $extraId, $v, 'shopextras');
            }

            //经营范围
            Db::name('cat_shops')->where('shopId', '=', $shopId)->delete();
            $goodsCats = explode(',', $goodsCatIds);
            foreach ($goodsCats as $key => $v) {
                if ((int)$v > 0) {
                    Db::name('cat_shops')->insert(['shopId' => $shopId, 'catId' => $v]);
                }
            }
            //认证类型
            Db::name('shop_accreds')->where('shopId', '=', $shopId)->delete();
            if ($accredIds != '') {
                $accreds = explode(',', $accredIds);
                foreach ($accreds as $key => $v) {
                    if ((int)$v > 0) {
                        Db::name('shop_accreds')->insert(['shopId' => $shopId, 'accredId' => $v]);
                    }
                }
            }
            if ($data['applyStatus'] == 2) {
                //建立店铺配置信息
                $sc = [];
                $sc['shopId'] = $shopId;
                Db::name('ShopConfigs')->insert($sc);
                $su = [];
                $su["shopId"] = $shopId;
                $su["userId"] = $shops->userId;
                $su["roleId"] = 0;
                Db::name('shop_users')->insert($su);
                //建立店铺评分记录
                $ss = [];
                $ss['shopId'] = $shopId;
                Db::name('shop_scores')->insert($ss);
            }

            if ($shops->applyStatus != $data['applyStatus']) {
                //发送消息
                $this->sendMessages($shopId, $shops->userId, $data, 'handleApply');
            }
            Db::commit();
            return WSTReturn("操作成功", 1);
        } catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('操作失败' . $e, -1);
        }
    }

    /**
     * 发送信息
     */
    public function sendMessages($shopId, $userId, $data, $method)
    {
        $user = model('users')->get($userId);
        $shops = model('shops')->get($shopId);
        if ((int)$data['applyStatus'] == 2) {
            //如果存在手机则发送手机号码提示
            $tpl = WSTMsgTemplates('PHONE_USER_SHOP_OPEN_SUCCESS');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1' && $data['applyLinkTel'] != '') {
                $params = ['tpl' => $tpl, 'params' => ['MALL_NAME' => WSTConf("CONF.mallName"), 'LOGIN_NAME' => $user->loginName]];
                $rv = model('admin/LogSms')->sendSMS(0, $userId, $data['applyLinkTel'], $params, $method);
            }
            //发送邮件
            $tpl = WSTMsgTemplates('EMAIL_USER_SHOP_OPEN_SUCCESS');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1' && $data['applyLinkEmail']) {
                $find = ['${LOGIN_NAME}', '${MALL_NAME}'];
                $replace = [$user->loginName, WSTConf("CONF.mallName")];
                $sendRs = WSTSendMail($data['applyLinkEmail'], '申请入驻审核通过', str_replace($find, $replace, $tpl['content']));
            }
            // 会员发送一条商城消息
            $tpl = WSTMsgTemplates('SHOP_OPEN_SUCCESS');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1') {
                $find = ['${LOGIN_NAME}', '${MALL_NAME}'];
                $replace = [$user->loginName, WSTConf("CONF.mallName")];
                WSTSendMsg($userId, str_replace($find, $replace, $tpl['tplContent']), ['from' => 0, 'dataId' => $shopId]);
            }
            //微信消息
            if ((int)WSTConf('CONF.wxenabled') == 1) {
                $params = [];
                $params['SHOP_NAME'] = $shops['shopName'];
                $params['APPLY_TIME'] = $shops['applyTime'];
                $params['NOW_TIME'] = date('Y-m-d H:i:s');
                $params['REASON'] = "申请入驻成功";
                WSTWxMessage(['CODE' => 'WX_SHOP_OPEN_SUCCESS', 'userId' => $userId, 'params' => $params]);
            }
        } else {
            //如果存在手机则发送手机号码提示
            $tpl = WSTMsgTemplates('PHONE_SHOP_OPEN_FAIL');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1' && $data['applyLinkTel'] != '') {
                $params = ['tpl' => $tpl, 'params' => ['MALL_NAME' => WSTConf("CONF.mallName"), 'REASON' => $data['applyDesc']]];
                $rv = model('admin/LogSms')->sendSMS(0, $userId, $data['applyLinkTel'], $params, $method);
            }
            //发送邮件
            $tpl = WSTMsgTemplates('EMAIL_SHOP_OPEN_FAIL');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1' && $data['applyLinkEmail']) {
                $find = ['${LOGIN_NAME}', '${MALL_NAME}', '${REASON}'];
                $replace = [$user->loginName, WSTConf("CONF.mallName"), $data['applyDesc']];
                $sendRs = WSTSendMail($data['applyLinkEmail'], '申请入驻失败', str_replace($find, $replace, $tpl['content']));
            }
            // 会员发送一条商城消息
            $tpl = WSTMsgTemplates('SHOP_OPEN_FAIL');
            if ($tpl['tplContent'] != '' && $tpl['status'] == '1') {
                $find = ['${LOGIN_NAME}', '${MALL_NAME}', '${REASON}'];
                $replace = [$user->loginName, WSTConf("CONF.mallName"), $data['applyDesc']];
                WSTSendMsg($userId, str_replace($find, $replace, $tpl['tplContent']), ['from' => 0, 'dataId' => $shopId]);
            }
            //微信消息
            if ((int)WSTConf('CONF.wxenabled') == 1) {
                $params = [];
                $params['SHOP_NAME'] = $shops['shopName'];
                $params['APPLY_TIME'] = $shops['applyTime'];
                $params['NOW_TIME'] = date('Y-m-d H:i:s');
                $params['REASON'] = $data['applyDesc'];
                WSTWxMessage(['CODE' => 'WX_SHOP_OPEN_FAIL', 'userId' => $userId, 'params' => $params]);
            }
        }
    }

    /**
     * 删除申请
     */
    public function delApply()
    {
        $id = input('post.id/d');
        $shop = $this->get($id);
        if ($shop->applyStatus == 2) return WSTReturn('通过申请的店铺不允许删除');
        Db::startTrans();
        try {
            //删除店铺信息
            Db::name('cat_shops')->where(['shopId' => $id])->delete();
            Db::name('shop_extras')->where(['shopId' => $id])->delete();
            Db::name('shops')->where(['shopId' => $id])->delete();
            WSTUnuseResource('shops', 'shopImg', $id);
            Db::commit();
            return WSTReturn("删除成功", 1);
        } catch (\Exception $e) {
            Db::rollback();
        }
        return WSTReturn('删除失败', -1);
    }

    /**
     * 新增
     */
    public function add()
    {
        $data = input('post.');
        //新增入驻申请
        // 先遍历前台传来的data,根据shop_base表判断是属于shops表还是shop_extras表，分别用两个数组保存
        $shopsData = [];
        $shopExtrasData = [];
        // 保存上传图片的路径，用来启用上传图片
        $uploadShopsImgPath = [];
        $uploadShopExtrasImgPath = [];
        $unsetField = [];
        $goodsCats = [];
        foreach ($data as $k => $v) {
            $field = Db::name('shop_bases')->where(['fieldName' => $k, 'dataFlag' => 1])->field('fieldName,fieldType,fieldAttr,isShopsTable,dateRelevance,isShow,isRequire')->find();
            if ($field['isShopsTable'] == 1) {
                // 属于shops表
                $shopsData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaIds = model('Areas')->getParentIs($shopsData[$k]);
                    if (!empty($areaIds)) $shopsData[$k] = implode('_', $areaIds) . "_";
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopsImgPath[] = $data[$k];
                }
            } else {
                // 属于shop_extras表
                $shopExtrasData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaIds = model('Areas')->getParentIs($shopExtrasData[$k]);
                    if (!empty($areaIds)) $shopExtrasData[$k] = implode('_', $areaIds) . "_";
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopExtrasImgPath[] = $data[$k];
                }
                // 日期字段入库前处理
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'date') {
                    // 当日期字段不是必填项，需删除该字段
                    if ($field['isRequire'] == 0) {
                        $unsetField[] = $field['fieldName'];
                    }
                    if ($field['dateRelevance']) {
                        $dateRelevance = explode(',', $field['dateRelevance']);
                        // 如果选择了长期，就删除字段的结束日期
                        if ($data[$dateRelevance[1]] == 1) {
                            $unsetField[] = $dateRelevance[0];
                        }
                    }
                }
                //经营范围
                if (!empty($data['goodsCatIds'])) $goodsCats = explode(',', $data['goodsCatIds']);
            }
        }
        // 删除无需入库的字段
        foreach ($shopExtrasData as $k => $v) {
            if (in_array($k, $unsetField)) {
                unset($shopExtrasData[$k]);
            }
        }

        $validate = new VShopBase();
        $validate->setRuleAndMessage($shopsData);
        $validate->setRuleAndMessage($shopExtrasData);
        $shopsData['applyStatus'] = 2;
        if (!$validate->scene('add')->check($data)) return WSTReturn($validate->getError());

        WSTUnset($data, 'id,shopId,userId,dataFlag,createTime,goodsCatIds,accredIds,isSelf');
        //判断经营范围
        $goodsCatIds = input('post.goodsCatIds')??365;
        $accredIds = input('post.accredIds');
        if ($goodsCatIds == '') return WSTReturn('请选择经营范围');
        Db::startTrans();
        try {
            $userId = 0;
            $isNewUser = (int)input('post.isNew/d');
            if ($isNewUser == 1) {
                //创建用户账号
                $user = [];
                $user['loginName'] = input('post.loginName');
                $user['loginPwd'] = input('post.loginPwd');
                $ck = WSTCheckLoginKey($user['loginName']);
                if ($ck['status'] != 1) return $ck;
                if ($user['loginPwd'] == '') $user['loginPwd'] = '88888888';
                $loginPwd = $user['loginPwd'];
                $user["loginSecret"] = rand(1000, 9999);
                $user['loginPwd'] = md5($user['loginPwd'] . $user['loginSecret']);
                $user["userType"] = 1;
                $user['createTime'] = date('Y-m-d H:i:s');
                model('users')->save($user);
                $userId = model('users')->userId;
            } else {
                $userId = (int)input('post.shopUserId/d');
                //检查用户是否可用
                $shopUser = model('users')->where(['userId' => $userId, 'dataFlag' => 1])->find();
                if (empty($shopUser)) return WSTReturn('无效的账号信息');
                $tmpShop = $this->where(['dataFlag' => 1, 'userId' => $userId])->find();
                if (!empty($tmpShop)) return WSTReturn('所关联账号已有店铺信息');
                $shopUser->userType = 1;
                $shopUser->save();
            }
            if ($userId > 0) {
                //创建商家基础信息
                $shopsData['userId'] = $userId;
                $shopsData['applyTime'] = date('Y-m-d H:i:s');
                $shopsData['createTime'] = date('Y-m-d');
                $shopsData['shopSn'] = ($data['shopSn'] == '') ? $this->getShopSn('S') : $data['shopSn'];
                $this->allowField(true)->save($shopsData);
                $shopId = $this->shopId;
                foreach ($uploadShopsImgPath as $k => $v) {
                    //启用上传图片
                    WSTUseResource(0, $shopId, $v, 'shops');
                }
                $shopExtrasData['shopId'] = $shopId;
                $seModel = model('ShopExtras');
                $seModel->allowField(true)->save($shopExtrasData);
                $extraId = $seModel->where(['shopId' => $shopId])->value('id');// 获取主键
                foreach ($uploadShopExtrasImgPath as $k => $v) {
                    //启用上传图片
                    WSTUseResource(0, $extraId, $v, 'shopextras');
                }

                //经营范围
                Db::name('cat_shops')->where('shopId', '=', $shopId)->delete();
                $goodsCats = explode(',', $goodsCatIds);
                foreach ($goodsCats as $key => $v) {
                    if ((int)$v > 0) {
                        Db::name('cat_shops')->insert(['shopId' => $shopId, 'catId' => $v]);
                    }
                }
                //认证类型
                Db::name('shop_accreds')->where('shopId', '=', $shopId)->delete();
                if ($accredIds != '') {
                    $accreds = explode(',', $accredIds);
                    foreach ($accreds as $key => $v) {
                        if ((int)$v > 0) {
                            Db::name('shop_accreds')->insert(['shopId' => $shopId, 'accredId' => $v]);
                        }
                    }
                }
                //建立店铺配置信息
                $sc = [];
                $sc['shopId'] = $shopId;
                Db::name('ShopConfigs')->insert($sc);
                $su = [];
                $su["shopId"] = $shopId;
                $su["userId"] = $userId;
                $su["roleId"] = 0;
                Db::name('shop_users')->insert($su);
                //建立店铺评分记录
                $ss = [];
                $ss['shopId'] = $shopId;
                Db::name('shop_scores')->insert($ss);
                Db::commit();
            }

            return WSTReturn("新增成功", 1);
        } catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('新增失败', -1);
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $data = input('post.');
        $shopId = input('post.shopId/d', 0);
        $shops = $this->get($shopId);
        if (empty($shops) || $shops->dataFlag != 1) return WSTReturn('店铺不存在');
        //先遍历前台传来的data,根据shop_base表判断是属于shops表还是shop_extras表，分别用两个数组保存
        $shopsData = [];
        $shopExtrasData = [];
        // 保存上传图片的路径，用来启用上传图片
        $uploadShopsImgPath = [];
        $uploadShopExtrasImgPath = [];
        $unsetField = [];
        $goodsCats = [];
        foreach ($data as $k => $v) {
            $field = Db::name('shop_bases')->where(['fieldName' => $k, 'dataFlag' => 1])->field('fieldName,fieldType,fieldAttr,isShopsTable,dateRelevance,isShow,isRequire')->find();
            if ($field['isShopsTable'] == 1) {
                // 属于shops表
                $shopsData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaId = $shopsData[$k];
                    $areaIds = model('Areas')->getParentIs($shopsData[$k]);
                    if (!empty($areaIds)) $shopsData[$k] = implode('_', $areaIds) . "_";
                    if ($field['fieldName'] == 'areaIdPath') $shopsData['areaId'] = $areaId;
                    if ($field['fieldName'] == 'bankAreaIdPath') $shopsData['bankAreaId'] = $areaId;
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopsImgPath[] = $data[$k];
                }
            } else {
                // 属于shop_extras表
                $shopExtrasData[$k] = $v;
                //获取地区
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'area') {
                    $areaIds = model('Areas')->getParentIs($shopExtrasData[$k]);
                    if (!empty($areaIds)) $shopExtrasData[$k] = implode('_', $areaIds) . "_";
                }
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'file') {
                    $uploadShopExtrasImgPath[] = $data[$k];
                }
                // 日期字段入库前处理
                if ($field['fieldType'] == 'other' && $field['fieldAttr'] == 'date') {
                    // 当日期字段不是必填项，需删除该字段
                    if ($field['isRequire'] == 0) {
                        $unsetField[] = $field['fieldName'];
                    }
                    if ($field['dateRelevance']) {
                        $dateRelevance = explode(',', $field['dateRelevance']);
                        // 如果选择了长期，就删除字段的结束日期
                        if ($data[$dateRelevance[1]] == 1) {
                            $unsetField[] = $dateRelevance[0];
                        }
                    }
                }
                //经营范围
                if (!empty($data['goodsCatIds'])) $goodsCats = explode(',', $data['goodsCatIds']);
            }
        }

        // 删除无需入库的字段
        foreach ($shopExtrasData as $k => $v) {
            if (in_array($k, $unsetField)) {
                unset($shopExtrasData[$k]);
            }
        }

        $validate = new VShopBase();
        $validate->setRuleAndMessage($shopsData);
        $validate->setRuleAndMessage($shopExtrasData);

        //判断经营范围
        $goodsCatIds = input('post.goodsCatIds');
        $accredIds = input('post.accredIds');

        Db::startTrans();
        try {
            //检测店铺编号是否存在
            if ($data['shopSn'] == '') {
                $shopsData['shopSn'] = $this->getShopSn('S');
            } else {
                if (!$this->checkShopSn($data['shopSn'], $shopId)) {
                    $shopsData['shopSn'] = $data['shopSn'];
                } else {
                    return WSTReturn('该店铺编号已存在');
                }
            }
            $shopsData['shopId'] = $shopId;
            $shopsData['shopStatus'] = ((int)input('shopStatus') == 1) ? 1 : -1;
            if ($shopsData['shopStatus'] == 0) {
                $shopsData['statusDesc'] = input('statusDesc');
                if ($shopsData['statusDesc'] == '') return WSTReturn('请输入停止原因');
            }
            $shopExtrasData['shopId'] = $shopId;
            if (!$validate->scene('add')->check($data)) return WSTReturn($validate->getError());
            WSTUnset($data, 'id,shopId,userId,dataFlag,createTime,goodsCatIds,accredIds,isSelf');
            $this->allowField(true)->save($shopsData, ['shopId' => $shopId]);
            foreach ($uploadShopsImgPath as $k => $v) {
                //启用上传图片
                WSTUseResource(0, $this->shopId, $v, 'shops');
            }
            $seModel = model('ShopExtras');
            $seModel->allowField(true)->save($shopExtrasData, ['shopId' => $shopId]);
            $extraId = $seModel->where(['shopId' => $shopId])->value('id');// 获取主键
            foreach ($uploadShopExtrasImgPath as $k => $v) {
                //启用上传图片
                WSTUseResource(0, $extraId, $v, 'shopextras');
            }
            if (!empty($goodsCatIds)) {
                //经营范围
                Db::name('cat_shops')->where('shopId', '=', $shopId)->delete();
                $goodsCats = explode(',', $goodsCatIds);
                foreach ($goodsCats as $key => $v) {
                    if ((int)$v > 0) {
                        Db::name('cat_shops')->insert(['shopId' => $shopId, 'catId' => $v]);
                    }
                }
            }

            //认证类型
            Db::name('shop_accreds')->where('shopId', '=', $shopId)->delete();
            if ($accredIds != '') {
                $accreds = explode(',', $accredIds);
                foreach ($accreds as $key => $v) {
                    if ((int)$v > 0) {
                        Db::name('shop_accreds')->insert(['shopId' => $shopId, 'accredId' => $v]);
                    }
                }
            }
            if ((int)input('shopStatus') != 1) {
                //店铺状态不正常就停用所有的商品
                model('goods')->unsaleByshopId($shopId);
            }
            //改变店铺钩子事件
            hook('afterChangeShopStatus', ['shopId' => $shopId]);
            Db::commit();
            return WSTReturn("编辑成功", 1);
        } catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('编辑失败', -1);
        }
    }

    /**
     * 获取所有店铺id
     */
    public function getAllShopId()
    {
        return $this->where(['dataFlag' => 1, 'shopStatus' => 1])->column('shopId');
    }

    /**
     * 搜索经验范围的店铺
     */
    public function searchQuery()
    {
        $goodsCatatId = (int)input('post.goodsCatId');
        if ($goodsCatatId <= 0) return [];
        $key = input('post.key');
        $where = [];
        $where[] = ['dataFlag', '=', 1];
        $where[] = ['shopStatus', '=', 1];
        $where[] = ['catId', '=', $goodsCatatId];
        if ($key != '') $where[] = ['shopName|shopSn', 'like', '%' . $key . '%'];
        return $this->alias('s')->join('__CAT_SHOPS__ cs', 's.shopId=cs.shopId', 'inner')
            ->where($where)->field('shopName,s.shopId,shopSn')->select();
    }

    /**
     * 自营自动登录
     */
    public function selfLogin($id)
    {
        $shopId = $id;
        $userid = $this->where(["dataFlag" => 1, "shopStatus" => 1, "shopId" => $shopId])->field('userId')->find();
        if (!empty($userid['userId'])) {
            $userId = $userid['userId'];
            //获取用户信息
            $u = new Users();
            $rs = $u->getById($userId);
            //获取用户等级
            $rrs = WSTUserRank($rs['userTotalScore']);
            $rs['rankId'] = $rrs['rankId'];
            $rs['rankName'] = $rrs['rankName'];
            $rs['userrankImg'] = $rrs['userrankImg'];
            $ip = request()->ip();
            $u->where(["userId" => $userId])->update(["lastTime" => date('Y-m-d H:i:s'), "lastIP" => $ip]);
            //加载店铺信息
            $shops = new Shops();
            $shop = $shops->where(["userId" => $userId, "dataFlag" => 1])->find();
            $shop['SHOP_MASTER'] = true;
            if (!empty($shop)) $rs = array_merge($shop->toArray(), $rs->toArray());
            //记录登录日志
            $data = array();
            $data["userId"] = $userId;
            $data["loginTime"] = date('Y-m-d H:i:s');
            $data["loginIp"] = $ip;
            Db::name('log_user_logins')->insert($data);
            if ($rs['userPhoto'] == '') $rs['userPhoto'] = WSTConf('CONF.userLogo');
            $rs["roleId"] = 0;
            session('WST_USER', $rs);
            hook('afterUserLogin', ['user' => $rs]);
            return WSTReturn("", "1");
        }
        return WSTReturn("", -1);
    }

}
