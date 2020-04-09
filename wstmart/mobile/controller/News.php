<?php
namespace wstmart\mobile\controller;
use wstmart\common\model\Articles as M;
/**
 * 新闻控制器
 */
class News extends Base{
	/**
	 * 列表查询
	 */
    public function view(){
        $m = new M();
        $data = $m->getChildInfos();
        $catId = $data['0']['catId'];
        $articleId = (int)input('articleId');
        $this->assign('articleId',$articleId);
        $this->assign('catInfo',$data);
        $this->assign('catId',$catId);
        return $this->fetch('news_list');
    }
    /**
    * 获取商城快讯列表
    */
    public function getNewsList(){
    	$m = new M();
    	$data = $m->getArticles();
    	foreach($data['data'] as $k=>$v){
    		$data['data'][$k]['articleContent'] = strip_tags(html_entity_decode($v['articleContent']));
            $data['data'][$k]['createTime'] = date('Y-m-d',strtotime($v['createTime']));
            $data['data'][$k]['coverImg'] = str_replace("_thumb.", ".",  $v['coverImg']);
    	}
    	return $data;
    }
    /**
    * 查看详情
    */
    public function getNews(){
    	$m = new M();
    	$data = $m->getNewsById();
        $data['articleContent']=htmlspecialchars_decode($data['articleContent']);
        $data['createTime'] = date('Y-m-d',strtotime($data['createTime']));
        $this->assign('data',$data);
        return $this->fetch('news_detail');
    }
    /**
     * 点赞
     */
    public function like(){
        $m = new M();
        $data = $m->like();
        return $data;
    }
}
