<?php

namespace app\common\service\geo;

use Symfony\Component\DomCrawler\Crawler;

/**
 * 知识库导入的取文服务(二期):
 *  - fetchUrlText:官网网址抓取 → 纯文本(标题 + 去脚本正文),带 SSRF 防护;
 *  - parseFileText:已上传 PDF/Word → 纯文本(优先中台 Coze 文件解析,docx 有本地 ZipArchive 兜底)。
 * 产出文本统一喂给现成的 parse_knowledge 任务(GeoTaskService),
 * 截断、实体抽取、计费口径与"粘贴文本"完全一致。
 */
class GeoFetchService
{
    /** 官网网址抓取正文(照 KbTeachLogic::capture 的成熟做法,外加 GEO 的 SSRF 防护) */
    public static function fetchUrlText(string $url): string
    {
        // URL 直接指向文档(而非网页)时转文档解析链路:PDF/Word 二进制被当网页正文
        // 塞给 AI,会得到"成功提取 0 实体"的假成功——用户被扣费且一条数据都没入库
        $ext = strtolower((string)pathinfo((string)parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            return self::parseFileText($url, $ext);
        }
        // safeDownload 逐跳校验重定向,防"公网 302 → 内网"绕过初始校验
        $html = GeoSiteService::safeDownload($url);
        if ($html === '') {
            throw new \Exception('页面抓取失败:无法访问该网址');
        }
        // 无扩展名的文档 URL 按魔数识别:%PDF=PDF、PK=docx(zip)、D0CF11E0=doc(OLE)
        if (strncmp($html, '%PDF', 4) === 0) {
            return self::parseFileText($url, 'pdf');
        }
        if (strncmp($html, "PK\x03\x04", 4) === 0) {
            return self::parseFileText($url, 'docx');
        }
        if (strncmp($html, "\xD0\xCF\x11\xE0", 4) === 0) {
            return self::parseFileText($url, 'doc');
        }
        $crawler = new Crawler($html);

        $content = '';
        $titleNodes = $crawler->filter('title');
        if ($titleNodes->count() > 0 && trim($titleNodes->text()) !== '') {
            $content = trim($titleNodes->text()) . PHP_EOL;
        }
        $body = $crawler->filter('body');
        if ($body->count() === 0) {
            throw new \Exception('页面无正文内容');
        }
        // 去掉脚本/样式节点后取纯文本
        $body->filter('script,style,noscript')->each(function ($node) {
            $n = $node->getNode(0);
            if ($n && $n->parentNode) $n->parentNode->removeChild($n);
        });
        $content .= $body->text();
        $content = trim((string)preg_replace('/[ \t]{2,}/u', ' ', (string)preg_replace('/\s*\n\s*/u', "\n", $content)));
        if (mb_strlen($content) < 30) {
            throw new \Exception('未能从该网址提取到有效正文(可能是纯 JS 渲染页面),可改用粘贴文本导入');
        }
        return $content;
    }

    /**
     * 解析已上传文档为纯文本。
     * @param string $fileUrl 上传后的文件 URL(公网可达)
     * @param string $ext     扩展名(pdf/doc/docx),为空时从 URL 推断
     */
    public static function parseFileText(string $fileUrl, string $ext = ''): string
    {
        $ext = strtolower($ext ?: (string)pathinfo((string)parse_url($fileUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            throw new \Exception('仅支持 PDF / Word(doc、docx)文档');
        }
        // 转发中台前先做 SSRF 校验,避免委托 Coze 拉取内网/元数据地址
        GeoSiteService::assertSafeUrl($fileUrl);
        // 优先中台 Coze 文件解析(PDF/Word 通吃)
        $text = \app\api\logic\coze\CozeToolsLogic::fileParse($fileUrl);
        if ($text !== '') {
            return $text;
        }
        // docx 本地兜底:docx 即 zip,直接读 word/document.xml 提取文本,不依赖外部服务
        if ($ext === 'docx') {
            $text = self::docxToText($fileUrl);
            if ($text !== '') return $text;
        }
        throw new \Exception('文档解析失败:解析服务不可用或文档无文本内容(扫描件请先 OCR)');
    }

    /** docx 本地解析:下载后按 zip 读取 word/document.xml */
    protected static function docxToText(string $fileUrl): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'geo_docx_');
        try {
            $bin = self::download($fileUrl);
            if ($bin === '') return '';
            file_put_contents($tmp, $bin);
            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) return '';
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            if ($xml === false) return '';
            // 段落/换行转 \n 后剥标签
            $xml = str_replace(['</w:p>', '<w:br/>', '<w:br />', '<w:tab/>'], ["\n", "\n", "\n", ' '], $xml);
            $text = trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
            return $text;
        } finally {
            @unlink($tmp);
        }
    }

    protected static function download(string $url): string
    {
        // file_url 是用户任意传入的,同样需要 SSRF 防护
        return GeoSiteService::safeDownload($url);
    }
}
