<?php
namespace lwfdeveloper\wxpay;

use Gaoming13\HttpCurl\HttpCurl;

class WeChatPay
{
    //保存例实例在此属性中
    private static $_instance;

    // 商户配置
    protected $option = [
        'appid' => '',
        'key' => '',
        'mch_id' => ""
    ];


    public function __construct($appid = null, $key = null ,$mch_id = null)
    {
        if (empty($appid) || empty($key) || empty($mch_id)) {
            return false;
        }
        $this->option['appid'] = $appid;
        $this->option['key'] = $key;
        $this->option['mch_id'] = $mch_id;
    }


    /**
     * 静态方法，单例统一访问入口
     * @param $options
     * @return RsaCrypt
     */
    public static function getInstance($options = [])
    {
        if (is_null ( self::$_instance ) || isset ( self::$_instance )) {
            self::$_instance = new self ($option['appid'],$option['key'],$option['mch_id']);
        }
        return self::$_instance;
    }


    /**
     * 创建微信订单|支持H5，微信小程序
     *
     * @param [type] $trade_no
     * @param [type] $reason
     * @param [type] $openid
     * @param [type] $money
     * @param [type] $callback_url
     * @return void
     */
    public function CreateWeChatOrder($trade_no, $reason, $openid, $money, $callback_url = null)
    {
        $postArr = [
            'appid' => $this->option['appid'],
            'body' => $reason,
            'mch_id' => $this->option['mch_id'],
            'nonce_str' => md5($trade_no),
            'notify_url' => $callback_url,
            'openid' => $openid,
            'out_trade_no' => $trade_no,
            'spbill_create_ip' => $this->getClientIp(),
            'total_fee' => $money,
            'trade_type' => 'JSAPI',
            'spbill_create_ip' => $this->getClientIp()
        ];

        // 签名
        $postArr['sign'] = $this->sign($postArr);

        // 生成xml
        $postData = $this->buildXML($postArr);

        // 发送数据并解析
        list($body) = HttpCurl::request('https://api.mch.weixin.qq.com/pay/unifiedorder', 'POST', $postData);

        $xmlString = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        $response = json_decode(json_encode($xmlString), true);


        if ($response['return_code'] == 'SUCCESS') {
            $data = [
                'appId' => $this->option['appid'],
                'timeStamp' => strval(time()),
                'nonceStr' => md5($trade_no),
                'package' => 'prepay_id=' . $response['prepay_id'],
                'signType' => 'MD5'
            ];
            ksort($data);
            $dataQurey = urldecode(http_build_query($data)) . '&key=' . $this->option['key'];
            $data['paySign'] = strtoupper(md5($dataQurey));

            return $data;
        } else {
            return false;
        }
    }


    /**
     * 创建App微信订单
     *
     * @param [type] $trade_no
     * @param [type] $reason
     * @param [type] $money
     * @param [type] $callback_url
     * @return void
     */
    public function CreateWeChatAppOrder($trade_no, $reason, $money, $callback_url = null)
    {
        $postArr = [
            'appid' => $this->option['appid'],
            'body' => $reason,
            'mch_id' => $this->option['mch_id'],
            'nonce_str' => md5($trade_no),
            'notify_url' => $callback_url,
            'out_trade_no' => $trade_no,
            'spbill_create_ip' => $this->getClientIp(),
            'total_fee' => $money,
            'trade_type' => 'APP'
        ];

        // 签名
        $postArr['sign'] = $this->sign($postArr);

        // 生成xml
        $postData = $this->buildXML($postArr);

        // 发送数据并解析
        list($body) = HttpCurl::request('https://api.mch.weixin.qq.com/pay/unifiedorder', 'POST', $postData);

        $xmlString = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        $response = json_decode(json_encode($xmlString), true);

        if ($response['return_code'] == 'SUCCESS') {
            // 接收微信返回的数据，传给APP！
            $data = [
                'appid' => $this->option['appid'],
                'timestamp' => time(),
                'noncestr' => md5($trade_no),
                'package' => 'Sign=WXPay',
                'partnerid' => $this->option['mch_id'],
                'prepayid' => $response['prepay_id']
            ];
            ksort($data);
            $dataQurey = urldecode(http_build_query($data)) . '&key=' . $this->option['key'];
            $data['sign'] = strtoupper(md5($dataQurey));
            $data['trade_no'] = $trade_no;

            return $data;
        } else {
            return false;
            // return '订单创建失败：' . $response['return_msg'];
        }
    }


    /**
     * 制作XML
     *
     * @param array $data
     * @return string
     */
    protected function buildXML(array $data) : string
    {
        $result = '<xml>';
        foreach ($data as $key => $value) {
            $result = $result . "\r\n" . '<' . $key . '>' . $value . '</' . $key . '>';
        }
        return $result . '</xml>';
    }


    /**
     * 签名
     */
    public function sign($arr)
    {
        if(!empty($arr['sign'])){
            unset($arr['sign']);
        }
        ksort($arr);
        $query = urldecode(http_build_query($arr)) . '&key=' . $this->option['key'];
        return strtoupper(md5($query));
    }


    /**
     * 获取客户端IP地址
     * @return array|false|string
     */
    public function getClientIp()
    {
        $cip = 'unknown';

        if($_SERVER['REMOTE_ADDR']){
            $cip = $_SERVER['REMOTE_ADDR'];
        }elseif (getenv("REMOTE_ADDR")){
            $cip = getenv("REMOTE_ADDR");
        }

        return $cip;
    }


    /**
     * 私有克隆函数，防止外办克隆对象
     */
    private function __clone() {}
}