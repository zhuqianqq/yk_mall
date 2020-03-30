<?php 
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 商品分类验证器
 */
class GoodsCats extends Validate{
	protected $rule = [
	    'catName' => 'require|max:30',
	    'commissionRate' => 'require',
	    'catSort' => 'require|max:16',
        'catListTheme'=>'require',
        'detailTheme'=>'require'
    ];
    
    protected $message = [
         'catName.require' => '请输入商品分类名称',
         'catName.max' => '商品分类名称不能超过10个字符',
         'commissionRate.require' => '请输入分类佣金',
         'catSort.require' => '请输入排序号',
         'catSort.max' => '排序号不能超过8个字符',
         'catListTheme.require' => '请输入电脑端商品列模板名',
         'detailTheme.require' => '请输入电脑端商品详情模板名',
    ];
    
    protected $scene = [
        'add'   =>  ['catName','commissionRate','catSort','catListTheme','detailTheme'],
        'edit'  =>  ['catName','commissionRate','catSort','catListTheme','detailTheme']
    ]; 
}