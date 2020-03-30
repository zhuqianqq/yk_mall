<?php
namespace wstmart\common\model;
use think\Exception;
use wstmart\common\validate\UserAlipayAccount as validate;
use think\Db;
use think\facade\Cache;
/**
 * 提现-支付宝账号
 */
class UserAlipayAccount extends Base{
	protected $pk = 'account_id';
    protected $table = "mall_user_alipay_account";

    public function getById($id){
        return $this->get(['id'=>$id]);
    }

    /**
     * 新增
     */
    public function add(){
        $data = input('post.');
        $userid = isset($data['mall_user_id']) ? $data['mall_user_id'] : 0;
        $accountNum = isset($data['account_num']) ? $data['account_num'] : '';
        $accountNumConfirm = isset($data['account_num_confirm']) ? $data['account_num_confirm'] : '';
        $trueName = isset($data['true_name']) ? $data['true_name'] : '';

        if (empty($userid) ||  empty($accountNum) || empty($accountNumConfirm) || empty($trueName)) {
            throw new \Exception('缺少参数');
        }
        if ($accountNum !== $accountNumConfirm) {
            throw new \Exception('两次输入不一致');
        }
        $insertData['user_id'] = $userid;
        $insertData['true_name'] = $trueName;
        $insertData['account_num'] = $accountNum;

        $user = Users::where("userID = {$userid}")->find();
        if (empty($user)) {
            throw new \Exception('没有该记录');
        }
        $userRecord = UserAlipayAccount::where("user_id = {$userid}")->find();
        if (!empty($userRecord)) {
            throw new \Exception('请勿重复提交');
        }
        $validate = new validate();
        if(!$validate->scene('add')->check($data)) {
            throw new \Exception($validate->getError());
        }
        $insertData['create_time'] = date('Y-m-d H:i:s');
        $id = $this->insertGetId($insertData);
        if(false !== $id){
            return $id;
        }
        throw new \Exception("新增失败");
    }

    /**
     * 编辑
     */
    public function edit($accountId){
        $data = input('post.');
        if (empty($accountId)) {
            throw new \Exception('缺少参数');
        }
        $userRecord = $this->where("account_id = {$accountId}")->find();
        if (empty($userRecord)) {
            throw new \Exception('请先添加');
        }
        if (empty($data['true_name'])) {
            $data['true_name'] = $userRecord['true_name'];
        }

        $accountNum = isset($data['account_num']) ? $data['account_num'] : '';
        $accountNumConfirm = isset($data['account_num_confirm']) ? $data['account_num_confirm'] : '';

        if (!empty($accountNum) && !empty($accountNumConfirm)) {
            if ($accountNumConfirm !== $accountNum) {
                throw new \Exception('两次输入不一致');
            }
        }
        $result = $this->allowField(true)->save($data, ['account_id'=>(int)$accountId]);
        if(false !==$result){
           return true;
        }
        throw new \Exception("修改失败");
    }

    /**
     * 查询
     * @throws \Exception
     */
    public function pageQuery()
    {
        $data = input('post.');
        $userid = isset($data['mall_user_id']) ? $data['mall_user_id'] : 0;
        if (empty($userid)) {
            throw new \Exception('缺少参数');
        }
        $user = Users::where("userID = {$userid}")->find();
        if (empty($user)) {
            throw new \Exception('没有该记录');
        }
        $userRecord = $this->where("user_id = {$userid}")->find();
        if (empty($userRecord)) {
            throw new \Exception('没有数据');
        }
        $data = [
            'trueName' => $userRecord['true_name'],
            'accountNum' => $userRecord['account_num'],
        ];
        return $data;
    }
}
