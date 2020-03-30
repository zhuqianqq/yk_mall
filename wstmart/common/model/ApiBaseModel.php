<?php
namespace wstmart\common\model;

use think\Model;
use think\Db;

abstract class ApiBaseModel extends Model
{
    /**
     * @var string 数据库连接
     */
    protected $connection = 'DB_API';

    /**
     * @var string 错误信息
     */
    protected $error;

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error ?? '';
    }

    /**
     * 查询总记录数
     * @param string $sql sql语句
     * @param array $bind 绑定的参数
     * @return int
     */
    public static function queryTotal($sql,$bind = [])
    {
        $total = 0;
        $res = Db::query($sql,$bind);
        if($res){
            $total = intval(current($res[0]));
        }
        return $total;
    }
}
