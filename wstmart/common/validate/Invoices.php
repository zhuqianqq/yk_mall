<?php 
namespace wstmart\common\validate;
use think\Validate;
/**
 * 发票信息验证器
 */
class Invoices extends Validate{
	protected $rule = [
		'invoiceHead' => 'require',
		'invoiceCode' => 'require',
		'invoiceType' => 'in:0,1|checkInvoiceBankName',
	];
	
	protected $message  =   [
		'invoiceHead.require' => '请输入发票抬头',
		'invoiceCode.require' => '请填写发票税号',
		'invoiceType.in' => '请选择发票类型'
	];

    protected $scene = [
        'add' => ['invoiceHead', 'invoiceCode', 'invoiceType'],
        'edit' => ['invoiceHead', 'invoiceCode', 'invoiceType']
    ];

    /**
     * 当发票类型不是普通
     */
    public function checkInvoiceBankName($value){
    	$invoiceType = (int)input('post.invoiceType');
    	if($invoiceType == 1){
    		if (input('post.invoiceAddr') == '') return '请填写发票地址';
    		if (input('post.invoicePhoneNumber') == '') return '请填写发票电话';
    		if (input('post.invoiceBankName') == '') return '请填写发票开户银行';
    		if (input('post.invoiceBankNo') == '') return '请填写发票银行账户';
    	}
    	return true;
    }
}