function loadStat(){
    var loading = WST.load({msg:'正在查询数据，请稍后...'});
	$.post(WST.U('shop/reports/getStatOrders'),WST.getParams('.j-ipt'),function(data,textStatus){
	    layer.close(loading);
	    var json = WST.toJson(data);
	    var myChart = echarts.init(document.getElementById('main'));
	    myChart.clear();
	    $('#mainTable').addClass('hide');
	    if(json.status=='1' && json.data){
			var option = {
			    tooltip : {
			        trigger: 'axis'
			    },
			    toolbox: {
			        show : true,
			        y: 'top',
			        feature : {
			            mark : {show: true},
			            dataView : {show: false, readOnly: false},
			            magicType : {show: true, type: ['line', 'bar', 'tiled']},
			            restore : {show: true},
			            saveAsImage : {show: true}
			        }
			    },
			    calculable : true,
			    legend: {
			        data:['待付款订单','取消订单','拒收订单','正常订单','总订单']
			    },
			    xAxis : [
			        {
			            type : 'category',
			            splitLine : {show : false},
			            data : json.data.days
			        }
			    ],
			    yAxis : [
			        {
			            type : 'value',
			            position: 'right'
			        }
			    ],
			    series : [
			        {
			            name:'待付款订单',
			            type:'line',
			            stack: '类型',
			            data:json.data['-2']
			        },
			        {
			            name:'取消订单',
			            type:'line',
			            stack: '类型',
			            data:json.data['-1']
			        },
			        {
			            name:'拒收订单',
			            type:'line',
			            stack: '类型',
			            data:json.data['-3']
			        },
			        {
			            name:'正常订单',
			            type:'line',
			            stack: '类型',
			            data:json.data['1']
			        },
			        {
			            name:'总订单',
			            type:'line',
			            data:json.data['total']
			        },
			        {
			            name:'订单类型细分',
			            type:'pie',
			            tooltip : {
			                trigger: 'item',
			                formatter: '{a} <br/>{b} : {c} ({d}%)'
			            },
			            center: [160,130],
			            radius : [0, 50],
			            itemStyle :　{
			                normal : {
			                    labelLine : {
			                        length : 20
			                    }
			                }
			            },
			            data:[
			                {value:json.data.map['-1'], name:'取消订单'},
			                {value:json.data.map['-3'], name:'拒收订单'},
			                {value:json.data.map['1'], name:'正常订单'},
			            ]
			        }
			    ]
			};  
			myChart.setOption(option);
			var gettpl = document.getElementById('stat-tblist').innerHTML;
			laytpl(gettpl).render(json.data.list, function(html){
	       		$('#list-box').html(html);
	       		$('#mainTable').removeClass('hide');
		    });
	    }else{
	    	WST.msg('没有查询到记录',{icon:5});
	    }
	}); 
}

function toExport(){
    var params = WST.getParams('.j-ipt');
    var box = WST.confirm({content:"您确定要导出该统计数据吗?",yes:function(){
        layer.close(box);
        location.href=WST.U('shop/reports/toExportStatOrders',params);
    }});
}