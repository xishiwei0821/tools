<?php

namespace Shiwei\Tools\Payment;

use Shiwei\Tools\Request;
use Shiwei\Tools\Helper;

class WechatV3
{
    // 请求地址
    private $access_path = 'https://api.mch.weixin.qq.com';
	// appid
    private $appid = '';
	// appsecret
    private $secret = '';
	// 商户号
    private $mchid = '';
	// 商户证书
    private $mch_cert = '';
    // 商户密钥
    private $mch_secret = '';
	// 证书序列号
    private $serial_no = '';

    /**
     *  构造函数
     *  @param array $configs 配置
     */
    public function __construct(array $configs = [])
    {
        $this->__init__($configs);
    }

    /**
     *  初始化
     *  @param array $configs 配置
     */
    private function __init__(array $configs = []): void
    {
        $this->appid      = $configs['appid']      ?? '';
        $this->secret     = $configs['secret']     ?? '';
        $this->mchid      = $configs['mchid']      ?? '';
        $this->mch_cert   = $configs['mch_cert']   ?? '';
        $this->mch_secret = $configs['mch_secret'] ?? '';
        $this->serial_no  = $configs['serial_no']  ?? '';
    }

    /**
     *  获取证书
     *  @throws \Exception
     */
    private function getPrivateKey()
    {
        $pem_path = $this->mch_cert;

        if (empty($pem_path)) throw new \Exception('缺少证书配置');

        if (!file_exists($pem_path)) throw new \Exception('证书文件不存在');

        return openssl_get_privatekey(file_get_contents($pem_path));
    }

    /**
     *  获取Authorization
     *  @param string $uri 请求路由
     *  @param string $method 请求方法
     *  @param array  $data 请求参数 get请求，参数拼接到uri
     *  @return string
     */
    private function getAuth(string $uri, string $method = 'GET', array $data = [])
    {
        if (!in_array('sha256WithRSAEncryption', openssl_get_md_methods(true))) throw new \Exception('当前环境不支持sha256WithRSA');

        $body = strtoupper($method) == 'GET' ? '' : json_encode($data, JSON_UNESCAPED_UNICODE);

        $timestamp = time();

        $nonce = Helper::createNonceStr(32);

        $sign_code = sprintf("%s\n%s\n%s\n%s\n%s\n", $method, $uri, $timestamp, $nonce, $body);

        openssl_sign($sign_code, $raw_sign, $this->getPrivateKey(), 'sha256WithRSAEncryption');

        $sign = base64_encode($raw_sign);

        $schema = "WECHATPAY2-SHA256-RSA2048";

        $data = [
            'mchid'     => $this->mchid,
            'nonce_str' => $nonce,
            'serial_no' => $this->serial_no,
            'timestamp' => $timestamp,
            'signature' => $sign
        ];

        $arrayString = '';
        foreach ($data as $key => $item) $arrayString .= $key . "=\"" . $item . "\",";

        return sprintf("%s %s", $schema, rtrim($arrayString, ','));
    }

    /**
     *  验证签名
     */
    public function verifySign(array $body = []): void
    {
        $header = getallheaders();

        // 密文信息
        $ciphertext = $body['data'][0]['encrypt_certificate']['ciphertext'] ?? '';
        if (empty($ciphertext)) throw new \Exception('未获取到密文信息');

        $ciphertext = base64_decode($ciphertext);

        // 额外信息
        $associated_data = $body['data'][0]['encrypt_certificate']['associated_data'] ?? '';
        if (empty($associated_data)) throw new \Exception('未获取到额外信息');

        // 解密字符串
        $nonce = $body['data'][0]['encrypt_certificate']['nonce'] ?? '';
        if (empty($nonce)) throw new \Exception('未获取到解密字符串');

        // 证书序列号
        if (!array_key_exists('Wechatpay-Serial', $header) || empty($header['Wechatpay-Serial'])) throw new \Exception('缺少Wechatpay-Serial证书');

        // 时间戳
        if (!array_key_exists('Wechatpay-Timestamp', $header) || empty($header['Wechatpay-Timestamp'])) throw new \Exception('缺少Wechatpay-Timestamp');

        // 随机字符串
        if (!array_key_exists('Wechatpay-Nonce', $header) || empty($header['Wechatpay-Nonce'])) throw new \Exception('缺少Wechatpay-Nonce');

        // 微信签名
        if (!array_key_exists('Wechatpay-Signature', $header) || empty($header['Wechatpay-Signature'])) throw new \Exception('缺少Wechatpay-Signature');

        // // 验证证书是否相同
        if ($header['Wechatpay-Serial'] != $this->serial_no) throw new \Exception('证书不匹配');

        // 时间戳5分钟内有效
        if ($header['Wechatpay-Timestamp'] > time() + 5 * 60) throw new \Exception('请求已过期');

        // 获取签名
        $sign = sprintf("%s\n%s\n%s\n", $header['Wechatpay-Timestamp'], $header['Wechatpay-Nonce'], $body);

        if (function_exists('sodium_crypto_aead_aes256gcm_is_available')) {
            sodium_crypto_aead_aes256gcm_is_available();
        }

        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            openssl_decrypt($ciphertext, $this->getPrivateKey(), 'aes-256-gcm', OPENSSL_RAW_DATA, $nonce, '', $associated_data);
        }
    }

    /**
     *  获取支付签名
     */
    private function __sign__(string $prepay_id, int $timestamp, string $nonce_str)
    {   
        $sign_code = $this->appid . "\n" . $timestamp . "\n" . $nonce_str . "\n" . 'prepay_id=' . $prepay_id . "\n";

        openssl_sign($sign_code, $raw_sign, $this->getPrivateKey(), 'sha256WithRSAEncryption');

        $sign = base64_encode($raw_sign);

        return $sign;
    }

    /**
     *  发起请求
     *  @param string $uri 请求路由
     *  @param string $method 请求方法
     *  @param array  $body 请求参数
     *  @param array  $header 额外请求头
     *  @throws \Exception
     *  @return array
     */
    public function request(string $uri, string $method = 'GET', array $body = [], array $header = []): array
    {
        $url = $this->access_path . $uri;

        $defaultHeader = [
            'Authorization: ' . $this->getAuth($uri, $method, $body),
            'Accept: application/json, text/plain, application/x-gzip',
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36'
        ];

        $header = array_merge($defaultHeader, $header);

        $result = Request::fetch($url, $method, $body, $header, 'json', 'json');

        if (array_key_exists('code', $result)) throw new \Exception($result['message'] ?? '请求失败');

        return $result;
    }

    /**
     *  获取回调数据
     */
    public function getNotifyData(): array
    {
        $body = file_get_contents("php://input");
        $body = json_decode($body, true);

        $this->verifySign($body);

        return $body;
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
        $nonce_str = Helper::createNonceStr(32);

        return [
            'timeStamp'    => (string)$time,
            'nonceStr'     => $nonce_str,
            'package'      => 'prepay_id=' . $prepay_id,
            'signType'     => 'RSA',
            'paySign'      => $this->__sign__($prepay_id, $time, $nonce_str)
        ];
    }
}
