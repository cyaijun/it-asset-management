<?php
// 简易 Excel 导出类 (使用 CSV 格式)
class SimpleExcelExporter {
    private $data = [];
    private $headers = [];

    public function __construct($headers = []) {
        $this->headers = $headers;
    }

    public function addRow($row) {
        $this->data[] = $row;
    }

    public function download($filename) {
        // 添加 BOM 以支持中文
        $output = "\xEF\xBB\xBF";

        // 写入表头
        if (!empty($this->headers)) {
            $output .= $this->toCSVRow($this->headers);
        }

        // 写入数据
        foreach ($this->data as $row) {
            $output .= $this->toCSVRow($row);
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $output;
        exit;
    }

    private function toCSVRow($row) {
        $csv = '';
        foreach ($row as $i => $value) {
            if ($i > 0) {
                $csv .= ',';
            }
            // 处理包含逗号、引号或换行的值
            $value = (string)$value;
            if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                $value = '"' . str_replace('"', '""', $value) . '"';
            }
            $csv .= $value;
        }
        return $csv . "\n";
    }
}
