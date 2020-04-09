/*
* 接受客户端方法
* 模拟调用方法 浏览器内执行receiveMessage('{"type":"carts_handle","data":"xxxxx"}')
* */
//
const CARTS_HANDLE = 'carts_handle' // 控制购物车

let receives = {}
const $receive = {
  // 编辑完成 购物车
  cartsHandle(cb) {  // receiveMessage('{"type":"carts_handle","data":0}')  data可选： 编辑=>0 ，完成=>1
    receives[CARTS_HANDLE] = cb
  }
}

// H5接收端内调用的方法
window.receiveMessage = msg => {
  if (typeof msg === 'string') {
    try {
      const message = JSON.parse(msg)
      const { type, data } = message
      if (type) {
        return receives[type] ? receives[type](data) : void 0
      } else {
        console.error('receiveMessage=>返回数据格式错误')
      }
    } catch (e) {
      console.error(e)
    }
  } else {
    throw new Error('receiveMessage=>返回数据必须string类型')
  }
}
