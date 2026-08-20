<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoContent;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

/**
 * GEO 内容导出:Markdown / HTML / Word(docx) / JSON
 * 返回 ['filename'=>..,'mime'=>..,'content'=>二进制字符串(base64 由控制器处理)]
 */
class GeoExportService
{
    /**
     * @param int[]  $ids    内容ID
     * @param string $format md|html|docx|json
     */
    public static function export(array $ids, string $format): array
    {
        $contents = GeoContent::whereIn('id', $ids)->order('id asc')->select()->toArray();
        $format = in_array($format, ['md', 'html', 'docx', 'json']) ? $format : 'md';
        $ts = date('YmdHis');

        switch ($format) {
            case 'json':
                $data = array_map(function ($c) {
                    return [
                        'title' => $c['title'], 'content_type' => $c['content_type'],
                        'body' => $c['body'], 'tags' => json_decode((string)$c['tags'], true) ?: [],
                    ];
                }, $contents);
                return [
                    'filename' => "geo_content_{$ts}.json",
                    'mime' => 'application/json',
                    'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ];

            case 'html':
                $body = '';
                foreach ($contents as $c) {
                    $body .= "<article><h1>" . htmlspecialchars($c['title']) . "</h1>\n"
                        . "<p class=\"meta\">类型:" . htmlspecialchars($c['content_type']) . "</p>\n"
                        . self::mdToHtml((string)$c['body']) . "</article>\n<hr/>\n";
                }
                $html = "<!doctype html><html lang=\"zh\"><head><meta charset=\"utf-8\">"
                    . "<title>GEO 内容导出</title><style>body{font-family:system-ui,'PingFang SC',sans-serif;max-width:820px;margin:40px auto;padding:0 20px;color:#1e293b;line-height:1.7}h1{font-size:24px}.meta{color:#94a3b8;font-size:13px}hr{border:none;border-top:1px solid #e2e8f0;margin:32px 0}</style></head><body>{$body}</body></html>";
                return ['filename' => "geo_content_{$ts}.html", 'mime' => 'text/html', 'content' => $html];

            case 'docx':
                return self::toDocx($contents, $ts);

            case 'md':
            default:
                $md = '';
                foreach ($contents as $c) {
                    $md .= "# {$c['title']}\n\n> 类型:{$c['content_type']}\n\n{$c['body']}\n\n---\n\n";
                }
                return ['filename' => "geo_content_{$ts}.md", 'mime' => 'text/markdown', 'content' => $md];
        }
    }

    /** 极简 Markdown → HTML(标题/段落,满足内容展示) */
    protected static function mdToHtml(string $md): string
    {
        $lines = explode("\n", $md);
        $html = '';
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            if (preg_match('/^### (.*)/', $t, $m)) $html .= '<h3>' . htmlspecialchars($m[1]) . "</h3>\n";
            elseif (preg_match('/^## (.*)/', $t, $m)) $html .= '<h2>' . htmlspecialchars($m[1]) . "</h2>\n";
            elseif (preg_match('/^# (.*)/', $t, $m)) $html .= '<h2>' . htmlspecialchars($m[1]) . "</h2>\n";
            elseif (preg_match('/^> (.*)/', $t, $m)) $html .= '<blockquote>' . htmlspecialchars($m[1]) . "</blockquote>\n";
            elseif (preg_match('/^[-*] (.*)/', $t, $m)) $html .= '<li>' . htmlspecialchars($m[1]) . "</li>\n";
            else $html .= '<p>' . htmlspecialchars($t) . "</p>\n";
        }
        return $html;
    }

    /** 生成 docx(PhpWord) */
    protected static function toDocx(array $contents, string $ts): array
    {
        $word = new PhpWord();
        foreach ($contents as $idx => $c) {
            $section = $word->addSection();
            $section->addTitle((string)$c['title'], 1);
            $section->addText('类型:' . $c['content_type'], ['size' => 9, 'color' => '94A3B8']);
            $section->addTextBreak(1);
            foreach (explode("\n", (string)$c['body']) as $line) {
                $t = trim($line);
                if ($t === '') continue;
                if (preg_match('/^#{1,3} (.*)/', $t, $m)) {
                    $section->addTitle($m[1], 2);
                } elseif (preg_match('/^> (.*)/', $t, $m)) {
                    $section->addText($m[1], ['italic' => true, 'color' => '64748B']);
                } elseif (preg_match('/^[-*] (.*)/', $t, $m)) {
                    $section->addListItem($m[1]);
                } else {
                    $section->addText($t);
                }
            }
        }
        $tmp = sys_get_temp_dir() . "/geo_{$ts}_" . uniqid() . '.docx';
        IOFactory::createWriter($word, 'Word2007')->save($tmp);
        $bin = file_get_contents($tmp);
        @unlink($tmp);
        return [
            'filename' => "geo_content_{$ts}.docx",
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'content' => $bin,
        ];
    }
}
