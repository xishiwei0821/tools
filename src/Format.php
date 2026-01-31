<?php

declare(strict_types=1);

namespace Shiwei\Tools;

/**
 *  格式化数据显示
 *  @author ShiweiXi <xishiwei0821@gmail.com>
 */
class Format
{
    /**
     *  时间格式列表
     */
    private static $hour_format = [
        ['name' => '凌晨', 'min' => 0, 'max' => 5],
        ['name' => '上午', 'min' => 5, 'max' => 12],
        ['name' => '中午', 'min' => 12, 'max' => 14],
        ['name' => '下午', 'min' => 14, 'max' => 20],
        ['name' => '晚上', 'min' => 20, 'max' => 25],
    ];

    /**
     *  将对象元素转数组
     *  @param mixed $object
     *  @return array
     */
    public static function objectToArray($object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     *  根据父子关系转树结构
     *  @param mixed $array
     *  @param mixed $id
     *  @param mixed $pid
     *  @param mixed $children
     *  @return array
     */
    public static function arrayToTree(array $array, string $id = 'id', string $pid = 'pid', string $children = 'children')
    {
        $array = array_values($array);

        if (empty($array)) return [];

        $items = [];

        foreach ($array as $value) {
            if (!array_key_exists($id, $value)) continue;
            if (!array_key_exists($pid, $value)) $value[$pid] = 0;

            $items[$value[$id]] = $value;
        }

        $tree = [];

        foreach ($items as $key => $item) {
            if (isset($items[$item[$pid]])) {
                $items[$item[$pid]][$children][] = &$items[$key];
            } else {
                $tree[] = &$items[$key];
            }
        }

        return $tree;
    }

    /**
     *  时间戳转通用时间格式
     *  @param integer $timestamp 时间戳
     *  @param string  $format 格式
     *  @return string
     */
    public static function timeToString(int $timestamp, string $format = 'Y-m-d H:i:s'): string
    {
        return !empty($timestamp) ? date($format, $timestamp) : '';
    }

    /**
     *  获取指定时间到现在的时长
     *  @param integer $timestamp
     *  @return string
     */
    public static function timeToElapsed(int $timestamp = 0): string
    {
        if (empty($timestamp)) return '';
        if ($timestamp == time()) return '现在';

        $prefix = $timestamp > time() ? '后' : '前';

        $diff_timestamp = abs($timestamp - time());

        $day  = floor($diff_timestamp / (24 * 60 * 60));
        $day_str = !empty($day) ? sprintf('%s天', $day) : '';

        $diff_timestamp -= $day * 24 * 60 * 60;
        $hour = floor($diff_timestamp / (60 * 60));
        $hour_str = !empty($hour) ? sprintf('%s小时', $hour) : '';

        $diff_timestamp -= $hour * 60 * 60;
        $minute = floor($diff_timestamp / 60);
        $minute_str = !empty($minute) ? sprintf('%s分', $minute) : '';

        $diff_timestamp -= $minute * 60;
        $second_str = !empty($diff_timestamp) ? sprintf('%s秒', $diff_timestamp) : '';

        return sprintf('%s%s%s%s%s', $day_str, $hour_str, $minute_str, $second_str, $prefix);
    }

    /**
     *  时间戳转换为常用显示时间
     *  @param integer $timestamp
     *  @return string
     */
    public static function timeToShow(int $timestamp = 0, string $time_type = 'H', array $format = []): string
    {
        if (empty($timestamp)) return '';
        if (empty($format)) $format = self::$hour_format;

        // 排除异常，来自未来的伙伴。
        $today_start = strtotime(date('Y-m-d 00:00:00'));
        $today_end   = strtotime(date('Y-m-d 23:59:59'));
        $yesterday   = $today_start - 24 * 60 * 60;

        if ($timestamp > time()) return '未来';

        $hour = date('H', $timestamp);
        $hour_type = '';

        foreach ($format as $format_item) {
            $min = array_key_exists('min', $format_item) ? $format_item['min'] : 0;
            $max = array_key_exists('max', $format_item) ? $format_item['max'] : 24;

            if ($hour >= $min && $hour < $max) {
                $hour_type = array_key_exists('name', $format_item) && !empty($format_item['name']) ? $format_item['name'] : '';
                break;
            }
        }

        $format = in_array($time_type, ['h', 'H']) ? $time_type . ':i' : 'h:i';
        $showTime = date($format, $timestamp);

        // 获取日期
        if ($timestamp > $today_start) {
            return sprintf('%s %s', $hour_type, $showTime);
        } elseif ($timestamp > $yesterday) {
            return sprintf('昨天 %s %s', $hour_type, $showTime);
        } else {
            return sprintf('%s %s %s', date('Y-m-d', $timestamp), $hour_type, $showTime);
        }
    }

    /**
     *  字符串转根据字节长度从指定key开始转数组（方便hex查看）
     *  @param string  $hex
     *  @param integer $start_index
     *  @param integer $byte_length
     *  @return string[]
     */
    public static function strToPoint(string $hex, int $start_index = 1, int $byte_length = 2)
    {
        if (empty($hex)) return [];

        $result = [];
        $index  = 0;
        while (true) {
            $offset = $index * $byte_length;

            if (strlen($hex) - 1 < $offset) break;

            $sub = substr($hex, $offset, $byte_length);
            $result[$index + $start_index] = $sub;

            $index += 1;
        }

        return $result;
    }

    /**
     *  文件转base64编码
     *  @param string $file_path
     *  @throws \Exception
     *  @return string
     */
    public static function fileToBase64(string $file_path = ''): string
    {
        if (!is_file($file_path)) throw new \Exception('缺少文件地址');

        $fb = fopen($file_path, 'rb', false);
        if (!$fb) throw new \Exception('文件打开失败');

        $binary = fread($fb, filesize($file_path));
        fclose($fb);

        $base64_string = base64_encode($binary);
        
        return $base64_string;
    }

    /**
     *  xml转数组
     *  @param string $xml
     *  @return array
     */
    public static function xmlToArray(string $xml): array
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
    
        $data = [];
        foreach ($index as $key => $value) {
            if ($key == 'xml' || $key == 'XML') {
                continue;
            }

            $tag   = strtolower($vals[$value[0]]['tag']);
            $value = $vals[$value[0]]['value'];

            $data[$tag] = $value;
        }

        return $data;
    }

    /**
     *  数组转xml
     *  @param array $array
     *  @return string
     */
    public static function arrayToXml(array $array = []): string
    {
        $string = "<xml>";
        foreach ($array as $key => $value) $string .= '<' . $key . '>' . $value . '</' . $key . '>';
        $string .= "</xml>";

        return $string;
    }

    public static function number2column(int $number): string
    {
        if ($number < 0) throw new \InvalidArgumentException('转换索引必须大于0');

        $column = '';
        while ($number >= 0) {
            $column = chr(65 + ($number % 26)) . $column;
            $number = (int)($number / 26) - 1;
        }

        return $column;
    }

    public static function column2number(string $column): int
    {
        if (!preg_match('/^[A-Za-z]+$/', $column)) throw new \InvalidArgumentException('转换列格式错误');
        
        $number = 0;
        $column = strtoupper($column);
        for ($i = 0; $i < strlen($column); $i++) {
            $char = $column[$i];
            $number = $number * 26 + (ord($char) - ord('A'));
        }
        return $number;
    }
}
