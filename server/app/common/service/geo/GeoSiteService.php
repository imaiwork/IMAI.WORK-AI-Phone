<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoSite;
use app\common\model\geo\GeoSiteTask;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoPublish;

/**
 * AI官网SEO:把 GEO 内容按每日配额定时发布到用户自己的站点(WordPress / 自定义 webhook)。
 *
 * 发布通道:
 *  - wordpress:POST {api_endpoint}/wp-json/wp/v2/posts,Basic 认证(用户名+应用密码),返回文章链接。
 *  - webhook:POST {api_endpoint},Bearer {api_key},body={title,content},约定返回 {url}。
 *  - manual:仅建记录,不自动发(留给人工)。
 *
 * 定时:cron 每小时跑 `php think geo_site_publish`(见 config/console.php),按 daily_count 配额发布。
 */
class GeoSiteService
{
    /**
     * SSRF 防护:站点接口地址仅允许 http(s) 且解析到公网 IP。
     * DNS 解析失败一律拒绝(不可验证时不放行),避免 gethostbyname 失败原样返回 hostname 绕过。
     * @return string 校验通过的公网 IP(供 CURLOPT_RESOLVE 钉死,避免二次解析窗口)
     * @throws \Exception 地址不合法、解析失败或指向内网/保留地址
     */
    public static function assertSafeUrl(string $url): string
    {
        $parts = parse_url($url);
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new \Exception('站点接口地址不合法,仅支持 http(s)');
        }
        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        // gethostbyname 失败时返回原 hostname,必须显式拒绝,不能当作“非内网 IP”放行
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \Exception('站点接口地址域名解析失败');
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new \Exception('站点接口地址不允许指向内网/保留地址');
        }
        return $ip;
    }

    /**
     * 校验 URL 并返回钉扎三元组,供出站请求 CURLOPT_RESOLVE 使用(一次解析,无二次窗口)。
     * @return array{0:string,1:int,2:string} [host, port, ip]
     * @throws \Exception
     */
    public static function resolveSafeHost(string $url): array
    {
        $ip = self::assertSafeUrl($url);
        $parts = parse_url($url);
        $host = (string)($parts['host'] ?? '');
        $scheme = strtolower((string)($parts['scheme'] ?? 'http'));
        $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        return [$host, $port, $ip];
    }

    /**
     * 取站点 api_key 明文:密文自动解密;历史明文原样返回(与授权凭据口径一致)。
     * @throws \Exception 密文解密失败
     */
    public static function plainApiKey(GeoSite $site): string
    {
        $v = trim((string)$site->api_key);
        if ($v === '' || !GeoCredentialService::isCipher($v)) {
            return $v;
        }
        return GeoCredentialService::decrypt($v);
    }

    /**
     * 带 SSRF 防护的 GET 下载:不用 FOLLOWLOCATION 整体跟随(公网 302 → 内网会绕过校验),
     * 而是逐跳重新 assertSafeUrl;校验返回的 IP 直接 CURLOPT_RESOLVE 钉住,防 DNS 重绑定。
     * @return string 响应体;失败返回 ''
     */
    public static function safeDownload(string $url, int $maxRedirects = 3): string
    {
        for ($i = 0; $i <= $maxRedirects; $i++) {
            try {
                [$host, $port, $ip] = self::resolveSafeHost($url);
            } catch (\Throwable $e) {
                return '';
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $loc = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
            curl_close($ch);
            if ($code >= 300 && $code < 400 && $loc !== '') {
                $url = $loc;
                continue;
            }
            return ($resp === false || $code >= 400) ? '' : (string)$resp;
        }
        return '';
    }
    /** 连通性自检:尝试探测站点接口是否可达 */
    public static function checkSite(GeoSite $site): array
    {
        if ($site->type === 'wordpress') {
            $url = rtrim((string)$site->api_endpoint, '/') . '/wp-json/wp/v2/types';
            [$code, $body] = self::httpGet($url, self::wpAuthHeader($site));
            $ok = $code >= 200 && $code < 300;
            return ['ok' => $ok, 'msg' => $ok ? 'WordPress 接口可达' : "探测失败 HTTP {$code}"];
        }
        if ($site->type === 'webhook') {
            return ['ok' => true, 'msg' => 'Webhook 已配置(发布时校验)'];
        }
        if ($site->type === 'wechat_oa') {
            try {
                GeoWechatService::token($site);
                return ['ok' => true, 'msg' => '公众号凭据有效'];
            } catch (\Throwable $e) {
                return ['ok' => false, 'msg' => mb_substr($e->getMessage(), 0, 150)];
            }
        }
        return ['ok' => true, 'msg' => '手动模式'];
    }

    /**
     * 发布一篇内容到站点,写入发布记录。
     * @return array publish 记录
     * @throws \Exception 发布失败
     */
    public static function publishContent(GeoSiteTask $task, GeoContent $content): array
    {
        $site = GeoSite::findOrEmpty($task->site_id);
        if ($site->isEmpty()) {
            throw new \Exception('站点不存在');
        }
        $url = '';
        $status = 'published';
        $err = '';
        try {
            if ($site->type === 'wordpress') {
                $url = self::publishWordpress($site, $content);
            } elseif ($site->type === 'webhook') {
                $url = self::publishWebhook($site, $content);
            } elseif ($site->type === 'wechat_oa') {
                // 公众号(二期):草稿箱必达;认证服务号自动群发,否则 pending 留待后台确认
                $r = GeoWechatService::publish($site, $content);
                $status = $r['status'];
                $err = $r['note'];
            } else {
                // manual:不自动发,记录为 pending 交人工
                $status = 'pending';
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $err = $e->getMessage();
        }

        $p = GeoPublish::create([
            'project_id' => (int)$task->project_id,
            'content_id' => (int)$content->id,
            'media_id' => 0,
            'channel' => 'site',
            'site_id' => (int)$site->id,
            'media_name' => (string)$site->name,
            'title' => (string)$content->title,
            'status' => $status,
            'mode' => $site->type === 'manual' ? 'manual' : 'api',
            'published_url' => $url,
            'cost' => 0, // 发到自有站点不计发稿费
            'error_msg' => mb_substr($err, 0, 500),
            'create_time' => time(),
            'update_time' => time(),
        ]);
        if ($status === 'failed') {
            throw new \Exception($err ?: '发布失败');
        }
        return $p->toArray();
    }

    /** WordPress REST 发文,返回文章链接 */
    protected static function publishWordpress(GeoSite $site, GeoContent $content): string
    {
        $endpoint = rtrim((string)$site->api_endpoint, '/') . '/wp-json/wp/v2/posts';
        $payload = json_encode([
            'title' => (string)$content->title,
            'content' => self::mdToHtml((string)$content->body),
            'status' => 'publish',
        ], JSON_UNESCAPED_UNICODE);
        [$code, $body] = self::httpPost($endpoint, $payload, array_merge(
            ['Content-Type: application/json'],
            self::wpAuthHeader($site)
        ));
        if ($code < 200 || $code >= 300) {
            throw new \Exception("WordPress 发布失败 HTTP {$code}: " . substr((string)$body, 0, 160));
        }
        $data = json_decode((string)$body, true);
        return (string)($data['link'] ?? ($data['guid']['rendered'] ?? ''));
    }

    /** 自定义 webhook 发文,约定返回 {url} */
    protected static function publishWebhook(GeoSite $site, GeoContent $content): string
    {
        $payload = json_encode([
            'title' => (string)$content->title,
            'content' => (string)$content->body,
            'html' => self::mdToHtml((string)$content->body),
        ], JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type: application/json'];
        $apiKey = self::plainApiKey($site);
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }
        [$code, $body] = self::httpPost((string)$site->api_endpoint, $payload, $headers);
        if ($code < 200 || $code >= 300) {
            throw new \Exception("Webhook 发布失败 HTTP {$code}: " . substr((string)$body, 0, 160));
        }
        $data = json_decode((string)$body, true);
        return (string)($data['url'] ?? '');
    }

    protected static function wpAuthHeader(GeoSite $site): array
    {
        $apiKey = self::plainApiKey($site);
        if ((string)$site->api_user === '' && $apiKey === '') {
            return [];
        }
        return ['Authorization: Basic ' . base64_encode($site->api_user . ':' . $apiKey)];
    }

    /** 极简 markdown→html(标题/加粗/换行) */
    public static function mdToHtml(string $md): string
    {
        $lines = explode("\n", $md);
        $html = '';
        foreach ($lines as $l) {
            $t = trim($l);
            if ($t === '') { continue; }
            if (preg_match('/^#{1,6}\s+(.*)/', $t, $m)) {
                $level = strlen(explode(' ', $t)[0]);
                $html .= "<h{$level}>" . htmlspecialchars($m[1]) . "</h{$level}>";
            } else {
                $t = htmlspecialchars($t);
                // 图片 ![alt](url) → <img>:此前被当纯文本转义,发到博客园/公众号后
                // 正文里显示裸的 markdown 图片语法。仅放行 http/https,防 javascript: 注入
                $t = preg_replace(
                    '/!\[([^\]]*)\]\((https?:\/\/[^)\s]+)\)/u',
                    '<img src="$2" alt="$1" style="max-width:100%">',
                    $t
                );
                // 链接 [text](url) → <a>(同样只放行 http/https)
                $t = preg_replace(
                    '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/u',
                    '<a href="$2" target="_blank" rel="noopener">$1</a>',
                    $t
                );
                $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);
                $html .= "<p>{$t}</p>";
            }
        }
        return $html;
    }

    // ---------- HTTP ----------
    /** 出站 POST:校验后 CURLOPT_RESOLVE 钉死 IP,与 safeDownload 同口径防 DNS 重绑定 */
    protected static function httpPost(string $url, string $body, array $headers): array
    {
        [$host, $port, $ip] = self::resolveSafeHost($url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, $resp];
    }

    /** 出站 GET:校验后 CURLOPT_RESOLVE 钉死 IP,与 safeDownload 同口径防 DNS 重绑定 */
    protected static function httpGet(string $url, array $headers): array
    {
        [$host, $port, $ip] = self::resolveSafeHost($url);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, $resp];
    }

    /**
     * 定时调度入口(cron 调用):遍历进行中的任务,按每日配额发布未发布内容。
     * @return array 摘要
     */
    public static function runDue(): array
    {
        $today = date('Y-m-d');
        $summary = ['tasks' => 0, 'published' => 0, 'failed' => 0];
        $tasks = GeoSiteTask::where('status', 1)->select();
        foreach ($tasks as $task) {
            // 单个任务异常(站点配置坏/网络问题)只跳过该任务,不中断整轮调度
            try {
                // 跨天重置今日计数
                if ($task->today_date !== $today) {
                    $task->today_date = $today;
                    $task->today_count = 0;
                }
                $quota = max(0, (int)$task->daily_count - (int)$task->today_count);
                if ($quota <= 0) { continue; }
                $summary['tasks']++;
                $done = self::publishBatch($task, $quota, $summary);
                $task->today_count = (int)$task->today_count + $done;
                $task->last_run = time();
                $task->save();
            } catch (\Throwable $e) {
                $summary['failed']++;
                \think\facade\Log::error("geo_site_publish task#{$task->id}: " . $e->getMessage());
            }
        }
        return $summary;
    }

    /**
     * 发布最多 $quota 篇该任务项目下"尚未发到该站点"的内容。
     * 去重口径:已 published 或 pending(manual 待人工)的不再发;
     * 24 小时内失败过的暂不重试(避免坏凭证站点每小时刷一条 failed 记录)。
     */
    public static function publishBatch(GeoSiteTask $task, int $quota, array &$summary): int
    {
        $publishedIds = GeoPublish::where('site_id', $task->site_id)
            ->whereIn('status', ['published', 'pending'])->column('content_id');
        $recentFailedIds = GeoPublish::where('site_id', $task->site_id)
            ->where('status', 'failed')
            ->where('create_time', '>', time() - 86400)->column('content_id');
        $skipIds = array_unique(array_merge($publishedIds, $recentFailedIds));
        $contents = GeoContent::where('project_id', $task->project_id)
            // 只发用户已采纳的内容:不过滤会把刚生成、用户还没看过的草稿自动发出去
            ->where('adopted', 1)
            ->whereNotIn('id', $skipIds ?: [0])
            ->order('id asc')->limit($quota)->select();
        $done = 0;
        foreach ($contents as $c) {
            try {
                self::publishContent($task, $c);
                $summary['published']++;
                $task->published_count = (int)$task->published_count + 1;
                $done++;
            } catch (\Throwable $e) {
                $summary['failed']++;
            }
        }
        return $done;
    }
}
