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
    public static function createPathDir(string $path = '', int $permission = 0777): bool
    {
        if (empty($path)) return true;
        
        if (!is_dir($path)) {
            if (!self::createPathDir(dirname($path))) {
                return false;
            }

            if (!mkdir($path, $permission)) {
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
     *  下载网络图片到指定目录
     *  @param string $file_url
     *  @param string $save_path
     *  @return string 图片地址
     */
    public static function downloadImage(string $file_url, string $save_path): string
    {
        $file_content = @file_get_contents($file_url);

        if ($file_content === false) throw new \Exception('无法读取图片内容');

        [$_, $_, $type] = @getimagesize($file_url);

        if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_SVG, IMAGETYPE_ICO])) {
            throw new \Exception('图片格式错误');
        }

        $ext_map = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_SVG  => 'svg',
            IMAGETYPE_ICO  => 'ico',
        ];

        if (!@getimagesize($file_content)) throw new \Exception('下载的内容不是有效的图片格式');

        $file_name = date('YmdHis') . sprintf('%04d', 0, 9999);
        $file_ext  = $ext_map[$type] ?? 'jpg';

        $full_path = $save_path . $file_name . '.' . $file_ext;

        if (!file_put_contents($full_path, $file_content)) throw new \Exception('写入内容失败');

        return $full_path;
    }

    /**
     *  压缩图片
     *  @param string $file_path  图片地址
     *  @param string $save_path  保存目录
     *  @param int    $max_width  最大宽度
     *  @param int    $max_height 最大高度
     *  @return string 图片地址
     */
    public static function compressImage(string $file_path, string $save_path, int $max_width, int $max_height): string
    {
        if (empty($file_path)) throw new \Exception('缺少图片地址');

        $image = @file_get_contents($file_path);
        if ($image === false) throw new \Exception('无法读取图片内容');

        list($width, $height, $type) = @getimagesize($file_path);
        if (!$width || !$height) throw new \Exception('获取图片尺寸错误');

        if ($width <= $max_width && $height <= $max_height) {
            return $file_path;
        }

        $scaleX = $max_width / $width;
        $scaleY = $max_height / $height;
        $scale  = min($scaleX, $scaleY);

        $new_width = (int)floor($width * $scale);
        $new_height = (int)floor($height * $scale);
        
        // 新建目标
        $file_name = date('YmdHis') . sprintf('%04d', 0, 9999);

        $dstImg = imagecreatetruecolor($new_width, $new_height);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImg = imagecreatefromjpeg($file_path);
                if ($srcImg === false) throw new \Exception('无法读取JPG图片内容');

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                $new_file_path = $save_path . $file_name . '.' . 'jpg';
                imagejpeg($dstImg, $new_file_path, 90);
                break;
            case IMAGETYPE_PNG:
                $srcImg = imagecreatefrompng($file_path);
                if ($srcImg === false) throw new \Exception('无法读取PNG图片内容');

                // 保留PNG透明的
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                $new_file_path = $save_path . $file_name . '.' . 'png';
                imagepng($dstImg, $new_file_path);
                break;
            case IMAGETYPE_GIF:
                $srcImg = imagecreatefromgif($file_path);
                if ($srcImg === false) throw new \Exception('无法读取GIF图片内容');

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                $new_file_path = $save_path . $file_name . '.' . 'gif';
                imagegif($dstImg, $new_file_path);
                break;
            default: throw new \Exception('暂时只支持JPG/PNG格式图片');
        }

        imagedestroy($dstImg);

        return $new_file_path;
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

    /**
     *  根据指定日期获取日期范围
     *  @param string $date 日期
     *  @param string $type 类型
     *  @param array  $extra 额外数据
     *  @return array
     */
    public static function getDateZone(string $date = '', string $type = '', array $extra = []): array
    {
        $date = strtotime($date) ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $type = !empty($type) ? strtoupper($type) : 'TODAY';

        $dateTime = new \DateTime($date);

        $times = [];

        switch ($type) {
            case 'TODAY':
                // 今天范围
                $times[] = $dateTime->format('Y-m-d 00:00:00');
                $times[] = $dateTime->format('Y-m-d 23:59:59');
                break;
            case 'YESTERDAY':
                // 昨天范围
                $last_day = $dateTime->modify('-1 days');
                $times[]  = $last_day->format('Y-m-d 00:00:00');
                $times[]  = $last_day->format('Y-m-d 23:59:59');
                break;
            case 'TOMORROW':
                // 明天
                $next_day = $dateTime->modify('+1 days');
                $times[]  = $next_day->format('Y-m-d 00:00:00');
                $times[]  = $next_day->format('Y-m-d 23:59:59');
                break;
            case 'WEEK':
                // 本周
                // 从哪天开始
                $weekIndex = $extra['week_index'] ?? 0;
                // 今天所在索引
                $dayOfWeek = (int)$dateTime->format('w');
                // 本周第一天
                $times[]   = $dateTime->modify(sprintf('-%s days', ($dayOfWeek - $weekIndex)))->format('Y-m-d 00:00:00');
                // 本周最后一天
                $times[]   = $dateTime->modify('+6 days')->format('Y-m-d 23:59:59');
                break;
            case 'LAST_WEEK':
                // 上周
                // 从哪天开始
                $weekIndex = $extra['week_index'] ?? 0;
                // 这周的第一天
                $dateTime->setISODate((int)$dateTime->format('o'), (int)$dateTime->format('W'), $weekIndex);
                // 上周的第一天
                $times[] = $dateTime->modify('-1 week')->format('Y-m-d 00:00:00');
                // 上周最后一天
                $times[] = $dateTime->modify('+6 days')->format('Y-m-d 23:59:59');
                break;
            case 'MONTH':
                // 本月
                $times[] = $dateTime->modify('first day of this month')->format('Y-m-d 00:00:00');
                $times[] = $dateTime->modify('last day of this month')->format('Y-m-d 23:59:59');
                break;
            case 'LAST_MONTH':
                // 上月
                $times[] = $dateTime->modify('-1 month')->format('Y-m-01 00:00:00');
                $times[] = $dateTime->modify('last day of this month')->format('Y-m-d 23:59:59');
                break;
            case 'QUARTER':
                // 本季
                $month = $dateTime->format('m');
                $year  = $dateTime->format('Y');
    
                $firstMonth = ($month - 1) - ($month - 1) % 3 + 1;
    
                $start_days = new \DateTime($year . '-' . $firstMonth . '-01 00:00:00');
    
                $times[] = $start_days->format('Y-m-d 00:00:00');
                $times[] = $start_days->modify('+3 months')->modify('-1 days')->format('Y-m-d 23:59:59');
                break;
            case 'LAST_QUARTER':
                // 上季度
                $month = $dateTime->format('m');
                $year  = $dateTime->format('Y');
    
                $firstMonth = $month - 3;
    
                if ($firstMonth <= 0) {
                    $firstMonth += 12;
                    $year -= 1;
                }
    
                $start_days = new \DateTime($year . '-' . $firstMonth . '-01 00:00:00');
                $times[] = $start_days->format('Y-m-d 00:00:00');
                $times[] = $start_days->modify('+3 months')->modify('-1 days')->format('Y-m-d 23:59:59');
                break;
            case 'YEAR':
                // 今年
                $times[] = $dateTime->format('Y-01-01 00:00:00');
                $times[] = $dateTime->format('Y-12-31 23:59:59');
                break;
            case 'LAST_YEAR':
                // 去年
                $times[] = $dateTime->modify('-1 years')->format('Y-01-01 00:00:00');
                $times[] = $dateTime->format('Y-12-31 23:59:59');
                break;
            default:
                // 今天
                $times[] = $dateTime->format('Y-m-d 00:00:00');
                $times[] = $dateTime->format('Y-m-d 23:59:59');
                break;
        }

        return $times;
    }
}
