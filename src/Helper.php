<?php

declare(strict_types=1);

namespace Shiwei\Tools;

/**
 *  常用函数
 *  @author ShiweiXi <xishiwei0821@gmail.com>
 */
class Helper
{
    /**
     *  创建随机字符串
     */
    public static function createNonceStr(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     *  递归创建目录
     *  @param string $path
     *  @return bool
     */
    public static function createPathDir(string $path = ''): bool
    {
        if (empty($path)) return true;
        
        if (!is_dir($path)) {
            if (!self::createPathDir(dirname($path))) {
                return false;
            }

            if (!mkdir($path, 0777)) {
                return false;
            }
        }

        return true;
    }

    /**
     *  获取文件的mine_type 类型 例如 image/jpeg
     *  @param string $file
     *  @throws \Exception
     *  @return string
     */
    public static function getFileType(string $file): string
    {
        if (!is_file($file)) throw new \Exception('请选择本地文件');

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if (!$finfo) throw new \Exception('文件打开失败');

        $minetype = finfo_file($finfo, $file);

        finfo_close($finfo);

        $typeArray = explode('/', $minetype);

        if (empty($typeArray)) throw new \Exception('获取文件类型失败');

        return $typeArray[0];
    }

    /**
     *  获取当前微秒时间
     *  @return string
     */
    public static function getMicroTime(): string
    {
        [$micro, $time] = explode(' ', microtime());

        return sprintf('%s%s', $time, sprintf('%03d', $micro * 1000));
    }

    /**
     *  获取笛卡尔乘积
     *  @param array
     *  @return array
     */
    public static function getAllProducts(array ...$arrays)
    {
        if (empty($arrays)) return [[]];

        $result = [];
        $first  = array_shift($arrays);

        foreach ($first as $element) {
            foreach (self::getAllProducts(...$arrays) as $combination) {
                $result[] = array_merge([$element], $combination);
            }
        }

        return $result;
    }

    /**
     *  根据字段获取索引树
     *  @param array
     *  @param string|array $key
     *  @param string $relation 对应关系 oto 一对一 otm 一对多
     *  @return array
     */
    public static function getIndexTree(array $array = [], $index = 'id', string $relation = 'otm'): array
    {
        $index_tree = [];

        foreach ($array as $item) {
            if (is_array($index)) {
                $all_fields = [];
                foreach ($index as $field) array_push($all_fields, is_array($item[$field]) ? (!empty($item[$field]) ? $item[$field] : [0]) : [!empty($item[$field]) ? $item[$field] : 0]);

                $all_keys = self::getAllProducts(...$all_fields);

                $unique_keys = [];
                foreach ($all_keys as $key) array_push($unique_keys, implode('_', $key));
            } else {
                $unique_keys = is_array($item[$index]) ? $item[$index] : [$item[$index]];
            }

            foreach ($unique_keys as $unique_key) {
                if (!array_key_exists($unique_key, $index_tree)) $index_tree[$unique_key] = [];

                switch ($relation) {
                    case 'oto': $index_tree[$unique_key] = $item; break;
                    case 'otm': array_push($index_tree[$unique_key], $item); break;
                }
            }
        }

        return $index_tree;
    }
}
