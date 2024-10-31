<?php

declare(strict_types=1);

namespace Shiwei\Tools;

class Helper
{
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
