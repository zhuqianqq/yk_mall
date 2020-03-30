<?php
namespace wstmart\common\model;
use think\Db;
use think\Loader;
use Env;
use wstmart\common\model\ApiBaseModel;

/**
 * 主播认证业务处理
 */
class TMemberValidates extends ApiBaseModel {
    protected $pk = 'validateId';
    protected $table = 't_member_validate';
    /**
     * 分页
     */
    public function pageQuery(){
        $where = [];
        // 身份证号
        $id_card = input('id_card', '');
        // 主播ID
        $user_id = input('user_id', 0);
        if (!empty($id_card)) {
            $where[] = ['id_card', '=', $id_card];
        }
        if (!empty($user_id)) {
            $where[] = ['user_id', '=', $user_id];
        }

        $page = $this->where($where)->order('create_time desc')->paginate(input('limit/d'))->toArray();
        return $page;
    }

    /**
     * 获取提现详情
     */
    public function getById(){
        $id = (int)input('id');
        $rs =  $this->get($id);
        return $rs;
    }

    /**
     * 处理提现成功
     */
    public function handle(){
        $id = (int)input('validateId');
        $cash = $this->get($id);
        if(empty($cash))return WSTReturn('无效的认证申请记录');
        $userid = $cash->user_id;
        $cash->status = 2;
        $result = $cash->save();

        $m = new TMember();
        $m->where("user_id = {$userid}")->setField('audit_status', 1);
        if(false != $result){
            return WSTReturn('操作成功!',1);
        }
        return WSTReturn('操作失败!',-1);
    }

    /**
     * 处理提现认证失败
     */
    public function handleFail(){
        $id = (int)input('validateId');
        $cash = $this->get($id);
        if(empty($cash))return WSTReturn('无效的认证申请记录');
        if(input('remark')=='')return WSTReturn('请输入认证失败原因');
        $cash->status = 3;
        $cash->remark = input('remark');
        $result = $cash->save();
        if(false != $result){
            return WSTReturn('操作成功!',1);
        }
        return WSTReturn('操作失败!',-1);
    }
}
