var grid;
$(function(){
    var laydate = layui.laydate;
    laydate.render({
        elem: '#startDate'
    });
    laydate.render({
        elem: '#endDate'
    });
})

function initRefundGrid(p){
    var h = WST.pageHeight();
    var cols = [
        {title:'订单编号', name:'orderNo',sortable: true, renderer: function(val,item,rowIndex){
            var h = "";
            h += "<a style='cursor:pointer' onclick='javascript:showDetail("+ item['orderId'] +");'>"+item['orderNo']+"</a>";
            return h;
        }},
        {title:'退款编号', name:'refundTradeNo',width:40,sortable: true},
        {title:'订单来源', name:'orderCodeTitle',width:40,sortable: true,hidden: true},
        {title:'配送方式', name:'deliverType',width:40,sortable: true,hidden: true},
        {title:'实收金额', name:'realTotalMoney', width:40,sortable: true,renderer: function(val,item,rowIndex){
            return "¥"+val;
        }},
        {title:'申请退款金额', name:'backMoney',width:70,sortable: true, renderer: function(val,item,rowIndex){
            return "¥"+val;
        }},
        {title:'申请时间', name:'createTime',sortable: true},
        {title:'支付来源', name:'refundTo', width:40,sortable: true,renderer: function(val,item,rowIndex){
            switch (item['refundTo']) {
                case 1:
                    return '微信';
                    break;
                case 2:
                    return '小程序';
                    break;
                case 3:
                    return '支付宝';
                default:
                    return '其他';
            }
        }},
        {title:'退款状态', name:'refundStatus', width:40,sortable: true,renderer: function(val,item,rowIndex){
            if(item['serviceId'] > 0)return (item['isServiceRefund']==1)?"已退款":"未退款";
            switch (item['refundStatus']) {
                case 2:
                    return '已退款';
                    break;
                case 1:
                    return '退款中';
                    break;
                case 3:
                    return '退款失败';
                    break;
                case 4:
                    return '退货退款同意';
                    break;
                case 7:
                    return '等待商家收货';
                    break;
                default:
                    return "未退款";
            }
        }},
        {title:'退款备注', name:'refundRemark',hidden: true},
        {title:'操作', name:'op' ,width:120, align:'center', renderer: function(val,item,rowIndex){
            var h = '';
            if((item['serviceId']==0 && (item['refundStatus'] == 1 || item['refundStatus'] == 4 || item['refundStatus'] == 7)) || (item['serviceId'] >0 && item['isServiceRefund']==0)){
                if (item['refundType'] == 1 && item['refundStatus'] == 1) {
                    h += "<a class='btn btn-blue' href='javascript:toRefund(" + item['refundId'] + ", "+ item['serviceId'] +")'><i class='fa fa-search'></i>同意退货退款</a> ";
                } else {
                    h += "<a class='btn btn-blue' href='javascript:toRefund(" + item['refundId'] + ", "+ item['serviceId'] +")'><i class='fa fa-search'></i>退款</a> ";
                }
            }
            h += "<a class='btn btn-blue' href='javascript:toView(" + item['orderId'] + ", "+ item['serviceId'] +")'><i class='fa fa-search'></i>详情</a> ";
            return h;
        }}
    ];

    mmg = $('.mmg').mmGrid({height: (h-90),indexCol: true, indexColWidth:50, cols: cols,method:'POST',
        url: WST.U('shop/orderrefunds/refundPageQuery'), fullWidthRows: true, autoLoad: false,nowrap:true,
        remoteSort:true ,
        sortName: 'createTime',
        sortStatus: 'desc',
        plugins: [
            $('#pg').mmPaginator({})
        ]
    });
    loadRefundGrid(p);
}
function loadRefundGrid(page){
    var p = WST.getParams('.j-ipt');
    page=(page<=1)?1:page;
    p.page = page;
    mmg.load(p);
}

function toView(id,sId){
	location.href=WST.U('shop/orders/view','id='+id+'&serviceId='+sId+'&src=orderrefunds&p='+WST_CURR_PAGE);
}
var w;
function toRefund(id,sId){
	var ll = WST.msg('正在加载信息，请稍候...');
	$.post(WST.U('shop/orderrefunds/toRefund',{id:id,serviceId:sId}),{},function(data){
		layer.close(ll);
		w =WST.open({type: 1,title:"订单退款",shade: [0.6, '#000'],offset:'50px',border: [0],content:data,area: ['650px', '550px']});
	});
}
function orderRefund(id){
	$('#editFrom').isValid(function(v){
		if(v){
        	var params = {};
        	params.content = $.trim($('#content').val());
        	params.id = id;
        	ll = WST.msg('正在处理数据，请稍候...');
		    $.post(WST.U('shop/orderrefunds/orderRefund'),params,function(data){
		    	layer.close();
                layer.close(w);
				if(data.status==1){
                    WST.msg(data.msg, {icon: 1,time:2500},function(){
                        loadRefundGrid(WST_CURR_PAGE);
                    });
				}else{
					WST.msg(data.msg, {icon: 2});
				}
                window.location.reload();
		   });
		}
    })
}
function showDetail(id){
    parent.showBox({title:'订单详情',type:2,content:WST.U('shop/orders/view',{id:id,from:1}),area: ['1020px', '500px'],btn:['关闭']});
}
function toExport(){
	var params = {};
	params = WST.getParams('.j-ipt');
	var box = WST.confirm({content:"您确定要导出订单吗?",yes:function(){
		layer.close(box);
		location.href=WST.U('shop/orderrefunds/toExport',params);
    }});
}