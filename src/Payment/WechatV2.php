<?php

namespace Shiwei\Tools\Payment;

use Shiwei\Tools\Request;
use Shiwei\Tools\Format;
use Shiwei\Tools\Helper;

class WechatV2
{
    private $access_path = 'https://api.mch.weixin.qq.com';
    private $appid;
    private $secret;
    private $mchid;
    private $mch_secret;

    public function __construct(array $configs = [])
    {
        $this->__init__($configs);
    }

    private function __init__(array $configs = []): void
    {
        $this->appid      = $configs['appid']      ?? '';
        $this->secret     = $configs['secret']     ?? '';
        $this->mchid      = $configs['mchid']      ?? '';
        $this->mch_secret = $configs['mch_secret'] ?? '';
    }

    /**
     *  生成签名
     */
    private function __sign__(array $sign_data, string $sign_key): string
    {
        ksort($sign_data);

        $string = '';
        if (!empty($sign_data)) {
            $array = [];
            foreach ($sign_data as $key => $param) {
                $array[] = $key . '=' . $param;
            }
            
            $string = implode('&', $array);
        }

        $string .= '&key=' . $sign_key;

        $result = strtoupper(md5($string));

        return $result;
    }

    /**
     *  验证微信回调签名
     *  @param array $body 微信返回数据
     *  @return void
     */
    public function wxNotifyVerify(array $body = []): void
    {
        if (empty($body)) throw new \Exception('未接收到参数');

        if (!array_key_exists('return_code', $body) && !array_key_exists('result_code', $body)) {
            throw new \Exception('接收参数错误');
        }

        if ($body['return_code'] !== 'SUCCESS' || $body['result_code'] !== 'SUCCESS') {
            throw new \Exception('接收参数错误');
        }
        
        $sign = $body['sign'];
        unset($receive_data['sign']);

        if ($this->__sign__($body, $this->mch_secret) !== $sign) throw new \Exception('签名失败');
    }

    /**
     *  请求微信接口
     *  @param string $url
     *  @param string $method
     *  @param array  $data
     *  @param array  $headers
     *  @throws \Exception
     *  @return array
     */
    public function request(string $url, string $method, array $data = [], array $headers = [])
    {
        $sign = $this->__sign__($data, $this->mch_secret);
        $data['sign'] = $sign;

        // $post_xml = Format::arrayToXml($data);

        $request_url = $this->access_path . $url;

        $result = Request::fetch($request_url, $method, $data, $headers, 'xml', 'xml');

        // $result = Format::xmlToArray($result);

        if ($result['return_code'] !== 'SUCCESS') throw new \Exception($result['return_msg']);

        if ($result['result_code'] !== 'SUCCESS') throw new \Exception($result['err_code'] . ':' . $result['err_code_des']);

        return $result;
    }
    
    /**
     *  获取微信回调数据
     *  @throws \Exception
     *  @return array
     */
    public function getNotifyData()
    {
        $receive_data = file_get_contents('php://input');
        $receive_data = Format::xmlToArray($receive_data);

        // $this->wxNotifyVerify($receive_data);

        return $receive_data;
    }

    /**
     *  获取支付数据
     *  @param mixed $pay_result
     *  @throws \Exception
     *  @return array
     */
    public function __get_pay_data__(array $pay_result = []): array
    {
        $prepay_id = $pay_result['prepay_id'] ?? '';

        if (empty($prepay_id)) throw new \Exception('缺少prepay_id');

        $time = time();
        $nonce_str = Helper::createNonceStr();

        $temp_array = [
            'appId'     => $this->appid,
            'nonceStr'  => $nonce_str,
            'package'   => 'prepay_id=' . $prepay_id,
            'signType'  => 'MD5',
            'timeStamp' => (string)$time
        ];

        return [
            'timeStamp'    => (string)$time,
            'nonceStr'     => $nonce_str,
            'package'      => 'prepay_id=' . $prepay_id,
            'signType'     => 'MD5',
            'paySign'      => $this->__sign__($temp_array, $this->mch_secret),
        ];
    }
}
