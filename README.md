这个插件是完全免费的，是由Foxupay APP提供，他的优势就是

1.不需要搭建额外的USDT插件服务器，收款无任何手续费

2.地址交易检测速度快，不会漏单

3.部署简单，即使你不懂编程知识也可以操作完成

4.自由选择到账钱包地址

有人问，Foxupay免费提供的目的是什么，靠什么盈利，很简单，foxupay有一块业务是波场链的能量租赁
这个大家应该知道，USDT转账是需要燃烧TRX的，用租用的能量替代的话，可以减少交易手续费85%以上
我们是通过租用能量盈利，大家如果用得到的话，可以去用我们的能量租用平台租能量
这是一个双赢的生意，当然不管你照不照顾我们生意，这个插件也是可以正常免费使用的
下面是正题，如何安装

如果您已经安装了独角数卡平台，可以使用下面的方法快速安装插件 进行使用
第一步：复制以下代码粘贴加到独角数卡的 dujiaoka\routes\common\pay.php文件的未尾

// foxupay

Route::get('foxupay/{payway}/{orderSN}', 'FoxupayController@gateway');

Route::post('foxupay/notify_url', 'FoxupayController@notifyUrl');

Route::get('foxupay/return_url', 'FoxupayController@returnUrl')->name('foxupay-return');



第二步：将文件FoxupayController.php文件复制到目录dujiaoka\app\Http\Controllers\Pay内

第三步：在独角数卡后台菜单 【配置】-【支付配置】-右上角新增，按照图片 dujiaoka-foxupay.jpg 所示进行填写
（填写之前需要先下载Foxupay APP，进行注册，获取需要填写的参数
APP下载链接https://foxupay.com/foxupay-app-download/）
以上信息都可以在代码中获得
