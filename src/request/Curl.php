<?php

declare(strict_types=1);

namespace Shiwei\Tools\Request;

/**
 *  curl请求
 *  @author ShiweiXi <xishiwei0821@gmail.com>
 */
class Curl
{
    private static $requestMethod = [ 'GET', 'POST', 'PUT', 'DELETE' ];
    
    /**
     *  发送curl请求
     *  @param string $url   请求地址
     *  @param string $type  请求方式
     *  @param array $data   请求数据
     *  @param bool $is_json 是否是json请求，true 将数据转为json，并添加Content-type: application/json;
     *  @param array $header 请求头
     *  @param bool $https   是否是https请求
     *  @throws \Exception
     *  @return string|array
     */
    public static function fetch(string $url, string $type = 'GET', array $data = [], bool $is_json = false, array $header = [], bool $https = false)
    {
        $request_method = strtoupper($type);

        if (!in_array($request_method, self::$requestMethod)) throw new \Exception('未知的请求方式');

        $defaultHeader = $is_json ? ['Content-Type: application/json; charset=utf8'] : [];

        $realHeader = !empty($header) ? array_unique(array_merge($defaultHeader, $header)) : $defaultHeader;

        $curl = curl_init();

        if ($request_method === 'GET' && !empty($data)) {
            $params = [];
            foreach ($data as $key => $value) array_push($params, sprintf('%s=%s', $key, $value));

            $params = implode('&', $params);

            $url = sprintf('%s%s%s', $url, strpos($params, '?') !== false ? '&' : '?', $params);
            $data = [];
        }

        $data = $is_json ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;

        curl_setopt($curl, CURLOPT_URL, $url);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        
        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $realHeader);
        // 设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        // 如果是https请求
        if ($https) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($curl);
        
        // 显示错误信息
        if (curl_error($curl)) {
            curl_close($curl);
            throw new \Exception(curl_error($curl));
        }
        
        curl_close($curl);

        if ($is_json) {
            $response = str_replace("\"", '"', $response);
            $response = json_decode($response, true);
        }

        return $response;
    }
}
