<?php
namespace wstmart\admin\model;
use wstmart\admin\validate\LimitWords as validate;
use think\Db;
/**
 * 系统禁用关键字业务处理
 */
class LimitWords extends Base{
    /**
	 * 分页
	 */
	public function pageQuery(){
		$word = input('word');
		$where = [];
		$where[] = ['dataFlag','=',1];
        if($word!='')$where[] = ['word','like',"%".$word."%"];
        $mrs = Db::name('limit_words')
			->where($where)
			->order('id', 'desc')->paginate(input('limit/d'))->toArray();
		return $mrs;
	}

    /**
     * 新增
     */
    public function add(){
        $data = input('post.');
        $data['createTime'] = date('Y-m-d H:i:s');
        $validate = new validate();
        if(!$validate->scene('add')->check($data))return WSTReturn($validate->getError());
        $result = $this->allowField(true)->save($data);
        if(false !== $result){
            $this->updateWSTConfData();
            return WSTReturn("新增成功", 1);
        }else{
            return WSTReturn($this->getError(),-1);
        }
    }

    /**
     * 编辑
     */
    public function edit(){
        $id = input('post.id');
        $data = input('post.');
        $validate = new validate();
        if(!$validate->scene('edit')->check($data))return WSTReturn($validate->getError());
        $result = $this->allowField(true)->update($data,['id'=>$id]);
        if(false !== $result){
            $this->updateWSTConfData();
            return WSTReturn("编辑成功", 1);
        }else{
            return WSTReturn($this->getError(),-1);
        }
    }

    /**
     * 删除
     */
    public function del(){
        $id = (int)input('post.id');
        $data = [];
        $data['dataFlag'] = -1;
        Db::startTrans();
        try{
            $result = $this->update($data,['id'=>$id]);
            if(false !== $result){
                $this->updateWSTConfData();
                Db::commit();
                return WSTReturn("删除成功", 1);
            }
        }catch (\Exception $e) {
            Db::rollback();
            return WSTReturn('删除失败',-1);
        }
    }

    /*
     * 更新缓存里的值
     */
    public function updateWSTConfData(){
        $words = $this->where(['dataFlag'=>1])->column('word');
        $rs = '';
        if(count($words)>0){
            $rs = implode(',',$words);
        }
        Db::name('sys_configs')->where(['fieldCode'=>'limitWords'])->update(['fieldValue'=>$rs]);
        cache('WST_CONF',null);
    }
}
