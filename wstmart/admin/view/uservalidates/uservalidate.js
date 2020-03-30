var mmg;
function initGrid(p){
    var h = WST.pageHeight();
    var cols = [
            {title:'真实姓名', name:'true_name', width: 90,sortable: true},
            {title:'主播ID', name:'user_id' ,width:40,sortable: true},
            {title:'性别', name:'sex' ,width:60,sortable: true, renderer:function(val,item,rowIndex){
                return (item['sex']==1)?"【男】":"【女】";
            }},
            {title:'正面照', name:'id_card_positive', width: 20, renderer: function(val,item,rowIndex){
                var thumb = item['id_card_positive'];
                return "<img class='img' onclick=toolTip('" + thumb + "')  style='height:50px;width:50px;' data-src='"+thumb
                    +"' src='"+thumb+"' />";
            }},
            {title:'反面照', name:'id_card_back', width: 20, renderer: function(val,item,rowIndex){
                var thumb = item['id_card_back'];
                return "<img class='img' onclick=toolTip('" + thumb + "')  style='height:50px;width:50px;' data-src='"+thumb
                    +"' src='"+thumb+"' />";
            }},
            {title:'身份证号', name:'id_card' ,width:60,sortable: true},
            {title:'手机号', name:'phone' ,width:40,sortable: true},
            {title:'认证时间', name:'create_time',sortable: true ,width:60},
            {title:'状态', name:'status' ,width:60,sortable: true, renderer:function(val,item,rowIndex){
                return (val==2)?"<span class='statu-yes'><i class='fa fa-check-circle'></i> 认证成功</span>":((val==3)?"<span class='statu-no'><i class='fa fa-ban'></i> 认证失败&nbsp;</span>":"<span class='statu-wait'><i class='fa fa-clock-o'></i> 待处理&nbsp;</span>");
            }},
            {title:'操作', name:'' ,width:120, align:'center', renderer: function(val,item,rowIndex){
                var h = "";
	            h += "<a class='btn btn-blue' href='javascript:toView(" + item['validateId'] + ")'><i class='fa fa-search'></i>查看</a> ";
	            if((item['status']==0 || item['status']==3) && WST.GRANT.TXSQ_04)h += "<a class='btn btn-green' href='javascript:toEdit(" + item['validateId'] + ")'><i class='fa fa-pencil'></i>处理</a> ";
	            return h;
            }}
            ];

    mmg = $('.mmg').mmGrid({height: h-182,indexCol: true,indexColWidth:50, cols: cols,method:'POST',nowrap:true,
        url: WST.U('admin/uservalidates/pageQuery'), fullWidthRows: true, autoLoad: false,remoteSort: true,sortName:'createTime',sortStatus:'desc',
        remoteSort:true ,
        sortName: 'validateId',
        sortStatus: 'desc',
        plugins: [
            $('#pg').mmPaginator({})
        ]
    });
    $('#headTip').WSTTips({width:90,height:35,callback:function(v){
         var diff = v?182:137;
         mmg.resize({height:h-diff})
    }});
    loadGrid(p)
}

function toEdit(id){
	location.href=WST.U('admin/uservalidates/toHandle','id='+id+'&p='+WST_CURR_PAGE);
}
function toView(id){
	location.href=WST.U('admin/uservalidates/toView','id='+id+'&p='+WST_CURR_PAGE);
}
function loadGrid(p){
    p=(p<=1)?1:p;
	mmg.load({page:p,id_card:$('#id_card').val(),phone:$('#phone').val(),user_id:$('#user_id').val()});
}

function save(p){
	var params = WST.getParams('.ipt');
	if(typeof(params.status)=='undefined'){
		WST.msg('请选择认证结果',{icon:2});
		return;
	}
	if(params.status==3 && $.trim(params.remark)==''){
		WST.msg('输入认证失败原因',{icon:2});
		return;
	}
	if(WST.confirm({content:'您确定该提现认证'+((params.status==2)?'成功':'失败')+'吗？',yes:function(){
		var loading = WST.msg('正在提交数据，请稍后...', {icon: 16,time:60000});
	    $.post(WST.U('admin/uservalidates/handle'),params,function(data,textStatus){
	    	layer.close(loading);
	    	var json = WST.toAdminJson(data);
	    	if(json.status=='1'){
	    		WST.msg("操作成功",{icon:1});
	    		location.href=WST.U('admin/uservalidates/index','p='+p);
	    	}else{
	    		WST.msg(json.msg,{icon:2});
	    	}
	    });
	}}));
}

function toolTip(curImgUrl){
    windowImg(curImgUrl);
}

function showImg(type) {
    if (type == 1) {
        // 正面
        var img_pos = $("#img-positvie").val()
    } else {
        // 反面
        var img_pos = $("#img-back").val()
    }
    toolTip(img_pos)
}

function windowImg(imgUrl) {
    $('#window-img').empty();
    var img = "<img class='window-img' src='"+ imgUrl +"' width='100%' />";
    $('#window-img').append(img);

    layer.open({
        type: 1,
        title: false,
        //resize:false,
        shadeClose: true,//点击遮罩关闭
        content:$('#window-img'),
        area: ['1000px', '800px'],
        maxWidth: 800,
        cancel: function () {
            $('#window-img').css('display', 'none');
        },
        end:function () {
            $('#window-img').css('display', 'none');
        }
    });
}
