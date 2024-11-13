### 我的工具类
###### 封装常用函数以及数据处理算法

1. Helper
   - 引入类 Shiwei\Tools\Helper
```
    getMicroTime:   获取13位微妙时间戳
    getAllProducts: 获取笛卡尔乘积函数
    getIndexTree:   根据指定key将数组转为索引函数
```
1. Format
   - 引入类 Shiwei\Tools\Format
```
    objectToArray:  将对象转为数组，常用于框架查询数据为对象
    arrayToTree:    将数组转为tree结构，可以指定数据id和pid
    timeToString:   将时间戳转为通用时间类型
    timeToElapsed:  将时间转为距离现在的时长 例如 x小时前
    timeToShow:     将时间转为可用显示时间   例如 昨天上午10:00
    strToPoint:     将字符串分段放入数组对应索引中，常用于tcp协议查询对应字节数据
```
2. Redis
   - 引入类 Shiwei\Tools\Redis
```
    set:            key -> value 类型数据存储
    get:            key -> value 类型数据获取key的值
    stringDel:      key -> value 类型数据删除
    hashSet:        hash表数据保存
    hashGet:        hash表数据获取
    hashDel:        hash表数据删除
    setSet:         集合类型数据保存
    setGet:         集合类型数据获取
    setDel:         集合类型数据删除
    setRem:         集合类型数据清空
    listPush:       列表push
    listPop:        列表pop
```
3. Curl
    - 引入类 Shiwei\Tools\Request\Curl
```
    fetch:          发送curl请求获取数据
```