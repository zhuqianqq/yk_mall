<?php
namespace wstmart\common\model;
use think\Model;
use think\Db;
/**
 * 基础模型器
 */

class Base extends Model {
	/**
	 * 获取空模型
	 */
	public function getEModel($tables){
		$rs =  Db::query('show columns FROM `'.config('database.prefix').$tables."`");
		$obj = [];
		if($rs){
			foreach($rs as $key => $v) {
				$obj[$v['Field']] = $v['Default'];
				if($v['Key'] == 'PRI')$obj[$v['Field']] = 0;
			}
		}
		return $obj;
	}

	/**
	 * 导出Excel
	 */
	public function PHPExcelWriter($objPHPExcel,$name){
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        // 从浏览器直接输出$filename
        header('Content-Type:application/csv;charset=UTF-8');
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-excel;");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition: attachment;filename="'.$name.'.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
	}
}