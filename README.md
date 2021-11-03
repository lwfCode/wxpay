#  PHP-WeChatPay

微信支付类 


## 安装

>composer require lwfdeveloper/wxpay

## 示例
```php
$options = [
    'appid' => 'you wechat appid',
    'key' => 'you key',
    'mch_id' => 'you wechat mch_id'
];

$weChatPay = \lwfdeveloper\wxpay\WeChatPay::getInstance($options);//实例化单例类

/** 订单号*/
$orderId = rand(000000, 999999) . date('YmdHisu') . rand(000000, 999999); 
$openid = 'create wechat pay openid';
$reason = "create wechat pay order";
/** 支付金额*/
$amount = 100;
$callbackUrl = "http://api.develop.cn/api/wechat/callback";

#H5，微信小程序内发起支付
$response = $weChatPay->CreateWeChatOrder($orderId, $reason, $amount, $openid, $callbackUrl);
var_dump($response);

#App内发起微信支付
$responseApp = $weChatPay->CreateWeChatAppOrder($orderId, $reason, $amount, $callbackUrl);
var_dump($responseApp);


```

