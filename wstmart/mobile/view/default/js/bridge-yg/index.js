var userAgent = navigator.userAgent
var ua = (function () {
  var regs = {
    // 系统
    // 'ios': /iphone|ipad|ipod/,
    'android': /android/i,

    // 机型
    'iphone': /iphone/i,
    'ipad': /ipad/i,
    'ipod': /ipod/i,

    // 环境
    'weixin': /micromessenger/i,
    'mqq': /QQ\//i,
    'app': /inke/i,
    'alipay': /aliapp/i,
    'weibo': /weibo/i,

    // 浏览器
    'chrome': /chrome\//i
  }

  var ret = {}
  Object.keys(regs).forEach((key) => {
    var reg = regs[key]
    ret[key] = reg.test(userAgent)
  })

  ret.ios = ret.iphone || ret.ipad || ret.ipod
  ret.mobile = ret.ios || ret.android
  ret.pc = !ret.mobile

  ret.chrome = !!window.chrome

  return ret
})()

var CLOSE = 'close'
var SHARE = 'share'
var FULL_SCREEN = 'full_screen'
var UPLOAD = 'upload'
var CARTS_EDIT_TOGGLE = 'carts_edit_toggle' // 购物车编辑完成切换 {"name":"carts_edit_toggle","data":12}
var ORDER_DETAIL = 'order_detail'
var LOGISTICS_FORM = 'logistics_form'
var CONTACT_US = 'contact_us'
var WX_APP_PAY = 'wx_app_pay'
var android = ua.android
var ios = ua.ios

// 调用映购app的端上方法
var bridgeFunc = function (params) {
  var name = params.name
  var data = params.data
  if (ios && (!window.webkit || !window.webkit.messageHandlers || !window.webkit.messageHandlers.iOSFunctionMananger)) {
    return console.error('ios端内打开必须注入window.webkit.messageHandlers.iOSFunctionMananger')
  }
  if (android && (!window.bc || !window.bc.bridgeFunction)) {
    return console.error('android端内打开必须注入window.bc.bridgeFunction')
  }
  if (typeof name === 'string' && name) {
    try {
      if (android) {
        var msg = JSON.stringify({
          type: name,
          data: data
        })
        window.bc.bridgeFunction(msg)
      } else if (ios) {
        window.webkit.messageHandlers.iOSFunctionMananger.postMessage({ name: name, data: data })
      }
    } catch (e) {
      console.error(e)
    }
  } else {
    console.error('bridge必须指定name')
  }
}
// share的例子
// export var defaultShareOpt = {
//   title: '映购',
//   desc: '映购',
//   link: 'https://www.yingtaorelian.com/',
//   imgUrl: 'https://img.ikstatic.cn/MTU4MzMyODkwODUzNiM1MTQjcG5n.png'
// 'https://img.ikstatic.cn/MTU4MzQ5MDc0OTc5NSM0MDcjcG5n.png'
// }

var $bridge = {
  close: function (data) {
    return bridgeFunc({
      name: CLOSE
    })
  },
  share: function (data) {
    return bridgeFunc({
      name: SHARE,
      data: data
    })
  },
  openScreenPage: function (data) { // data{link:'',title:''}
    return bridgeFunc({
      name: FULL_SCREEN,
      data: data
    })
  },
  // 切换购物车状态
  cartsEditToggle: function (data) { // data:"complete" or "edit"
    return bridgeFunc({
      name: CARTS_EDIT_TOGGLE,
      data: data
    })
  },
  openUpload: function (data) { // "data":{"length":3}
    return bridgeFunc({
      name: UPLOAD,
      data: data
    })
  },
  //订单管理页面  获取订单详情
  OrderDetail:function (data) { 
    return bridgeFunc({
      name: ORDER_DETAIL,
      data: data
    })
  },
  //物流信息表
  LogisticsForm:function (data) { 
    return bridgeFunc({
      name: LOGISTICS_FORM,
      data: data
    })
  },
  //联系客服
  ContactUs:function (data) { 
    return bridgeFunc({
      name: CONTACT_US,
      data: data
    })
  },
  //微信APP支付
  WXAppPay:function (data) { 
    return bridgeFunc({
      name: WX_APP_PAY,
      data: data
    })
  },

  
}
