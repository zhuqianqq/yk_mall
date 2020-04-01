<?php
/**
 * 验证码类
 */
namespace util;

class ValidateCode
{
    public $charset;
    private $code;//验证码
    private $codelen = 4;//验证码长度
    private $width = 155;//宽度
    private $height = 46;//高度
    private $img;//图形资源句柄
    private $font;//指定的字体
    private $fontsize = 15;//指定字体大小
    private $fontcolor;//指定字体颜色

    /**
     * @var int 1 数字 2 英文 3 数字加英文 4 中文
     */
    private $codetype = 1;

    //构造方法初始化
    public function __construct($type)
    {
        $this->codetype = $type;
        if ($this->codetype == 4) {
            if (app()->lang->getLangSet() != 'zh-cn') {
                $this->codetype = 3;  //非中文语言屏蔽中文验证码
            } else {
                $this->font = dirname(__FILE__) . '/font/JhengHe.ttf';//中文字体
                $this->charset = "的一了是我不在人们有来他这上着个地到大里说就去子得也和那要下看天时过出小么起你都把好还多没为又可家学只以主会样年想生同老中十从自面前头道它后然走很像见两用她国动进成回什边作对开而己些现山民候经发工向事命给长水几义三声于高手知理眼志点心战二问但身方实吃做叫当住听革打呢真全才四已所敌之最光产情路分总条白话东席次亲如被花口放儿常气五第使写军吧文运再果怎定许快明行因别飞外树物活部门无往船望新带队先力完却站代员机更九您每风级跟笑啊孩万少直意夜比阶连车重便斗马哪化太指变社似士者干石满日决百原拿群究各六本思解立河村八难早论吗根共让相研今其书坐接应关信觉步反处记将千找争领或师结块跑谁草越字加脚紧爱等习阵怕月青半火法题建赶位唱海七女任件感准张团屋离色脸片科倒睛利世刚且由送切星导晚表够整认响雪流未场该并底深刻平伟忙提确近亮轻讲农古黑告界拉名呀土清阳照办史改历转画造嘴此治北必服雨穿内识验传业菜爬睡兴形量咱观苦体众通冲合破友度术饭公旁房极南枪读沙岁线野坚空收算至政城劳落钱特围弟胜教热展包歌类渐强数乡呼性音答哥际旧神座章帮啦受系令跳非何牛取入岸敢掉忽种装顶急林停息句区衣般报叶压慢叔背细";
            }
        }else{
            $this->font = dirname(__FILE__) . '/font/Elephant.ttf';//英文字体
            if ($this->codetype == 1) {
                $charset = "0123456789";
            }else if ($this->codetype == 2) {
                $charset = "ABCDEFGHIGKLMNOPQRSTUVWXYZ";
            }else if ($this->codetype == 3) {
                $charset = "0123456789ABCDEFGHIGKLMNOPQRSTUVWXYZ";
            }
            $this->charset = $charset;
        }
    }

    //生成随机码
    private function createCode()
    {
        $_len = mb_strlen($this->charset, "utf-8") - 1;
        $numlen = 0;
        if ($this->codetype == 3) {
            $numlen = mt_rand(1, $this->codelen - 1);
            for ($i = 0; $i < $numlen; $i++) {
                $this->code .= mt_rand(0, 9);
            }
        }
        for ($i = 0; $i < $this->codelen - $numlen; $i++) {
            $this->code .= mb_substr($this->charset, mt_rand(0, $_len), 1, "utf-8");
        }
        if ($this->codetype == 3) {
            $this->code = str_shuffle($this->code);//中文非单字节字符，会乱码
        }
    }

    //生成背景
    private function createBg()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->img, mt_rand(210, 255), mt_rand(210, 255), mt_rand(210, 255));
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
    }

    //生成文字
    private function createFont()
    {
        $_x = $this->width / $this->codelen;
        for ($i = 0; $i < $this->codelen; $i++) {
            $this->fontcolor = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            $x = $i == 0 ? 5 : $_x * $i + mt_rand(1, 5);
            imagettftext($this->img, $this->fontsize, mt_rand(-30, 30),$x , $this->height / 1.4, $this->fontcolor, $this->font, mb_substr($this->code, $i, 1, "utf-8"));
        }
    }

    //生成线条、雪花
    private function createLine()
    {
        //线条
        for ($i = 0; $i < 3; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        //雪花
        for ($i = 0; $i < 30; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
        }
    }

    //输出
    private function outPut()
    {
        ob_end_clean();
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }

    //对外生成
    public function doImg()
    {
        $this->createBg();
        $this->createCode();
        $this->createLine();
        $this->createFont();
        $this->outPut();
    }

    //获取验证码
    public function getCode()
    {
        if ($this->codetype == 4) {
            return $this->code;
        } else {
            return strtolower($this->code);
        }
    }
}