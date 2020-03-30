String.prototype.addQueryParams = function(params) {
    var split = '?';
    if (this.indexOf('?') > -1) {
        split = '&';
    }
    var queryParams = '';
    for(var i in params) {
        queryParams += i + '=' + params[i] + '&';
    }
    queryParams = queryParams.substr(0, queryParams.length -1)
    return this + split + queryParams;
};
/**
 * 基础类库
 */
(function(){
    var Util = {
        ajaxURL:{}, //防止重复提交
        ajax:function(options){
            var url = options.url;
            if(this.ajaxURL[url]){
                layer.msg("请不要重复提交", {icon: 2});
                return;
            }
            options = options || {};
            options.type = options.type || "GET";
            options.dataType = options.dataType || "json";
            if(options.data){
                //options.data = this.appendCsrfToken(options.data);
            }
            if(!options.error){
                options.error = jQuery.proxy(this.ajaxError,this);
            }
            var comp = options.complete || null;
            var self = this;
            options.complete = function(xhr,textStatus){
                delete self.ajaxURL[url];
                if(options.loadingIndex){
                    self.hideLoading(options.loadingIndex);
                }
                if(typeof comp == "function"){
                    comp(xhr,textStatus);
                }
                options = null;
            };
            options.cache = false; //不缓存
            jQuery.ajax(options);
            this.ajaxURL[url] = true;
            if(options.loading !== false){
                options.loadingIndex = this.showLoading();
            }
        },
        //get请求
        get:function(url,data,success,error){
            this.ajax({
                type:"GET",
                url:url,
                data:data,
                success:success,
                error:error
            });
        },
        //post请求
        post:function(url,data,success,error){
            this.ajax({
                type:"POST",
                url:url,
                data:data,
                success:success,
                error:error
            });
        },
        ajaxError:function(xhr,textStatus,errorThrown){
            this.errorTip("异步请求出错：" + textStatus + "，" + xhr.status + " " + xhr.statusText);
        },
        appendCsrfToken:function(data){
            var csrf_param = $("meta[name='csrf-param']").attr("content") || "_csrf";
            var csrf = $("meta[name='csrf-token']").attr("content") || $("#_csrf").val() || "";
            if(csrf != ""){
                if(typeof data === "string"){
                    data += "&" + csrf_param + "=" + csrf;
                }else if(typeof data === "object"){
                    data[csrf_param] = csrf;
                }
            }
            return data;
        },
        showMsg:function(msg){
            layer.msg(msg);
        },
        successTip:function(msg,end){
            msg = msg || "操作成功";
            layer.msg(msg, {icon: 6},end);
        },
        errorTip:function(msg,end){
            msg = msg || "操作失败";
            layer.msg(msg, {icon: 5},end);
        },
        showLoading:function(msg,time){
            if(typeof time == "undefined" || time === null){
                time = 1000*1000; //等待1000秒
            }
            var index = layer.load(2,{shade: [0.2, '#393D49'],time:time});
            if(msg){
                jQuery(".layui-layer-content").html(msg).css({"width":"auto","paddingLeft":"36px","lineHeight":"32px"});
            }
            return index;
        },
        hideLoading:function(loadingIndex){
            if(loadingIndex){
                layer.close(loadingIndex);
            }else{
                layer.closeAll('loading');
            }
        },
        refreshPage:function(time){
            time = time || 1000;
            var _this = this;
            setTimeout(function(){
                Util.showLoading("加载中...");
                window.location.reload();
            },time);
        },
        iframe:function(url,title,options){
            var options = $.extend({
                maxmin:true,
                area:["800px","580px"]
            },options);
            options["type"] = 2;
            options["content"] = url;
            options["title"] = title;
            layer.open(options);
        },
        confirm:function(msg,yes,cancel,options){
            var def_opt = {"icon":3,title:'提示',area:"320px",btn:['确定','取消'],success:function(layobj){
                layobj.find('.layui-layer-btn0').focus();
            }};
            options = options || {};
            var opt = $.extend(def_opt,options);
            return layer.confirm(msg,opt,yes,cancel);
        },
        closeIframe:function(){
            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            parent.layer.close(index); //再执行关闭
        },
        //全屏iframe
        layerFull:function(title,url){
            if (url.indexOf("/") > 0 && typeof HHOME != "undefined") {
                url = HHOME + '/' + url;
            }
            var index = layer.open({
                type: 2,
                title: title,
                content: url
            });
            layer.full(index); //最大化iframe
        },
        /**
         * 获取URL参数值
         * @param {string} name 参数名
         * @returns {string}
         */
        getParam:function(name){
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            if (r != null){
                return decodeURIComponent(r[2]);
            }
            return "";
        },
        /**
         * alert弹窗
         * @param msg
         * @param ok 确定按钮回调
         * @param close 关闭按钮回调
         */
        alert:function(msg,ok,close){
            if(typeof msg == "string"){
                msg = msg.replace(/\n/gm,"<br/>");
            }
            if(typeof close == "function"){
                layer.alert(msg,{"icon":0,"title":"提示","cancel":close,success:function(layobj){
                    layobj.find('.layui-layer-btn0').focus();
                }},ok);
            }else{
                layer.alert(msg,{"icon":0,"title":"提示",success:function(layobj){
                    layobj.find('.layui-layer-btn0').focus();
                }},ok);
            }

        },
        getCookie:function(name){
            var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
            if (arr != null){
                return decodeURIComponent(arr[2]);
            }
            return null;
        },
        delCookie:function(){
            if(this.getCookie(name)){
                document.cookie = name + "=" + ";expires=Thu, 01-Jan-1970 00:00:01 GMT";
            }
        },
        setCookie:function(name,value,expires,path,domain,secure){
            var today = new Date();
            if (expires){
                expires = expires * 24 * 60 * 60 * 1000; //过期天数
            }
            var expires_date = new Date(today.getTime() + expires);
            document.cookie = name + '=' + encodeURIComponent(value)
                + (expires ? ';expires=' + expires_date.toGMTString() : '')
                + (path ? ';path=' + path : '')
                + (domain ? ';domain=' + domain : '')
                + (secure ? ';secure' : '');
        },
        filterXss:function(str){
            if(!str){
                return str;
            }
            return str.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&apos;").replace(/"/g,'&#x22;');
        },
        isEmail:function(email){
            return /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(email);
        },
        isMobile:function (mobile) {
            return /^1\d{10}$/.test(mobile);
        },
        //是否为正整数
        isDigit:function(val){
            return /^\d+$/.test(val);
        },
        //是否为大于0的数字，允许2位小数
        isNumeric:function(val){
            var reg =/^(([1-9]{1}\d*)|([0]{1}))(\.(\d){1,2})?$/;
            return reg.test(val);
        },
        //显示放大图片
        showImagePop:function (e,max_w,max_h) {
            var img_url = e.target.getAttribute("big_img") || e.target.getAttribute("src") || "";
            if(!img_url){
                return;
            }
            var img_pop = document.getElementById("img_pop");
            if(!img_pop){
                img_pop = document.createElement("img");
                img_pop.setAttribute("id","img_pop");
                img_pop.className = 'img-pop';
                document.body.appendChild(img_pop);
            }
            max_w = max_w || 260; //弹层宽度与高度
            max_h = max_h || 470;
            img_pop.setAttribute("src",img_url);
            var pop_w = Math.min(img_pop.width,max_w),
                pop_h = Math.min(img_pop.height,max_h);
            var offset_w = document.documentElement.offsetWidth,
                offset_h = document.documentElement.offsetHeight;
            var left = e.pageX + 10,
                top = e.pageY - 20;
            if((left + pop_w) > offset_w){
                left = e.pageX - pop_w - 5;
            }
            if((top + pop_h) > offset_h){
                top = offset_h - pop_h;
            }
            img_pop.style.left = left + "px";
            img_pop.style.top = top + "px";
            img_pop.style.display = "block";
        },
        hideImagePop:function () {
            var img_pop = document.getElementById("img_pop");
            if(img_pop){
                img_pop.style.display = "none";
            }
        },
        stopPropagation:function (e) {
            var e = e || window.event;
            e && e.stopPropagation();
        },
        doExport:function (btn,max) {
            var max = max || 5000; //最大导出条数
            Util.confirm("一次最大导出记录条数为" + max + "条，如果超出该记录条数，请先缩小查询条件再导出，是否继续？",function(index){
                $("#export").val(1);
                btn.form.target = "_blank";
                setTimeout(function(){
                    $("#export").val(0);
                    btn.form.target = '';
                },1000);
                btn.form.submit();
                layer.close(index);
            });
        },
        showBigImg:function(img_url){
            var html = '<div style="text-align:center;"><img src="' + img_url + '" style="width:100%;max-height:700px;" /></div>';
            layer.open({
                type: 1,
                title: false,
                closeBtn: 1,
                area: '800px',
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content:html
            });
        },
        showBigVideo:function(video_url){
            var html = '<video style="max-width:100%;max-height:650px;" controls="true" autoplay="true">'
                     +   '<source src="' + video_url + '" type="video/mp4">'
                     + '</video>';
            layer.open({
                type: 1,
                title: "视频播放",
                closeBtn: 1,
                offset: '80px',//只定义top坐标，水平保持居中
                area: '800px',
                content:html
            });
        },
        //显示二维码弹层
        showQrCode:function(url,width,height){
            var html = '<div id="qrcode" style="text-align: center;" onclick="layer.closeAll();"></div>';
            var w = width || 375,
                h = height || 375;
            var makeQrCode = function () {
                layer.open({
                    type: 1,
                    title: false,
                    closeBtn: false,
                    offset: '150px',
                    shadeClose: true,
                    area:[w + 5 + "px",h + 5 + "px"],
                    content:html,
                    success:function(){
                        var qrcode = new QRCode(document.getElementById("qrcode"), {
                            width : w,
                            height : h
                        });
                        qrcode.makeCode(url);
                    }
                });
            };
            if(typeof QRCode == "undefined"){
                jQuery.getScript("/static/admin/js/qrcode.js", function(){
                    makeQrCode();
                });
            }else{
                makeQrCode();
            }
        }
    };
    window.Util = Util;
    if(typeof String.prototype.trim != "function"){
        String.prototype.trim = function(){
            return this.replace(/(^\s*)|(\s*$)/g,"");
        };
    }
    $("form.search-form").submit(function(){
        Util.showLoading("提交中...",10 * 2000);
        $(this).find("button[type='submit']").prop("disabled",true);
    });
})();