<?php
namespace wstmart\mobile\controller;
/**
 * 分享控制器
 */
class Share extends Base{
	public function download()
    {
        return $this->fetch('download');
    }
}
