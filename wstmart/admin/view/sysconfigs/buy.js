var layer = layui.layer;
$(function(){
	form = layui.form;
  	form.render();
	form.on('switch(isOpenScorePay)', function(data){
	  	if(this.checked){
	  		WST.showHide(1,'#scoreToMoneyTr')
	  	}else{
	  		WST.showHide(0,'#scoreToMoneyTr')
	  	}
	});
	form.on('switch(isOrderScore)', function(data){
	  	if(this.checked){
	  		WST.showHide(1,'#moneyToScoreTr')
	  	}else{
	  		WST.showHide(0,'#moneyToScoreTr')
	  	}
	});
	form.on('switch(isAppraisesScore)', function(data){
	  	if(this.checked){
	  		WST.showHide(1,'#appraisesScoreTr')
	  	}else{
	  		WST.showHide(0,'#appraisesScoreTr')
	  	}
	});
});

function edit(){
	if(!WST.GRANT.GWSZ_02)return;
	var params = WST.getParams('.ipt');
	var loading = WST.msg('正在保存数据，请稍后...', {icon: 16,time:60000});
    $.post(WST.U('admin/sysconfigs/editBuyConfig'),params,function(data,textStatus){
          layer.close(loading);
          var json = WST.toAdminJson(data);
          if(json.status==1){
        	  WST.msg(json.msg,{icon:1});
          }
   });
}