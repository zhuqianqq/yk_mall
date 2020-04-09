var WSTHook_beforeStatCartMoney = [], WSTHook_beforeStatGoodsMoney = []
var edit_state = 0 // 编辑状态 默认0 是非编辑 1是编辑
//编辑
const EDIT = 0 // 编辑
const COMPLETE = 1 // 完成
$(document).ready(function () {
  initChecked()

  // 初始化检验是否勾选中
  function initChecked() {
    $('.ui-container .wst-ca-s').each(function (index, item) {
      var carts = $(item).find('.goods .ui-icon-chooseg')
      if (carts.length > 0) {
        var shopId = $(carts[0]).data('shop-id')
        console.log('log=>', shopId)
        checkCartChecked(shopId)
      }
    })
  }

  // $('body').text(JSON.stringify(WST.ua))

  if (WST.ua.weixin) {
    $('.hj-cart-header').show()
  } else {
    $('.hj-cart-header').hide()
    $('.hj-carts-container').css({ 'border': 0 })
  }

  if (parseInt($('#pageId').val()) == 0) {
    WST.initFooter('cart')
  } else {
    WST.selectCustomMenuPage('cart')
  }
  WST.imgAdapt('j-imgAdapt')

  statCartMoney()
  //选中店铺
  $('.ui-icon-chooses').click(function () {
    WST.changeIconStatus($(this), 1)
    var childrenId = $(this).attr('childrenId')
    var goodsCount = $('.' + childrenId).length//商品个数
    var ids = []
    if ($(this).attr('class').indexOf('wst-active') == -1) {
      WST.changeIconStatus($('.' + childrenId), 2)//选中
      for (var i = 0; i < goodsCount; i++) {
        var cid = $('.' + childrenId).eq(i).attr('cartId')
        ids.push(cid)
      }
      WST.batchChangeCartGoods(ids.join(','), 0)
    } else {
      WST.changeIconStatus($('.' + childrenId), 2, 'wst-active')//取消选中
      for (var i = 0; i < goodsCount; i++) {
        var cid = $('.' + childrenId).eq(i).attr('cartId')
        ids.push(cid)
      }
      WST.batchChangeCartGoods(ids.join(','), 1)
    }
    checkShopChecked()
    statCartMoney()
  })

  // 选中店铺的时候  检验 全选是否勾选
  function checkShopChecked() {
    var activeLength = $('.ui-container .wst-ca-s .shop .wst-active').length
    var allLength = $('.ui-container .wst-ca-s').length
    var $all = $('.ui-footer .ui-icon-choose')
    if (activeLength === allLength) {
      WST.changeIconStatus($all, 1)//选中
    } else {
      WST.changeIconStatus($all, 2)//取消选中
    }
  }

  //选中商品
  $('.ui-icon-chooseg').click(function () {
    const isNoGoods = $(this).parent().parent().hasClass('nogoods')

    if ($(this).attr('class').indexOf('wst-active') == -1) {
      var checked = 1
      WST.changeIconStatus($(this), 1)//选中
    } else {
      var checked = 0
      WST.changeIconStatus($(this), 2)//取消选中
    }
    if (!isNoGoods) {
      checkCartChecked($(this).data('shop-id'))
    }
    var cid = $(this).attr('cartId')
    if (cid != '') {
      WST.changeCartGoods(cid, $('#buyNum_' + cid).val(), checked)
      statCartMoney()
    }
  })

  // 选中商品的时候 检验 店铺是否勾选
  function checkCartChecked(shopId) {
    var activeLength = $('.wst-ca-s-' + shopId + ' .goods .wst-active').length
    var allLength = $('.wst-ca-s-' + shopId + ' .goods').length
    var $shop = $('.wst-ca-s-' + shopId + ' .shop .ui-icon-chooses')
    if (activeLength === allLength) {
      WST.changeIconStatus($shop, 1)//选中
    } else {
      WST.changeIconStatus($shop, 2)//取消选中
    }
    checkShopChecked()
  }

  //选中合计
  $('.ui-icon-choose').click(function () {
    WST.changeIconStatus($(this), 1)
    var shopIconCount = $('.ui-icon-chooses').length//店铺个数
    var goodsCount = $('.ui-icon-chooseg').length//商品个数
    var ids = []
    if ($(this).attr('class').indexOf('wst-active') == -1) {
      //选中所有
      for (var i = 0; i < shopIconCount; i++) {
        WST.changeIconStatus($('.ui-icon-chooses').eq(i), 2)
      }
      for (var i = 0; i < goodsCount; i++) {
        WST.changeIconStatus($('.ui-icon-chooseg').eq(i), 2)
        var cid = $('.ui-icon-chooseg').eq(i).attr('cartId')
        ids.push(cid)
      }
      WST.batchChangeCartGoods(ids.join(','), 0)
    } else {
      //取消选中所有
      for (var i = 0; i < shopIconCount; i++) {
        WST.changeIconStatus($('.ui-icon-chooses').eq(i), 2, 'wst-active')
      }
      for (var i = 0; i < goodsCount; i++) {
        WST.changeIconStatus($('.ui-icon-chooseg').eq(i), 2, 'wst-active')
        var cid = $('.ui-icon-chooseg').eq(i).attr('cartId')
        ids.push(cid)
      }
      WST.batchChangeCartGoods(ids.join(','), 1)
    }
    statCartMoney()
  })
})

//合计
function statCartMoney() {
  var cartMoney = 0, goodsTotalPrice, id
  $('.wst-active').each(function () {
    id = $(this).attr('cartId')
    if (WSTHook_beforeStatGoodsMoney.length > 0) {
      for (var i = 0; i < WSTHook_beforeStatGoodsMoney.length; i++) {
        delete window['callback_' + WSTHook_beforeStatGoodsMoney[i]]
        window[WSTHook_beforeStatGoodsMoney[i]](id)
        if (window['callback_' + WSTHook_beforeStatGoodsMoney[i]]) {
          window['callback_' + WSTHook_beforeStatGoodsMoney[i]]()
          return
        }
      }
    }
    goodsTotalPrice = parseFloat($(this).attr('mval')) * parseInt($('#buyNum_' + id).val())
    cartMoney = cartMoney + goodsTotalPrice
  })
  var minusMoney = 0
  for (var i = 1; i < $('#totalshop').val(); i++) {
    var shopMoney = 0, goodsTotalPrice2
    $('.clist' + i).each(function () {
      id = $(this).attr('cartId')
      goodsTotalPrice2 = parseFloat($(this).attr('mval')) * parseInt($('#buyNum_' + id).val())
      shopMoney = shopMoney + goodsTotalPrice2
    })
    //满就送减免
    if (WSTHook_beforeStatCartMoney.length > 0) {
      for (var hkey = 0; hkey < WSTHook_beforeStatCartMoney.length; hkey++) {
        delete window['callback_' + WSTHook_beforeStatCartMoney[hkey]]
        minusMoney = window[WSTHook_beforeStatCartMoney[hkey]](i)
        if (window['callback_' + WSTHook_beforeStatCartMoney[hkey]]) {
          window['callback_' + WSTHook_beforeStatCartMoney[hkey]]()
          return
        }
        shopMoney = shopMoney - minusMoney
        cartMoney = cartMoney - minusMoney
      }
    }
    $('#tprice_' + i).html('<span>¥ </span>' + shopMoney.toFixed(2))
  }
  $('#totalMoney').html('<span>¥ </span>' + cartMoney.toFixed(2))
  if (edit_state === EDIT) {
    checkGoodsBuyStatus()
  }
}

function checkGoodsBuyStatus() {
  var cartNum = 0, stockNum = 0, cartId = 0
  $('.wst-active').each(function () {
    cartId = $(this).attr('cartId')
    cartNum = parseInt($('#buyNum_' + cartId).val(), 10)
    stockNum = parseInt($('#buyNum_' + cartId).attr('data-max'), 10)
    if (stockNum < 0 || stockNum < cartNum) {
      if (stockNum < 0) {
        msg = '库存不足'
      } else {
        msg = '购买量超过库存'
      }
      // $('#noprompt' + cartId).show().html(msg)
      $(this).parent().parent().addClass('nogoods')
      WST.changeIconStatus($(this), 2)//取消选中
      WST.changeCartGoods(cartId, $('#buyNum_' + cartId).val(), 0)
      statCartMoney()
    } else {
      $('#noprompt' + cartId).hide().html('')
      $(this).parent().parent().removeClass('nogoods')
    }
  })
}

function edit(type) {
  if (type == EDIT) {
    WST.showHide('', '#edit,#settlement,#total')

    WST.showHide(1, '#complete,#delete,#favorite')
  } else {
    WST.showHide('', '#complete,#delete,#favorite')
    WST.showHide(1, '#edit,#settlement,#total')
  }
  $('.ui-container .ui-icon-chooses,.ui-container .ui-icon-chooseg,.ui-footer .ui-icon-choose').removeClass('ui-icon-success-block wst-active').addClass('ui-icon-unchecked-s');
  if(type === EDIT){
    edit_state = COMPLETE
  }else{
    // statCartMoney()
    edit_state = EDIT
  }
  // edit_state = type === EDIT ? COMPLETE : EDIT
}

//删除
function deletes() {
  var goodsIds = ''
  var goodsIconCount = $('.ui-icon-chooseg').length//商品个数
  for (var i = 0; i < goodsIconCount; i++) {
    if ($('.ui-icon-chooseg').eq(i).attr('class').indexOf('wst-active') != -1) {
      goodsIds += $('.ui-icon-chooseg').eq(i).attr('cartId') + ','
    }
  }
  if (goodsIds != '') {
    WST.dialog('确定删除选中的商品吗？', 'del("' + goodsIds + '")')
  } else {
    WST.msg('请选择要删除的商品', 'info')
  }
}

function del(goodsIds) {
  $.post(WST.U('mobile/carts/delCart'), { id: goodsIds }, function (data, textStatus) {
    var json = WST.toJson(data)
    if (json.status == 1) {
      WST.msg(json.msg, 'success')
      WST.dialogHide('prompt')
      setTimeout(function () {
        $bridge.cartsEditToggle('complete')
        var href = location.origin + WST.U('mobile/carts/index') + '?is_full_screen=1'
        location.href = href
      }, 500)
    } else {
      WST.msg(json.msg, 'warn')
    }
  })
}

// 移入我的关注
function toFavorites() {
  var cartIds = ''
  var goodsIds = ''
  var goodsIconCount = $('.ui-icon-chooseg').length//商品个数
  var selectGoods = 0
  for (var i = 0; i < goodsIconCount; i++) {
    if ($('.ui-icon-chooseg').eq(i).attr('class').indexOf('wst-active') != -1) {
      selectGoods++
      cartIds += $('.ui-icon-chooseg').eq(i).attr('cartId') + ','
      goodsIds += $('.ui-icon-chooseg').eq(i).attr('goodsId') + ','
    }
  }
  if (goodsIds != '') {
    WST.dialog('确定要将这' + selectGoods + '种商品移入我的关注吗？', 'moveToFavorites("' + goodsIds + '","' + cartIds + '")')
  } else {
    WST.msg('请选择要移入关注的商品', 'info')
  }
}

function moveToFavorites(goodsIds, cartIds) {
  $.post(WST.U('mobile/carts/moveToFavorites'), { goodsIds: goodsIds, cartIds: cartIds }, function (data, textStatus) {
    var json = WST.toJson(data)
    if (json.status == 1) {
      WST.msg(json.msg, 'success')
      WST.dialogHide('prompt')
      setTimeout(function () {
        location.href = WST.U('mobile/carts/index')
      }, 2000)
    } else {
      WST.msg(json.msg, 'warn')
    }
  })
}

//结算
function toSettlement() {
  var goodsIconCount = $('.ui-icon-chooseg').length//商品个数
  var noGoodsSelected = true
  for (var i = 0; i < goodsIconCount; i++) {
    if ($('.ui-icon-chooseg').eq(i).attr('class').indexOf('wst-active') != -1) {
      noGoodsSelected = false
    }
  }
  if (noGoodsSelected) {
    WST.msg('请勾选要结算的商品', 'info')
    return false
  }
  var isFullScreen = WST.getUrlParams().is_full_screen
  console.log(isFullScreen)
  var href = WST.U('mobile/carts/settlement')
  if (isFullScreen === '1') { // 是需要全屏打开
    $bridge.openScreenPage({
      link: location.origin + href + '?' + WST.getSearch(),
      title: '商品详情'
    })
  } else {
    location.href = href
  }
}

//导航
function inMore() {
  if ($('#arrow').css('display') == 'none') {
    $('#arrow').show()
    $('#layer').show()
  } else {
    $('#arrow').hide()
    $('#layer').hide()
  }
}

// 接受客户端方法
$receive.cartsHandle(edit)
