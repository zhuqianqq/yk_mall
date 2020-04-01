<?php
/**
 * 图片处理帮助类(水印，压缩)
 */
namespace util;

use util\Tools;

class ImageHelper
{
    /**
     * 给$groundImage图片添加水印
     * @param string $groundImage 背景图片路径
     * @param string $waterImage 水印图片路径
     * @return bool 成功返回true
     */
    public static function addWaterMark($groundImage, $waterImage = "")
    {
        try {
            if (empty($waterImage)) {
                //$waterImage = "/home/web/www.yinzhong.cc/htdocs/assets/app/img/water_mark.png";
            }
            if (!file_exists($groundImage) || !file_exists($waterImage)) {
                return false;
            }
            //读取水印文件
            $water_info = @getimagesize($waterImage);
            if ($water_info === false) {
                return false;
            }
            $water_w = $water_info[0];//取得水印图片的宽
            $water_h = $water_info[1];//取得水印图片的高
            switch ($water_info[2]) { //取得水印图片的格式
                case 1:
                    $water_im = @imagecreatefromgif($waterImage);
                    break;
                case 2:
                    $water_im = @imagecreatefromjpeg($waterImage);
                    break;
                case 3:
                    $water_im = @imagecreatefrompng($waterImage);
                    break;
                default:
                    return false;
            }
            if(!$water_im){
                return false;
            }
            //读取背景图片
            $ground_info = @getimagesize($groundImage);
            if ($ground_info === false) {
                return false;
            }
            $ground_w = $ground_info[0];//取得背景图片的宽
            $ground_h = $ground_info[1];//取得背景图片的高
            switch ($ground_info[2]) { //取得背景图片的格式
                case 1:
                    $ground_im = @imagecreatefromgif($groundImage);
                    break;
                case 2:
                    $ground_im = @imagecreatefromjpeg($groundImage);
                    break;
                case 3:
                    $ground_im = @imagecreatefrompng($groundImage);
                    break;
                default:
                    return false;
            }
            if(!$ground_im){
                return false;
            }
            //水印位置底端居右
            $posX = $ground_w - $water_w; // 是距离右侧0px
            $posY = 0; // 是距离顶部 0px
            imagealphablending($ground_im, true);  //设定图像的混色模式
            imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w, $water_h);//拷贝水印到目标文件
            //生成水印后的图片
            @unlink($groundImage);
            switch ($ground_info[2]) {//取得背景图片的格式
                case 1:
                    @imagegif($ground_im, $groundImage);
                    break;
                case 2:
                    @imagejpeg($ground_im, $groundImage);
                    break;
                case 3:
                    @imagepng($ground_im, $groundImage);
                    break;
                default:
                    return false;
            }
            //释放内存
            unset($water_info);
            imagedestroy($water_im);
            unset($ground_info);
            imagedestroy($ground_im);
            return true;
        }catch(\Exception $ex){
            Tools::addLog("img_error","addWater error:".$ex->getMessage());
            return false;
        }
    }

    /**
     * 调整图片尺寸
     * @param string $image 源图片文件路径
     * @param string $image_thumb 目标图片文件路径
     * @param int $dw 调整后的最大宽度
     * @param int $dh 调整后的最大高度
     * @return bool
     */
    public static function resize($image, $image_thumb, $dw = 1200, $dh = 900)
    {
        try{
            if (!file_exists($image)) {
                return false;
            }
            //取得文件的类型,根据不同的类型建立不同的对象
            $imageInfo = @getimagesize($image);
            if(!$imageInfo){
                return false;
            }
            switch ($imageInfo[2]) {
                case 1:
                    $img = @imagecreatefromgif($image);
                    break;
                case 2:
                    $img = @imagecreatefromjpeg($image);
                    break;
                case 3:
                    $img = @imagecreatefrompng($image);
                    break;
            }
            #如果对象没有创建成功,则说明非图片文件
            if (empty($img)) {
                return false;
            }
            #如果是执行调整尺寸操作则
            $w = imagesx($img);  //取得源图像宽度
            $h = imagesy($img);  //取得源图像高度
            $width = $w;  //目标图像宽度
            $height = $h; //目标图像高度
            if ($width > $dw) {
                $per = $dw / $width; //缩放比例
                $width = $dw;
                $height = $height * $per;
                if ($height > $dh) {
                    $per = $dh / $height;
                    $height = $dh;
                    $width = $width * $per;
                }
            } else if ($height > $dh) {
                $per = $dh / $height;
                $height = $dh;
                $width = $width * $per;
                if ($width > $dw) {
                    $per = $dw / $width;
                    $width = $dw;
                    $height = $height * $per;
                }
            } else {
                $width = $width;
                $height = $height;
            }
            $bgImg = imagecreatetruecolor($width, $height); //新建一个真彩色画布
            imagecopyresampled($bgImg, $img, 0, 0, 0, 0, $width, $height, $w, $h);  //重采样拷贝部分图像并调整大小
            imagejpeg($bgImg, $image_thumb); //以JPEG格式将图像输出到文件
            imagedestroy($img);
            imagedestroy($bgImg);
            return true;
        }catch(\Exception $ex){
            Tools::addLog("img_error","resize error:".$ex->getMessage());
            return false;
        }
    }
}