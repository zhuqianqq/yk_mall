<?php
/**
 * 支付宝移动支付表
 */
namespace wstmart\common\model;

use think\facade\Db;

class TAliMobilePay extends ApiBaseModel
{
    protected $table = "mall_t_ali_mobile_pay";

    const PAY_STATUS_WAIT_PAY = 0;
    const PAY_STATUS_SUCCESS = 1;
    const PAY_STATUS_FAIL = 2;
    const PAY_STATUS_CANCEL = 3;

    /**
     * @var array 支付状态
     */
    public static $PAY_STATUS_ARR = [
        self::PAY_STATUS_WAIT_PAY => "待支付",
        self::PAY_STATUS_SUCCESS => "支付成功",
        self::PAY_STATUS_FAIL => "支付失败",
        self::PAY_STATUS_CANCEL => "支付取消",
    ];
}
