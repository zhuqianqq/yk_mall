<?php
/**
 * 自定义扩展分页操作类
 */
namespace util;

class PagerHelper
{
    /**
     * 静态代理
     * @var
     */
    protected static $instance;

    /**
     * 请求体
     * @var
     */
    protected $request;

    /**
     * 总记录数
     * @var
     */
    protected $total;

    /**
     * 总页数
     * @var
     */
    protected $pageNum;

    /**
     * 当前页
     * @var
     */
    protected $page;

    /**
     * 每页显示条数
     * @var
     */
    protected $limit;

    /**
     * 页面初始化跳转链接
     * @var
     */
    protected $entranceUrl;

    // 初始化
    private function __construct($options)
    {
        $this->request = request();
        $controller = $this->request->controller();
        $this->entranceUrl = $this->request->baseFile() . '/' . $controller . "/" . $this->request->action();

        $this->total = $options['total'];
        $this->limit = $options['limit'];
        $this->pageNum = (int)ceil($this->total / $this->limit);
        $this->page = intval($this->request->param('page', 1));
    }

    /**
     * 获取外部实列
     * @param array $options
     * @return static
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    // 禁止克隆
    private function __clone()
    {
    }

    /**
     * 计算上一页
     * @param int $page 分页page参数
     * @return int
     */
    public function prePage($page)
    {
        if ($page > 1) {
            return $page - 1;
        } else {
            return 1;
        }
    }

    /**
     * 计算下一页
     * @param int $page 分页page参数
     * @param int $pageNum 总页数  总记录/每页显示条数
     * @return mixed
     */
    protected function nextPage($page, $pageNum)
    {
        if ($page < $pageNum) {
            return $page + 1;
        } else {
            return $pageNum;
        }
    }

    /**
     * 分页显示输出
     * @param int $page 分页page参数
     * @param int $pageNum 总页数  总记录/每页显示条数
     * @param null $query 分页额外参数 array|string array = $this->request->param()  string = "/keywords/{$keywords}"
     * @return string
     */
    public function render($page, $pageNum, $query = null)
    {
        if ($pageNum > 0) {
            $html = "<div class='page'>";
            if (empty($query)) {
                $query = '';
                if ($page > 1) {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}?page=1'>首页</a>&nbsp;&nbsp;";
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}?page=" . $this->prePage($page) . "'>上一页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a href='javascript:;' class='cp-button cp-disabled'>首页</a>&nbsp;&nbsp;";
                    $html .= "<a href='javascript:;' class='cp-button cp-disabled'>上一页</a>&nbsp;&nbsp;";
                }

                if ($page < $pageNum && $page >= 1) {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}?page=" . $this->nextPage($page, $pageNum) . "'>下一页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a class='cp-button cp-disabled' href='javascript:;'>下一页</a>&nbsp;&nbsp;";
                }

                if ($page == $pageNum) {
                    $html .= "<a class='cp-button cp-disabled' href='javascript:;'>尾页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}?page=" . $pageNum . "'>尾页</a>&nbsp;&nbsp;";
                }
            } else {
                if (is_array($query)) {
                    if (isset($query['page'])) unset($query['page']);
                    if ($query) {
                        $page_html = '&';
                        foreach ($query as $k => $v) {
                            if ($v) {
                                $page_html .= $k . '=' . $v . '&';
                            }
                        }
                        if (strlen($page_html) > 1) {
                            $page_html = substr($page_html, 0, -1);
                        }
                        $query = $page_html;
                    } else {
                        $query = '';
                    }
                }
                if ($page > 1) {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}" . "?page=1" . $query . "'>首页</a>&nbsp;&nbsp;";
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}" . '?page=' . $this->prePage($page) . $query . "'>上一页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a href='javascript:;' class='cp-button cp-disabled'>首页</a>&nbsp;&nbsp;";
                    $html .= "<a href='javascript:;' class='cp-button cp-disabled'>上一页</a>&nbsp;&nbsp;";
                }

                if ($page < $pageNum && $page >= 1) {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}" . '?page=' . $this->nextPage($page, $pageNum) . $query . "'>下一页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a class='cp-button cp-disabled' href='javascript:;'>下一页</a>&nbsp;&nbsp;";
                }

                if ($page == $pageNum) {
                    $html .= "<a class='cp-button cp-disabled' href='javascript:;'>尾页</a>&nbsp;&nbsp;";
                } else {
                    $html .= "<a class='cp-button' href='{$this->entranceUrl}" . '?page=' . $pageNum . $query . "'>尾页</a>&nbsp;&nbsp;&nbsp;&nbsp;";
                }
            }

            $html .= "第" . '<span class="cp-page-index">' . $page . '</span>' . "页/共" . '<span class="cp-page-num">' . $pageNum . '</span>' . "页&nbsp;&nbsp;&nbsp;";
            $html .= "共" . '<span class="cp-page-total">' . $this->total . '</span>' . "条记录&nbsp;&nbsp;&nbsp;";
            $html .= "跳转到&nbsp;&nbsp;&nbsp;<input name='page' id='cp_page' min=1 max=" . $pageNum . " style='width:45px;' type='number'/>&nbsp;&nbsp;&nbsp;页&nbsp;&nbsp;&nbsp;<button onclick='jump();' class='cp-jump' style='padding:1px 5px;'>跳转</button>";
            $html .= '</div>';
            $str = "<script>
				function jump()
				{
					var p = document.getElementById('cp_page').value;
					if(p>0 && p <=" . $this->pageNum . ")
					{
						location.href ='{$this->entranceUrl}?page='+p+'{$query}';
					} else {
					    var _html = '查询数据共' + $pageNum + '页,无法跳转到第' + p + '页';
					    alert(_html);
					}
				}
			</script>";
            return $html . $str;
        } else {
            return '';
        }
    }
}