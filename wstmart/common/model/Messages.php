<?php
namespace wstmart\common\model;

/**
 * 商城消息
 */
use think\Db;
class Messages extends Base{
    /**
     * 获取最后一条商城消息
     */
    public function getLastMsg($uId=0){
        $userId = $uId==0?(int)session('WST_USER.userId'):$uId;
        $where = ['receiveUserId'=>(int)$userId,'dataFlag'=>1];
        $data = Db::name('Messages')->where($where)
                     ->field('id,msgContent,msgStatus,createTime')
                     ->order('id desc')
                     ->find();
        if(!empty($data)){
            $data['msgContent'] = strip_tags(htmlspecialchars_decode($data['msgContent']));
            $data['createTime'] = date('Y-m-d', strtotime($data['createTime']));
        }else{
            $data = ['msgContent'=>'-', 'createTime'=>'-'];
        }
        return $data;
    }
   /**
    * 获取列表
    */
    public function pageQuery(){
      	 $userId = (int)session('WST_USER.userId');
         $where = ['receiveUserId'=>(int)$userId,'dataFlag'=>1];
         $page = model('Messages')->where($where)->order('msgStatus asc,id desc')->paginate(input('post.limit/d'))->toArray();
         foreach ($page['data'] as $key => $v){
         	$page['data'][$key]['msgContent'] = WSTMSubstr(strip_tags(htmlspecialchars_decode($v['msgContent'])),0,140);
         }
         return $page;
    }
   /**
    *  获取某一条消息详情
    */
    public function getById(){
    	$userId = (int)session('WST_USER.userId');
        $id = (int)input('msgId');
        $data = $this->get(['id'=>$id,'receiveUserId'=>$userId]);
        if(!empty($data)){
          $data['msgContent'] = str_replace('${DOMAIN}',WSTConf('CONF.resourcePath'),$data['msgContent']);    
          $data['msgContent'] = htmlspecialchars_decode($data['msgContent']);
          if($data['msgStatus']==0)
            model('Messages')->where('id',$id)->setField('msgStatus',1);
        }
        return $data;
    }

    /**
     * 删除
     */
    public function del(){
    	$userId = (int)session('WST_USER.userId');
        $id = input('id/d');
        $data = [];
        $data['dataFlag'] = -1;
        $result = $this->update($data,['id'=>$id,'receiveUserId'=>$userId]);
        if(false !== $result){
            return WSTReturn("删除成功", 1);
        }else{
            return WSTReturn($this->getError(),-1);
        }
    }
    /**
    * 批量删除
    */
    public function batchDel(){
    	$userId = (int)session('WST_USER.userId');
        $ids = input('ids/a');
        $data = [];
        $data['dataFlag'] = -1;
        $result = $this->update($data,[['id','in',$ids],['receiveUserId','=',$userId]]);
        if(false !== $result){
            return WSTReturn("删除成功", 1);
        }else{
            return WSTReturn($this->getError(),-1);
        }
    }
    /**
    * 标记为已读
    */
    public function batchRead(){
    	$userId = (int)session('WST_USER.userId');
        $ids = input('ids/a');
        $data = [];
        $data['msgStatus'] = 1;
        $result = $this->update($data,[['id','in',$ids],['receiveUserId','=',$userId]]);
        if(false !== $result){
            return WSTReturn("操作成功", 1);
        }else{
            return WSTReturn($this->getError(),-1);
        }
    }

    
}
