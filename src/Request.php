<?php

declare(strict_types=1);

namespace Shiwei\Tools;

/**
 *  curl请求
 *  @author ShiweiXi <xishiwei0821@gmail.com>
 */
class Request
{
    private static $requestMethod = [ 'GET', 'POST', 'PUT', 'DELETE' ];
    
    /**
     *  发送curl请求
     *  @param string $url   请求地址
     *  @param string $type  请求方式
     *  @param mixed  $data   请求数据
     *  @param array  $header 请求头
     *  @param string $data_type
     *      form array 添加 Content-type: multipart/form-data
     *      json 数据转json 添加 Content-type: application/json
     *      xml 数据转xml 添加 Content-type: application/xml
     *      urlencode urlencode编码参数 添加 Content-type: application/x-www-form-urlencoded
     *  @param string $return_type 返回类型
     *  @throws \Exception
     *  @return string|array
     */
    public static function fetch(string $url, string $type = 'GET', $data = [], array $header = [], string $data_type = 'json', string $return_type = 'json', ?callable $stream_callback = null)
    {
        $curl = curl_init();

        $request_method = strtoupper($type);

        if (!in_array($request_method, self::$requestMethod)) throw new \Exception('未知的请求方式');

        if ($request_method === 'GET' && !empty($data)) {
            $params = http_build_query($data);
            $url = sprintf('%s%s%s', $url, strpos($params, '?') !== false ? '&' : '?', $params);

            $data = [];
        }

        if (!empty($data)) {
            switch ($data_type) {
                case 'form':
                    $header[] = 'Content-type: multipart/form-data';
                    break;
                case 'json':
                    $header[] = 'Content-type: application/json';
                    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    break;
                case 'xml':
                    $header[] = 'Content-type: application/xml';
                    $data = Format::arrayToXml($data);
                    break;
                case 'urlencode':
                    $header[] = 'Content-type: application/x-www-form-urlencoded';
                    $data = http_build_query($data);
                    break;
                default:
                    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    break;
            }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request_method);

        // 设置请求头
        // curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        // 如果是https请求
        if (strpos("$" . $url, 'https')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if ($request_method === 'POST') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        
        if ($return_type == 'stream') {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, function ($curl, $data) use ($stream_callback) {
                is_callable($stream_callback) && $stream_callback($data);
                return strlen($data);
            });
        } else {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        }

        $response = curl_exec($curl);
        
        // 显示错误信息
        if (curl_error($curl)) {
            curl_close($curl);
            throw new \Exception(curl_error($curl));
        }

        $httpCode    = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        curl_close($curl);
        
        if ($httpCode != 200) {
            throw new \Exception('Curl 请求错误, 错误消息: ' . $response, $httpCode);
        }

        switch ($return_type) {
            case 'json':
                $response = is_string(($response)) && json_decode($response) ? json_decode($response, true) : $response;
                break;
            case 'xml':
                $response = Format::xmlToArray($response);
            case 'stream': return $response;
            default: break;
        }

        return $response;
    }
}
