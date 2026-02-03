<?php

declare(strict_types = 1);

namespace Shiwei\Tools;

use Vtiful\Kernel\Excel as XlsExcel;

/**
  * 导出类
  * @author ShiweiXi<xishiwei0821@gmail.com>;
  */
class Excel
{
    protected $options = [];

    protected ?XlsExcel $excel = null;

    public function __construct($options = [])
    {
        $this->setOptions($options);
        $this->getExcel();
    }

    public function setOptions(array $options = []): self
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
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

    private function getExcel()
    {
        try {
            if (!class_exists(XlsExcel::class)) throw new \Exception('缺少php扩展: xlswriter');

            if ($this->excel instanceof XlsExcel) return $this->excel;

            $root_path = $this->options['root_path'] ?? '';
            $root_path = empty($root_path) || !is_dir($root_path) ? $_SERVER["DOCUMENT_ROOT"] : $root_path;

            $this->excel = new XlsExcel(['path' => $root_path]);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     *  读取excel全部数据
     *  @param string $file_path
     *  @return array
     */
    public function read(string $file_path, array $sheet_type = []): array
    {
        if (!file_exists($file_path)) throw new \InvalidArgumentException('文件不存在');

        try {
            $excel = $this->excel;
            $excel = $excel->setType($sheet_type);

            $sheetList = $excel->openFile($file_path)->sheetList();

            $result = [];
            foreach ($sheetList as $sheetName) $result[$sheetName] = $excel->openSheet($sheetName)->getSheetData();
            
            return $result;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     *  游标处理数据
     */
    public function course_handle(string $file_path, array $sheet_type = [], ?callable $callback = null): void
    {
        if (!file_exists($file_path)) throw new \InvalidArgumentException('文件不存在');
        
        try {
            $excel = $this->excel;
            $excel = $excel->setType($sheet_type);

            $sheetList = $excel->openFile($file_path)->sheetList();

            foreach ($sheetList as $sheetName) {
                $excel->openSheet($sheetName);

                while (($row = $excel->nextRow()) !== NULL) is_callable($callback) && $callback($sheetName, $row);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     *  写入数据
     *  @param array  $options   内容设置
     *      option.key    对应字段路径
     *      option.title  展示表头
     *      option.format 自定义处理方法
     *  @param array  $data      数据
     *  @param string $file_name 文件名称
     *  @return void
     */
    public function write(array $options = [], array $data = [], string $file_name = '', string $save_path = ''): void
    {
        try {
            $excel = $this->excel;

            Helper::createPathDir($save_path);

            $random    = date('YmdHis') . mt_rand(1000, 9999);
            $file_name = empty($file_name) ? $random : $file_name . '_' . $random;
            $file_ext  = 'xlsx';

            $excel->fileName(sprintf('%s/%s.%s', $save_path, $file_name, $file_ext));

            $this->_handleWriteData($excel, $options, $data);

            $excel->output();
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     *  处理写入数据
     */
    private function _handleWriteData(XlsExcel &$excel, array $options = [], array $data = []): void
    {
        $startIndex = 0;

        // 获取标题列数据
        $titles = array_map(fn($item) => $item['title'] ?? $item['key'] ?? '--', $options);

        // 逐个插入表头
        foreach ($titles as $key => $title) $excel->insertText($startIndex, $key, $title);
        
        // 逐行插入数据
        foreach ($data as $row) self::_handleWriteRow($excel, $row, $options, $startIndex);
    }

    /**
     *  处理单行写入
     */
    private function _handleWriteRow(XlsExcel &$excel, array $row, $options, &$index = 0)
    {
        $index += 1;

        foreach ($options as $key => $item) {
            $type = $item['type'] ?? 'text';

            $insertValue = self::_getInstanceValue($row, $item['key']);

            isset($item['format']) && is_callable($item['format']) && $insertValue = $item['format']($insertValue);

            switch ($type) {
                default: $excel->insertText($index, $key, $insertValue);
            }
        }
    }

    /**
     *  根据key获取实例值
     */
    private function _getInstanceValue(array $row, string $key)
    {
        if (empty($key)) return '';

        $keys = explode('.', $key);
        $current = $row;

        foreach ($keys as $field) {
            if (is_array($current) && isset($current[$field])) {
                $current = $current[$field];
            } else {
                return '';
            }
        }

        return $current;
    }
}
