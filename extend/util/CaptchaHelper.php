<?php
namespace util;

use think\facade\Session;

/**
 * @property mixed           seKey
 * @property mixed           expire
 * @property mixed           length
 * @property mixed           fontSize
 * @property float|mixed     imageH
 * @property float|int|mixed imageW
 * @property mixed           codeSet
 * @property mixed           reset
 * @property mixed           fontttf
 * @property mixed           useNoise
 * @property mixed           useCurve
 * @property mixed           useArithmetic
 */
class CaptchaHelper
{
    protected $config = [
        'seKey'         => 'captcha_key', // 验证码加密密钥
        'codeSet'       => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',// 验证码字符集合
        'expire'        => 1800, // 验证码过期时间（s）
        'fontSize'      => 25, // 验证码字体大小(px)
        'useCurve'      => true, // 是否画混淆曲线
        'useNoise'      => false, // 是否添加杂点
        'imageH'        => 0, // 验证码图片高度
        'imageW'        => 0, // 验证码图片宽度
        'length'        => 5, // 验证码位数
        'fontttf'       => '', // 验证码字体，不设置随机获取
        'bg'            => [243, 251, 254], // 背景颜色
        'reset'         => true, // 验证成功后是否重置
        'useArithmetic' => false //是否使用算术验证码
    ];

    private $im    = null; // 验证码图片实例
    private $color = null; // 验证码字体颜色

    /**
     * 架构方法 设置参数
     * @access public
     * @param  array $config 配置参数
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 使用 $this->name 获取配置
     * @access public
     * @param  string $name 配置名称
     * @return mixed    配置值
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    /**
     * 设置验证码配置
     * @access public
     * @param  string $name  配置名称
     * @param  string $value 配置值
     * @return void
     */
    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 检查配置
     * @access public
     * @param  string $name 配置名称
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->config[$name]);
    }

    /**
     * 验证验证码是否正确
     * @access public
     * @param string $code 用户验证码
     * @param string $id   验证码标识
     * @return bool 用户验证码是否正确
     */
    public function check($code, $id = '')
    {
        $key = $this->authcode($this->seKey) . $id;
        // 验证码不能为空
        $secode = Session::get($key, '');
        if (empty($code) || empty($secode)) {
            return false;
        }
        // session 过期
        if (time() - $secode['verify_time'] > $this->expire) {
            Session::delete($key, '');
            return false;
        }

        if ($this->authcode(strtoupper($code)) == $secode['verify_code']) {
            $this->reset && Session::delete($key, '');
            return true;
        }

        return false;
    }

    /**
     * 输出验证码并把验证码的值保存的session中
     * 验证码保存到session的格式为： array('verify_code' => '验证码值', 'verify_time' => '验证码创建时间');
     * @access public
     * @param string $id 要生成验证码的标识
     * @return \think\Response
     */
    public function entry($id = '')
    {
        // 图片宽(px)
        $this->imageW || $this->imageW = $this->length * $this->fontSize * 1.5 + $this->length * $this->fontSize / 2;
        // 图片高(px)
        $this->imageH || $this->imageH = $this->fontSize * 2.5;
        // 建立一幅 $this->imageW x $this->imageH 的图像
        $this->im = imagecreate( $this->imageW,  $this->imageH);
        // 设置背景
        imagecolorallocate($this->im, $this->bg[0], $this->bg[1], $this->bg[2]);

        // 验证码字体随机颜色
        $this->color = imagecolorallocate($this->im, mt_rand(1, 150), mt_rand(1, 150), mt_rand(1, 150));


        if ($this->useNoise) {
            // 绘杂点
            $this->writeNoise();
        }
        if ($this->useCurve) {
            // 绘干扰线
            $this->writeCurve();
        }


        $ttfPath = __DIR__ . '/assets/ttfs/';

        // 绘验证码
        $code   = []; // 验证码
        $codeNX = 0; // 验证码第N个字符的左边距
        if ($this->useArithmetic) {
            $this->fontttf = $ttfPath . '6.ttf';
            $code          = [mt_rand(1, 9), '+', mt_rand(1, 9)];
            foreach ($code as $key => $char) {
                $codeNX += $this->fontSize * 1.5;
                imagettftext($this->im, $this->fontSize, 0, $codeNX, $this->fontSize * 1.6, $this->color, $this->fontttf, $char);
            }
            $code = $this->authcode($code[0] + $code[2]);
        } else {
            // 验证码使用随机字体
            if (empty($this->fontttf)) {
                $dir  = dir($ttfPath);
                $ttfs = [];
                while (false !== ($file = $dir->read())) {
                    if ('.' != $file[0] && substr($file, -4) == '.ttf') {
                        $ttfs[] = $file;
                    }
                }
                $dir->close();
                $this->fontttf = $ttfs[array_rand($ttfs)];
            }
            $this->fontttf = $ttfPath . $this->fontttf;
            for ($i = 0; $i < $this->length; $i++) {
                $code[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
                $codeNX   += mt_rand($this->fontSize * 1.2, $this->fontSize * 1.6);
                imagettftext($this->im, $this->fontSize, mt_rand(-15, 15), $codeNX, $this->fontSize * 1.55, $this->color, $this->fontttf, $code[$i]);
            }
            $code = $this->authcode(strtoupper(implode('', $code)));
        }

        // 保存验证码
        $key = $this->authcode($this->seKey);

        $secode                = [];
        $secode['verify_code'] = $code; // 把校验码保存到session
        $secode['verify_time'] = time(); // 验证码创建时间
        Session::set($key . $id, $secode);

        ob_start();
        // 输出图像
        imagepng($this->im);
        $content = ob_get_clean();
        imagedestroy($this->im);

        header("Content-Type:image/png");
        header("Content-Length:".strlen($content));
        exit($content);
    }

    /**
     * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数)
     *
     *      高中的数学公式咋都忘了涅，写出来
     *        正弦型函数解析式：y=Asin(ωx+φ)+b
     *      各常数值对函数图像的影响：
     *        A：决定峰值（即纵向拉伸压缩的倍数）
     *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
     *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
     *        ω：决定周期（最小正周期T=2π/∣ω∣）
     *
     */
    private function writeCurve()
    {
        $px = $py = 0;

        // 曲线前部分
        $A = mt_rand(1, $this->imageH / 2); // 振幅
        $b = mt_rand(-$this->imageH / 4, $this->imageH / 4); // Y轴方向偏移量
        $f = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T = mt_rand($this->imageH, $this->imageW * 2); // 周期
        $w = (2 * M_PI) / $T;

        $px1 = 0; // 曲线横坐标起始位置
        $px2 = mt_rand($this->imageW / 2, $this->imageW * 0.8); // 曲线横坐标结束位置

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i  = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->im, $px + $i, $py + $i, $this->color); // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多
                    $i--;
                }
            }
        }

        // 曲线后部分
        $A   = mt_rand(1, $this->imageH / 2); // 振幅
        $f   = mt_rand(-$this->imageH / 4, $this->imageH / 4); // X轴方向偏移量
        $T   = mt_rand($this->imageH, $this->imageW * 2); // 周期
        $w   = (2 * M_PI) / $T;
        $b   = $py - $A * sin($w * $px + $f) - $this->imageH / 2;
        $px1 = $px2;
        $px2 = $this->imageW;

        for ($px = $px1; $px <= $px2; $px = $px + 1) {
            if (0 != $w) {
                $py = $A * sin($w * $px + $f) + $b + $this->imageH / 2; // y = Asin(ωx+φ) + b
                $i  = (int)($this->fontSize / 5);
                while ($i > 0) {
                    imagesetpixel($this->im, $px + $i, $py + $i, $this->color);
                    $i--;
                }
            }
        }
    }

    /**
     * 画杂点
     * 往图片上写不同颜色的字母或数字
     */
    private function writeNoise()
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        for ($i = 0; $i < 10; $i++) {
            //杂点颜色
            $noiseColor = imagecolorallocate($this->im, mt_rand(150, 225), mt_rand(150, 225), mt_rand(150, 225));
            for ($j = 0; $j < 5; $j++) {
                // 绘杂点
                imagestring($this->im, 5, mt_rand(-10, $this->imageW), mt_rand(-10, $this->imageH), $codeSet[mt_rand(0, strlen($codeSet) - 1)], $noiseColor);
            }
        }
    }


    /* 加密验证码 */
    private function authcode($str)
    {
        $key = substr(md5($this->seKey), 5, 8);
        $str = substr(md5($str), 8, 10);
        return md5($key . $str);
    }
}
