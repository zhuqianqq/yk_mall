jQuery.noConflict();
var currStoreId = 0;
function onSwitch(obj,n){
	$(obj).children('.ui-icon-push').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	$(obj).siblings().children('.ui-icon-push').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
}
/* 选择是否需要发票 */
function isInvoice(obj,n){
	$(obj).children('.ui-icon-push').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	$(obj).siblings().children('.ui-icon-push').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
	$('#isInvoice').val(n);// 记录用户是否需要开发票
	$('#invoicesh').val(n);
}
/* 发票对象【个人or单位】 */
function invOnSwitch(obj,n){
	$(obj).children('.ui-icon-push').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	$(obj).siblings().children('.ui-icon-push').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
	if(n==1){
		$('.inv_hidebox').show();
	}else{
		$('.inv_hidebox').hide();
	}
	$('#invoice_obj').val(n);// 记录用户所开发票对象
}
/* 发票抬头列表绑定事件 */
$(function(){
	$('#special_invoice_head').focus(function(){
		$('#special_inv_headlist').show();
	})
	$('#special_invoice_head').blur(function(){
		setTimeout(function(){
			$('#special_inv_headlist').hide();
		},100)
	})

	$('#invoice_head').focus(function(){
		$('#inv_headlist').show();
	})
	$('#invoice_head').blur(function(){
		setTimeout(function(){
			$('#inv_headlist').hide();
		},100)
	})
	// 只要用户编辑了,就视为新增
	$('#invoice_head').bind('input propertychange', function() {
	    $('#invoiceId').val(0);
	});
})
/* 完成发票信息填写 */
function saveInvoice(){
	var param={};
	var invoiceId = $('#invoiceId').val();// 发票id
	param.invoiceCode = $('#invoice_code').val();// 纳税人识别码
	param.invoiceHead = $('#invoice_head').val();// 发票抬头
	var url = 'wechat/invoices/add';
	if(invoiceId>0){
		url = 'wechat/invoices/edit';
		param.id = invoiceId;
	}
	if($('#invoice_obj').val()!=0){
		$.post(WST.U(url),param,function(data){
			var json = WST.toJson(data);
			if(json.status==1){
				setInvoiceText();
				if(invoiceId==0)$('#invoiceId').val(json.data.id)
			}else{
				WST.msg(json.msg,'info');
			}
		})
	}else{
		setInvoiceText();
	}

}
// 设置页面显示值
function setInvoiceText(){
	var isInvoice  = $('#isInvoice').val();
	var invoiceObj = $('#invoice_obj').val();// 发票对象
	var invoiceHead = $('#invoice_head').val();// 发票抬头
	var invoiceType = $('#invoiceType').val();// 发票类型
	var text = '不开发票';
	var _taxText = invoiceType==1?'专用':'普通';
	if(isInvoice==1){
		text = (invoiceObj==0)?(_taxText+'发票（纸质）  个人   明细'):(_taxText+'发票（纸质）<br />'+invoiceHead+'<br />明细');
	}
	$('#invoicest').html(text);
	invoiceHide();
}
function inDetermine(n){
	$('#'+n+' .wst-active').each(function(){
		type = $(this).attr('mode');
		word = $(this).attr('word');
		if(n=='payments')payCode = $(this).attr('payCode');
	});
	if(n=='gives'){
		if(type==1){
			$('#storeId').val(currStoreId);
			word = $("#storbox .infot").html();
		}else{
			$('#storeId').val(0);
		}
	}
	$('#'+n+'h').val(type);
	$('#'+n+'t').html(word);
	if(n=='payments'){
		$('#'+n+'w').val(payCode);
	}
	getCartMoney();
	dataHide(n);
}

//计算价格
function getCartMoney(){
	var params = {};
	params.isUseScore = $('#scoreh').val();
	params.useScore = $('#userOrderScore').html();
	params.areaId2 = $('#areaId').val();
	params.deliverType = $('#givesh').val();
	var goodsType = $('#goodsType').val();
	WST.load('正在计算价格...');
	if(goodsType==0){
		$.post(WST.AU('groupon://carts/getCartMoney'),params,function(data,textStatus){
			WST.noload();
			var json = WST.toJson(data);
			if(json.status==1){
			    json = json.data;
		    	// 设置每间店铺的运费及总价格
		    	$('#shopF').html('¥'+json.shops['freight'].toFixed(2));
		    	$('#shopC').html('¥'+json.shops['goodsMoney'].toFixed(2));
			 	$('#totalMoney').html('¥'+json.realTotalMoney.toFixed(2));
			}
		});
	}else{//虚拟商品
		params.deliverType = 1;
		$.post(WST.AU('groupon://carts/getCartMoney'),params,function(data,textStatus){
			WST.noload();
			var json = WST.toJson(data);
			if(json.status==1){
			    json = json.data;
			 	$('#totalMoney').html('¥'+json.realTotalMoney.toFixed(2));
			}
		});
	}
}
//提交订单
function submitOrder(){
	var addressId =  $('#addressId').val();
	if($('#givesh').val()==0 && addressId==''){
		WST.msg('请选择收货地址','info');
		return false;
	}
	WST.load('提交中···');
    var param = WST.getParams('.j-ipt');
    param.s_addressId = addressId;
    param.s_areaId = $('#areaId').val();
    param.payType = $('#paymentsh').val();
    param.payCode = $('#paymentsw').val();
    param.isUseScore = $('#scoreh').val();
    param.useScore = $('#userOrderScore').html();
	$('.wst-se-sh .shopn').each(function(){
		shopId = $(this).attr('shopId');
	    param['remark_'+shopId] = $('#remark_'+shopId).val();
	});
    param.deliverType = $('#givesh').val();
    param.isInvoice = $('#isInvoice').val();
    param.invoiceId = $('#invoiceId').val();
    param.invoiceClient = $('#invoice_obj').val()==1?$('#invoice_head').val():'个人';
    param.orderSrc = 1;
    $('.wst-se-confirm .button').attr('disabled', 'disabled');
	$.post(WST.AU('groupon://carts/submit'),param,function(data,textStatus){
		var json = WST.toJson(data);
	    WST.noload();
	    if(json.status==1){
	    	WST.msg(json.msg,'success');
		      setTimeout(function(){
		    	  if(param.payType==1){
		    		  if(param.payCode=='weixinpays' || param.payCode==''){
			    		  location.href = WST.U('wechat/weixinpays/toPay',{"pkey":json.pkey});
		    		  }else if(param.payCode=='wallets'){
		    			  location.href = WST.U('wechat/wallets/payment',{"pkey":json.pkey});
		    		  }
		    	  }else{
		    		  location.href = WST.U('wechat/orders/index');
		    	  }
		      },1000);
	    }else{
	    	WST.msg(json.msg,'info');
	    	$('.wst-se-confirm .button').removeAttr('disabled');
	    }
	});
}
function addAddress(type,id){
	if(WST.conf.IS_LOGIN==0){
		WST.inLogin();
		return;
	}
	location.href = WST.AU('groupon://useraddress/index','type='+type+'&addressId='+id);
}
function goGoods(id){
    location.href=WST.AU('groupon://goods/wxdetail','id='+id);
}
var dataHeight = $(".frame").css('height');
	dataHeight = parseInt(dataHeight)+50+'px';
$(document).ready(function(){
	WST.imgAdapt('j-imgAdapt');
    $(".frame").css('bottom','-'+dataHeight);
});
//弹框
function dataShow(n){
	jQuery('#cover').attr("onclick","javascript:dataHide('"+n+"');").show();
	jQuery('#'+n).animate({"bottom": 0}, 500);
	//显示已保存的数据
	var type = $('#'+n+'h').val();
	if(n=='gives'){
		getStores();
	}
	if(type==0){
		jQuery('i[class*="'+n+'"]').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
		jQuery('.'+n+'0').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	}else{
		jQuery('i[class*="'+n+'"]').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
		jQuery('.'+n+'1').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	}
	if(n=='payments'){
		var payCode = $('#'+n+'w').val();
		jQuery('i[class*="'+n+'"]').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
		jQuery('.'+n+'_'+payCode).removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	}
	if(n=='invoices'){
		if(type==0){
			jQuery('#j-invoice').hide();
		}else{
			jQuery('#j-invoice').show();
		}
	}
}
function dataHide(n){
	jQuery('#'+n).animate({'bottom': '-'+dataHeight}, 500);
	jQuery('#cover').hide();
}
document.addEventListener('touchmove', function(event) {
    //判断条件,条件成立才阻止背景页面滚动,其他情况不会再影响到页面滚动
    if(!jQuery("#cover").is(":hidden")){
        event.preventDefault();
    }
})
/*********************** 发票信息层 ****************************/
function invTypeSwitch(obj, n){
	// 将选中的值置为空
	if(n!=$('#invoiceType').val()){
		// 将选中的值置为空
		$('.inv_head_input').val('');
		$('#invoice_code').val('');
	}
	$('#invoice_obj').val(n);
	$('#invoiceType').val(n);
	$(obj).children('.ui-icon-push').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	$(obj).siblings().children('.ui-icon-push').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
	if(n==0){
		$('#j-special-box').hide();
		$('#j-normal-box').show();
		$('#inv_personal').click();
		// 普通发票
		$('.j-normal').show();
		$('.j-special').hide();
	}else{
		$('#j-special-box').show();
		$('#j-normal-box').hide();
		// 专用发票
		$('.j-normal').hide();
		$('.j-special').show();
	}
}
//弹框
function invoiceShow(){
	var _type = $('#invoiceType').val();
	if(_type==0){
		// 普票
		if($('#invoice_obj').val()==0){
			$('#j-normal-btn').click();
		}else{
			// 普通发票
			$('.j-normal').show();
			$('.j-special').hide();
		}
	}else{
		// 专票
		$('#j-special-btn').click();
	}
	jQuery('#cover').attr("onclick","javascript:invoiceHide();").show();
	jQuery('#frame').animate({"bottom": 0}, 500);

}
function invoiceHide(){
	jQuery('#frame').animate({'bottom': '-100%'}, 500);
	jQuery('#cover').hide();
}



function getInvoiceList(){
	$.post(WST.U('wechat/invoices/pageQuery'),{},function(data){
		var json = WST.toJson(data);
		if(json.status!=-1){
			var gettpl1 = document.getElementById('invoiceBox').innerHTML;
			laytpl(gettpl1).render(json, function(html){
				$('.inv_list_item').html(html);
				invoiceShow();
				// 点击抬头item
				$('.inv_list_item li').click(function(){
					// 设置值
					$('.inv_head_input').val($(this).html());
					$('#invoice_head').val($(this).html());
					$('#invoiceId').val($(this).attr('invId'));
					$('#invoice_code').val($(this).attr('invCode'));
				})
			});
		}else{
			WST.msg(json.msg,'info');
		}
	});
}


//弹框
function dataShow2(title){
    jQuery('#store').animate({"right": 0}, 500);
}
function dataHide2(){
    jQuery('#store').animate({'right': '-100%'}, 500);
}
function onSwitch2(obj,val){
	$(obj).children('.ui-icon-push').removeClass('ui-icon-unchecked-s').addClass('ui-icon-checked-s wst-active');
	$(obj).siblings().children('.ui-icon-push').removeClass('ui-icon-checked-s wst-active').addClass('ui-icon-unchecked-s');
	if(val==1){
		$("#storbox").show();
	}else{
		$("#storbox").hide();
	}
}

function getStores(){
	var addressId =  $('#addressId').val();
	var shopId =  $('#shopId').val();
	$.post(WST.AU('groupon://carts/getShopStores'),{shopId:shopId,addressId:addressId},function(data,textStatus){
		var json = WST.toJson(data);
	    if(json.status==1){
	    	if(json.data && json.data.length>0){
	    		$("#deliver1").show();
	    		if($("#storeId").val()<=0){
	    			currStoreId = json.data[0].storeId;
	    			var gettpl = document.getElementById('tblist').innerHTML;
			       	laytpl(gettpl).render(json.data[0], function(html){
			       		$("#storbox").html(html);
			       	});
	    		}
	    		if($("#givesh").val()==1){
	    			$("#storbox").show();
	    		}else{
	    			$("#storbox").hide();
	    		}
	    		
	    		var gettpl = document.getElementById('tblist2').innerHTML;
		       	laytpl(gettpl).render(json.data, function(html){
		       		$("#storelist").html(html);
		       	});
	    	}else{
	    		$("#deliver1").hide();
	    		$("#storbox").hide();
	    		$("#storelist").html("<div>该区域内没有自提点,请选择快递运输</div>");
	    	}
	    }else{
	    	WST.msg(json.msg,{icon:2});
	    }
	});
}

function checkStore(obj,storeId){
	currStoreId = storeId;
	$(".store-item").removeClass("currchk");
	$(obj).addClass("currchk");
	$("#storbox").html('<li onclick="dataShow2()" class="li-store" style="margin: 0">'+$("#store-info"+storeId).html()+'</li>');
	dataHide2();
}