//密码登录
function login(){
    var public_key=$('#token').val();
    var exponent="10001";
    var res = '';
    if(WST.conf.IS_CRYPT=='1'){
   	    var rsa = new RSAKey();
        rsa.setPublic(public_key, exponent);
        var res = rsa.encrypt($.trim($('#loginPwd').val()));
    }else{
        res = $.trim($('#loginPwd').val());
    }
    var loading = WST.msg('加载中', {icon: 16,time:60000,offset: '200px'});
	var params = WST.getParams('.ipt');
	params.typ = 2;
	params.loginPwd = res;

	$.post(WST.U('shop/index/checkLogin'),params,function(data,textStatus){
		layer.close(loading);
		var json = WST.toJson(data);
		if(json.status=='1'){
			WST.msg("登录成功",{icon:1,offset: '200px'},function(){
				if(parent){
					parent.location.href=WST.U('shop/index/index');;
				}else{
                    location.href=WST.U('shop/index/index');
				}
			});
		}else{
			getVerify('#verifyImg');
			WST.msg(json.msg,{icon:2,offset: '200px'});			
		}
	});
}
var getVerify = function(img){
	$(img).attr('src',WST.U('shop/index/getVerify','rnd='+Math.random()));
	$('#verifyCode').val('');
}
$(document).keypress(function(e) {
	if(e.which == 13 || e.keyCode == 13) {
		loginByPhone();
	} 
});
var errorTip = function(msg){
	layer.msg(msg,{anim: 6});
};

//验证码登录
function loginByPhone(){
	var loginName = $.trim($("#loginName").val());
	if(loginName == ''){
		errorTip("用户名或手机号不能为空");
		return false;
	}
	var mobileCode = $.trim($("#verifyCode").val());
	if(mobileCode == ''){
		errorTip("验证码不能为空");
		return false;
	}
	var data = {
		typ:2, //商家登录
		loginNamea:loginName,
		mobileCode:mobileCode
	};
	var url = WST.U("shop/index/checkLoginByPhone");
	Util.ajax({
		method:"POST",
		url:url,
		data:data,
		dataType : "json",
		loading:true,
		success:function(res){
			if(res.status == '1'){
				WST.msg("登录成功",{icon:1,offset: '200px'},function(){
					if(parent){
						parent.location.href = WST.U('shop/index/index');
					}else{
						location.href = WST.U('shop/index/index');
					}
				});
			}else{
				WST.msg(res.msg,{icon:2,offset: '200px'});
			}
		}
	});
}

var count_time = 60;
$("#btnGetVcode").click(function(e){
	e.preventDefault();
	e.stopPropagation();
	var obj = $(this);
	var userPhone = $.trim($("#loginName").val());
	if(userPhone == ''){
		errorTip("手机号不能为空");
		return false;
	}
	var url = WST.U("shop/index/sendSmsCode");
	Util.ajax({
		url:url,
		method:"POST",
		data:{userPhone:userPhone},
		dataType : "json",
		loading:true,
		success:function(res){
			if(res.code == 0){
				Util.successTip(res.msg);
				setCountTime(obj);
			}else{
				errorTip(res.msg);
			}
		}
	});
});

function setCountTime(obj) { //发送验证码倒计时
	if (count_time == 0) {
		obj.prop('disabled',false).removeClass("disabled");
		obj.html("获取验证码");
		count_time = 60;
		obj = null;
		return;
	} else {
		obj.prop('disabled',true).addClass("disabled");
		obj.html("重新发送(" + count_time + ")");
		count_time --;
	}
	setTimeout(function(){setCountTime(obj)},1000);
}