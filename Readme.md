## 工具类
#### 封装常用函数以及数据处理算法

- Helper

需要引入类 Shiwei\Tools\Helper

|方法名|调用方式|备注|
|--|--|--|
|createNonceStr|静态调用|创建随机字符串|
|createPathDir|静态调用|创建多级目录|
|getFileType|静态调用|获取本地文件mini_type类型|
|getMicroTime|静态调用|获取13位微妙时间戳|
|getAllProducts|静态调用|获取笛卡尔乘积函数|
|getIndexTree|静态调用|根据指定key将数组转为索引函数|
|getDateZone|静态调用|获取时间范围|

- Format

需要引入类 Shiwei\Tools\Format

|方法名|调用方式|备注|
|--|--|--|
|objectToArray|静态调用|将对象转为数组|
|arrayToTree|静态调用|将数组转为tree结构，可以指定数据id和pid|
|timeToString|静态调用|将时间戳转为通用时间类型|
|timeToElapsed|静态调用|将时间转为距离现在的时长 例如 x小时前|
|timeToShow|静态调用|将时间转为可用显示时间   例如 昨天上午10:00|
|strToPoint|静态调用|将字符串分段转数组|
|fileToBase64|静态调用|文件转base64字符串|
|xmlToArray|静态调用|xml转数组|
|arrayToXml|静态调用|数组转xml|

- Redis

需要引入类 Shiwei\Tools\Redis

|方法名|调用方式|备注|
|--|--|--|
|set|实例化|key -> value 类型数据存储|
|get|实例化|key -> value 类型数据获取key的值|
|del|实例化|key -> value 类型数据删除|
|hashSet|实例化|hash表数据保存|
|hashGet|实例化|hash表数据获取|
|hashDel|实例化|hash表数据删除|
|setSet|实例化|集合类型数据保存|
|setGet|实例化|集合类型数据获取|
|setDel|实例化|集合类型数据删除|
|setRem|实例化|集合类型数据清空|
|listPush|实例化|列表push|
|listPop|实例化|列表pop|

- Request

需要引入类 Shiwei\Tools\Request

|方法名|调用方式|备注|
|--|--|--|
|fetch|静态调用|发送curl请求获取数据|

- Excel

需要引入类 Shiwei\Tools\Excel

|方法名|调用方式|备注|
|--|--|--|
|number2column|静态调用|将索引转列名|
|column2number|静态调用|将列名转索引|
|setOptions|实例化|设置参数|
|getOptions|实例化|获取参数|
|read|实例化|全量读取数据|
|course_handle|实例化|逐行读取数据|
|write|实例化|根据设置格式写入数据|
