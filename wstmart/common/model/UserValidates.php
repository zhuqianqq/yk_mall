<?php
namespace wstmart\common\model;
use think\Exception;
use wstmart\common\validate\UserValidates as validate;
use think\Db;
use think\facade\Cache;
/**
 * 实名认证
 */
class UserValidates extends Base{
	protected $pk = 'validateId';
    protected $table = "mall_user_validate";

    // 0 待审核 1 审核中 2 审核通过 3 审核失败
    const STATUS_INIT = 0;
    const STATUS_CHECKING = 1;
    const STATUS_PASS = 2;
    const STATUS_FAIL = 3;

    public function getById($id){
        return $this->get(['id'=>$id]);
    }

    /**
     * 新增
     */
    public function add(){
        $data = input('post.');
        $userid = isset($data['mall_user_id']) ? $data['mall_user_id'] : 0;
        $trueName = isset($data['true_name']) ? $data['true_name'] : '';
        $id_card = isset($data['id_card']) ? $data['id_card'] : '';
        $address = isset($data['address']) ? $data['address'] : '';
        $sex = isset($data['sex']) ? $data['sex'] : 0;
        $id_card_positive = isset($data['id_card_positive']) ? $data['id_card_positive'] : '';
        $id_card_back = isset($data['id_card_back']) ? $data['id_card_back'] : '';
        $phone = isset($data['phone']) ? $data['phone'] : '';
        if (empty($userid) || empty($trueName) || empty($id_card) || empty($id_card_positive) || empty($id_card_back) || empty($phone)) {
            throw new \Exception('缺少参数');
        }

        $user = Users::where("userID = {$userid}")->find();
        if (empty($user)) {
            throw new \Exception('没有该记录');
        }
        $userRecord = UserValidates::where("user_id = {$userid}")->find();
        if (!empty($userRecord)) {
            throw new \Exception('请勿重复提交');
        }
        if ($phone !== $user['userPhone']) {
            throw new \Exception('手机号错误，请重新填写~');
        }
        $validate = new validate();
        if(!$validate->scene('add')->check($data)) {
            throw new \Exception($validate->getError());
        };
        $insertData['user_id'] = $userid;
        $insertData['true_name'] = $trueName;
        $insertData['id_card'] = $id_card;
        $insertData['address'] = $address;
        $insertData['sex'] = $sex;
        $insertData['id_card_positive'] = $id_card_positive;
        $insertData['id_card_back'] = $id_card_back;
        $insertData['phone'] = $phone;
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
    public function edit($validateId){
        if (empty($validateId)) {
            throw new \Exception('缺少参数');
        }
        $data = input('post.');
        $userRecord = $this->where("validateId = {$validateId}")->find();
        if (empty($userRecord)) {
            throw new \Exception('请先添加');
        }
        $status = $userRecord['status'];
        // 如果状态为审核中和审核通过，则不能进行修改
        $noEditStatus = [self::STATUS_CHECKING, self::STATUS_PASS];
        if (in_array($status, $noEditStatus)) {
            throw new \Exception('当前不可编辑');
        }
        if ($status == self::STATUS_FAIL) {
            // 如果是拒绝重新提交，则重置为0
            $data['status'] = self::STATUS_INIT;
            $data['remark'] = '';
        }
        if (empty($data['true_name'])) {
            $data['true_name'] = $userRecord['true_name'];
        }
        if (empty($data['id_card'])) {
            $data['id_card'] = $userRecord['id_card'];
        }
        if (empty($data['address'])) {
            $data['address'] = $userRecord['address'];
        }
        if (empty($data['sex'])) {
            $data['sex'] = 0;
        }
        if (empty($data['id_card_positive'])) {
            $data['id_card_positive'] = $userRecord['id_card_positive'];
        }
        if (empty($data['id_card_back'])) {
            $data['id_card_back'] = $userRecord['id_card_back'];
        }
        if (empty($data['phone'])) {
            $data['phone'] = $userRecord['phone'];
        } else {
            $userid = $userRecord['user_id'];
            $user = Users::where("userID = {$userid}")->find();
            if (empty($user)) {
                throw new \Exception('没有该记录');
            }
            $userPhone = $user['userPhone'];
            if ($userPhone !== $data['phone']) {
                throw new \Exception('手机号错误，请重新填写~');
            }
        }

            $result = $this->allowField(true)->save($data, ['validateId'=>(int)$validateId]);
        if(false !==$result){
            if(false !== $result){
                return true;
            }
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
        switch ($userRecord['status']) {
            // 0 待审核 1 审核中 2 审核通过 3 审核失败
            case 1:
                $content = '等待审核中';
                break;
            case 2:
                $content = '恭喜您！审核通过';
                break;
            case 3:
                $content = '很遗憾，审核失败，具体原因如下:';
                break;
            default:
                $content = '待审核';
        }
        $data = [
            'validateId' => $userRecord['validateId'],
            'trueName' => $userRecord['true_name'],
            'idCard' => $userRecord['id_card'],
            'address' => $userRecord['address'],
            'sex' => $userRecord['sex'],
            'idCardPositive' => $userRecord['id_card_positive'],
            'idCardBack' => $userRecord['id_card_back'],
            'phone' => $userRecord['phone'],
            'status' => $userRecord['status'],
            'reason' => $userRecord['remark'],
            'tip' => $content,
        ];
        return $data;
    }
}
