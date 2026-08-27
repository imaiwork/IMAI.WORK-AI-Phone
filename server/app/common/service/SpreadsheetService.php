<?php

namespace app\common\service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SpreadsheetService
{
    private const OPEN_BASEDIR_NOTICE = 'open_basedir restriction in effect';
    private const VENDOR_PATH = '/phpoffice/phpspreadsheet/';

    /**
     * 读取 Excel 文件，规避 PhpSpreadsheet 在 open_basedir 环境下的误报。
     *
     * WPS 等工具导出的 xlsx，rels 里的 Target 可能写成绝对部件名（/xl/worksheets/sheet1.xml）。
     * Reader\Xlsx::load() 会把它拼成 xl//xl/worksheets/sheet1.xml，归一化后仍带前导斜杠，
     * 再交给 Shared\File::realpath() 用 file_exists() 探一次真实文件系统。开了 open_basedir
     * 的机器上这一步会抛 warning，被 ThinkPHP 的错误处理器转成 ErrorException，导入直接失败——
     * 而 PhpSpreadsheet 自己下一行的 substr($fileName, 1) 兜底本来就能读到正确的条目。
     * 所以这里只吞掉 PhpSpreadsheet 内部这一类 warning，其余错误照旧交回原处理器。
     */
    public static function load(string $filename): Spreadsheet
    {
        $previous = set_error_handler(
            static function (int $errno, string $errstr, string $errfile = '', int $errline = 0) use (&$previous) {
                if (self::isOpenBasedirProbe($errstr, $errfile)) {
                    return true;
                }
                return $previous ? ($previous)($errno, $errstr, $errfile, $errline) : false;
            }
        );

        try {
            return IOFactory::load($filename);
        } finally {
            restore_error_handler();
        }
    }

    private static function isOpenBasedirProbe(string $errstr, string $errfile): bool
    {
        return str_contains($errstr, self::OPEN_BASEDIR_NOTICE)
            && str_contains(str_replace('\\', '/', $errfile), self::VENDOR_PATH);
    }
}
