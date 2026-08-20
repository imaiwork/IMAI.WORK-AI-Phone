<?php

namespace app\common\service\geo;

use app\common\model\geo\GeoAuthAccount;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoSite;

/**
 * 授权账号发布通道:用商家自己授权的平台官方 API 直发内容(0 费用)。
 *
 * 已实现自动发布:wechat_oa(复用 GeoWechatService)、cnblogs(MetaWeblog XML-RPC)、
 * baijiahao(自主开发接入 HTTP)。其余平台目前仅保存凭据(GeoAuthLogic::PLATFORMS
 * 里 publish=0),待通道打通后在此扩展 publishXxx 即可。
 */
class GeoAuthPublishService
{
    /**
     * 取凭据(解密版):secret 字段按 v1: 密文入库(GeoCredentialService),
     * 逐值识别解密;非密文(普通字段/历史明文)原样放行。
     * @throws \Exception 密钥未配置或密文校验失败(check 会兜底为 ok=false)
     */
    protected static function cred(GeoAuthAccount $acc): array
    {
        $cred = json_decode((string)$acc->credentials, true) ?: [];
        foreach ($cred as $k => $v) {
            if (is_string($v) && GeoCredentialService::isCipher($v)) {
                $cred[$k] = GeoCredentialService::decrypt($v);
            }
        }
        return $cred;
    }

    /** 连通性检测 @return ['ok'=>bool,'msg'=>string] */
    public static function check(GeoAuthAccount $acc): array
    {
        try {
            // cred() 必须在 try 内:密钥未配置/密文校验失败也兜底为 ok=false,不穿透 500
            $c = self::cred($acc);
            switch ((string)$acc->platform) {
                case 'wechat_oa':
                    GeoWechatService::token(self::asSite($acc));
                    return ['ok' => true, 'msg' => 'access_token 获取成功,凭据有效'];
                case 'cnblogs':
                    self::assertCnblogsBlog($c);
                    return ['ok' => true, 'msg' => 'MetaWeblog 凭据有效'];
                case 'yuque':
                    $me = self::yuqueRequest($c, 'GET', '/user');
                    $login = (string)($me['data']['login'] ?? '');
                    if ($login === '') return ['ok' => false, 'msg' => 'Token 无效或已过期'];
                    // 顺带验证知识库可达:只验 token 会出现「检测通过但发布 404」
                    try {
                        [$repo, $repoPath] = self::yuqueRepo($c);
                        self::yuqueRequest($c, 'GET', '/repos/' . $repoPath);
                    } catch (\Throwable $e) {
                        return ['ok' => false, 'msg' => 'Token 有效(账号:' . $login . '),但知识库不可访问:'
                            . $e->getMessage() . ';「知识库路径」需填 数字ID 或 用户名/知识库路径(如 ' . $login . '/geo)'];
                    }
                    return ['ok' => true, 'msg' => '语雀凭据有效,账号:' . $login . ',知识库「' . $repo . '」可访问'];
                default:
                    return ['ok' => true, 'msg' => '凭据已保存(该平台暂无免费校验接口,以首次发布结果为准)'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 发布一篇 GEO 文章到授权平台。
     * @return array ['status'=>'published'|'pending','url'=>string,'note'=>string]
     * @throws \Exception 平台不支持/接口失败
     */
    public static function publish(GeoAuthAccount $acc, GeoContent $content): array
    {
        switch ((string)$acc->platform) {
            case 'wechat_oa':
                return GeoWechatService::publish(self::asSite($acc), $content);
            case 'cnblogs':
                return self::publishCnblogs($acc, $content);
            case 'baijiahao':
                return self::publishBaijiahao($acc, $content);
            case 'yuque':
                return self::publishYuque($acc, $content);
            default:
                throw new \Exception('该平台暂不支持自动发布');
        }
    }

    /** 把授权账号适配成临时 GeoSite(不落库),复用公众号站点发布通道 */
    protected static function asSite(GeoAuthAccount $acc): GeoSite
    {
        $c = self::cred($acc);
        $site = new GeoSite();
        $site->id = 100000000 + (int)$acc->id; // 仅用于 token 缓存 key 隔离,不写库
        $site->type = 'wechat_oa';
        $site->api_user = (string)($c['app_id'] ?? '');
        $site->api_key = (string)($c['app_secret'] ?? '');
        return $site;
    }

    // ---------- 博客园 MetaWeblog ----------

    /**
     * 归一化 MetaWeblog 端点。用户常把「博客主页」(https://www.cnblogs.com/博客名)
     * 或 http 地址当成 MetaWeblog 地址填进来:往主页 POST 会被 302 重定向,而
     * xmlRpc 因 SSRF 防护不跟随重定向,直接报「HTTP 302」。这里统一推导成
     * https://rpc.cnblogs.com/metaweblog/博客名,用户怎么填都能走通。
     */
    protected static function cnblogsEndpoint(array $c): string
    {
        $raw = trim((string)($c['blog_url'] ?? ''));
        if ($raw === '') {
            throw new \Exception('请先填写 MetaWeblog 地址(https://rpc.cnblogs.com/metaweblog/你的博客名)');
        }
        $u = parse_url($raw) ?: [];
        $host = strtolower((string)($u['host'] ?? ''));
        $path = trim((string)($u['path'] ?? ''), '/');
        if ($host === 'rpc.cnblogs.com') {
            if (stripos($path, 'metaweblog/') === 0 && basename($path) !== 'metaweblog') {
                return 'https://rpc.cnblogs.com/' . $path;
            }
            throw new \Exception('MetaWeblog 地址缺少博客名,请填写 https://rpc.cnblogs.com/metaweblog/你的博客名');
        }
        if (in_array($host, ['www.cnblogs.com', 'cnblogs.com'], true)) {
            $blogName = explode('/', $path)[0] ?? '';
            if ($blogName !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $blogName)) {
                return 'https://rpc.cnblogs.com/metaweblog/' . $blogName;
            }
        }
        throw new \Exception('MetaWeblog 地址不正确,请到博客园「设置」页整条复制(https://rpc.cnblogs.com/metaweblog/你的博客名)');
    }

    protected static function publishCnblogs(GeoAuthAccount $acc, GeoContent $content): array
    {
        $c = self::cred($acc);
        $endpoint = self::cnblogsEndpoint($c);
        self::assertCnblogsBlog($c);
        $html = GeoSiteService::mdToHtml((string)$content->body);
        $resp = self::xmlRpc($endpoint, 'metaWeblog.newPost', [
            'geo', (string)($c['username'] ?? ''), (string)($c['token'] ?? ''),
            ['title' => (string)$content->title, 'description' => $html, 'categories' => ['[随笔分类]GEO']],
            true, // 直接发布
        ]);
        self::assertCnblogsRpc($resp, '发布');
        $postId = self::xmlRpcScalar($resp);
        if ($postId === '' || $postId === '0' || !preg_match('/^[A-Za-z0-9_-]+$/', $postId)) {
            throw new \Exception('博客园未返回文章 ID，可能尚未开通博客');
        }
        $blogName = basename($endpoint); // 端点已归一化,末段必为博客名
        $url = "https://www.cnblogs.com/{$blogName}/p/{$postId}.html";
        return ['status' => 'published', 'url' => $url, 'note' => '已通过 MetaWeblog 发布到博客园'];
    }

    /** 发布前确认账号已开通博客，避免未开通时被标成已发布 */
    protected static function assertCnblogsBlog(array $c): void
    {
        $resp = self::xmlRpc(self::cnblogsEndpoint($c), 'blogger.getUsersBlogs', [
            'geo', (string)($c['username'] ?? ''), (string)($c['token'] ?? ''),
        ]);
        self::assertCnblogsRpc($resp, '校验');
        if (!preg_match('#<name>\s*(blogid|blogName|url)\s*</name>#i', $resp)) {
            throw new \Exception('该账号尚未开通博客园博客，请先到博客园申请开通后再发布');
        }
    }

    protected static function assertCnblogsRpc(string $resp, string $action): void
    {
        $plain = html_entity_decode(strip_tags($resp), ENT_QUOTES);
        if (str_contains($resp, '未开通博客') || str_contains($plain, '未开通博客')
            || str_contains($plain, '申请博客')) {
            throw new \Exception('该账号尚未开通博客园博客，请先到博客园申请开通后再发布');
        }
        if (stripos($resp, '<html') !== false && stripos($resp, 'methodResponse') === false) {
            throw new \Exception('博客园接口未返回有效结果，请确认已开通博客并填写正确的 MetaWeblog 地址');
        }
        if (stripos($resp, '<fault>') !== false || stripos($resp, 'faultString') !== false) {
            throw new \Exception('博客园' . $action . '失败:' . self::xmlFault($resp));
        }
        if (stripos($resp, 'methodResponse') === false) {
            throw new \Exception('博客园接口返回异常，请确认已开通博客并开启 MetaWeblog');
        }
    }

    /** 极简 XML-RPC 客户端(只支持 string/bool/struct/array of string 参数,够 MetaWeblog 用) */
    protected static function xmlRpc(string $endpoint, string $method, array $params): string
    {
        [$host, $port, $ip] = GeoSiteService::resolveSafeHost($endpoint);
        $xmlParams = '';
        foreach ($params as $p) { $xmlParams .= '<param><value>' . self::xmlVal($p) . '</value></param>'; }
        $payload = '<?xml version="1.0" encoding="UTF-8"?><methodCall><methodName>' . htmlspecialchars($method)
            . '</methodName><params>' . $xmlParams . '</params></methodCall>';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=UTF-8'],
            CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"],
        ]);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) throw new \Exception('请求 MetaWeblog 接口失败:' . $err);
        if ($code >= 300 && $code < 400) {
            // SSRF 防护不跟随重定向:3xx 基本都是端点地址不对(主页/http)
            throw new \Exception('接口地址被重定向(HTTP ' . $code . '),请确认填写的是 https://rpc.cnblogs.com/metaweblog/你的博客名');
        }
        if ($code < 200 || $code >= 300) {
            throw new \Exception('博客园接口返回 HTTP ' . $code);
        }
        return (string)$resp;
    }

    protected static function xmlVal($v): string
    {
        if (is_bool($v)) return '<boolean>' . ($v ? '1' : '0') . '</boolean>';
        if (is_array($v)) {
            // list => array;map => struct(不用 array_is_list,兼容 PHP 8.0)
            if ($v === [] || array_keys($v) === range(0, count($v) - 1)) {
                $items = '';
                foreach ($v as $i) { $items .= '<value>' . self::xmlVal($i) . '</value>'; }
                return '<array><data>' . $items . '</data></array>';
            }
            $members = '';
            foreach ($v as $k => $i) {
                $members .= '<member><name>' . htmlspecialchars((string)$k) . '</name><value>' . self::xmlVal($i) . '</value></member>';
            }
            return '<struct>' . $members . '</struct>';
        }
        return '<string>' . htmlspecialchars((string)$v) . '</string>';
    }

    protected static function xmlFault(string $resp): string
    {
        if (preg_match('#faultString.*?<string>(.*?)</string>#s', $resp, $m)) return trim(strip_tags($m[1]));
        return '接口返回异常';
    }

    /** 从 methodResponse 取第一个标量(文章 id)，避免误匹配 fault 或 HTML 里的 string */
    protected static function xmlRpcScalar(string $resp): string
    {
        if (preg_match('#<methodResponse>.*?<params>.*?<value>\s*<(string|i4|int)>([^<]*)</\1>#s', $resp, $m)) {
            return trim(html_entity_decode($m[2], ENT_QUOTES));
        }
        return '';
    }

    // ---------- 语雀(开放 API,个人 Token) ----------

    /**
     * 语雀 REST 调用。凭据:token(X-Auth-Token)、repo_id(知识库 id 或 namespace)。
     * @throws \Exception 网络失败或接口返回非 2xx
     */
    protected static function yuqueRequest(array $c, string $method, string $path, array $body = []): array
    {
        $token = trim((string)($c['token'] ?? ''));
        if ($token === '') throw new \Exception('缺少语雀 Token');
        $ch = curl_init('https://www.yuque.com/api/v2' . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'X-Auth-Token: ' . $token,
                'Content-Type: application/json',
                'User-Agent: imai-geo', // 语雀强制要求 UA,缺失会被拒
            ],
        ];
        if ($body) $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) throw new \Exception('请求语雀接口失败:' . $err);
        $data = json_decode((string)$resp, true) ?: [];
        if ($code < 200 || $code >= 300) {
            throw new \Exception('语雀接口返回 ' . $code . ':' . ($data['message'] ?? substr((string)$resp, 0, 120)));
        }
        return $data;
    }

    /**
     * repo_id 归一:数字 id 原样;完整 namespace(用户名/知识库路径)原样;
     * 只填了知识库路径一段时,自动用 Token 所属账号的 login 补全前缀
     * (用户常只抄地址栏最后一段,直接 POST /repos/{单段}/docs 必 404)。
     * @return array{0: string, 1: string} [归一后的 repo, URL 逐段编码后的路径]
     */
    protected static function yuqueRepo(array $c): array
    {
        $repo = trim((string)($c['repo_id'] ?? ''), " \t\n\r/");
        if ($repo === '') throw new \Exception('缺少语雀知识库 ID');
        if (!is_numeric($repo) && strpos($repo, '/') === false) {
            $me = self::yuqueRequest($c, 'GET', '/user');
            $login = trim((string)($me['data']['login'] ?? ''));
            if ($login !== '') $repo = $login . '/' . $repo;
        }
        // namespace 里的 / 是路径分隔符,必须逐段编码 —— 整串 rawurlencode 会把它变成 %2F 导致 404
        $path = implode('/', array_map('rawurlencode', explode('/', $repo)));
        return [$repo, $path];
    }

    protected static function publishYuque(GeoAuthAccount $acc, GeoContent $content): array
    {
        $c = self::cred($acc);
        [$repo, $repoPath] = self::yuqueRepo($c);
        // slug 必须唯一;用内容 id + 时间戳,避免同名文档冲突
        $slug = 'geo-' . (int)$content->id . '-' . date('YmdHis');
        try {
            $res = self::yuqueRequest($c, 'POST', '/repos/' . $repoPath . '/docs', [
                'title' => (string)$content->title,
                'slug' => $slug,
                'public' => 1,
                'format' => 'markdown',
                'body' => (string)$content->body,
            ]);
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                throw new \Exception('语雀知识库「' . $repo . '」不存在或 Token 无权访问:请在授权设置把「知识库路径」'
                    . '填成 数字ID 或 用户名/知识库路径(浏览器地址栏 www.yuque.com/ 后面的完整两段),'
                    . '并确认 Token 勾选了该知识库的读写权限');
            }
            throw $e;
        }
        $docSlug = (string)($res['data']['slug'] ?? $slug);
        // repo_id 传的是数字 id 时拼不出可读链接,交由用户在语雀后台查看
        $url = is_numeric($repo) ? '' : 'https://www.yuque.com/' . trim($repo, '/') . '/' . $docSlug;
        return ['status' => 'published', 'url' => $url, 'note' => '已发布到语雀知识库'];
    }

    // ---------- 百度百家号(自主开发接入) ----------

    protected static function publishBaijiahao(GeoAuthAccount $acc, GeoContent $content): array
    {
        $c = self::cred($acc);
        $fields = http_build_query([
            'app_id' => (string)($c['app_id'] ?? ''),
            'app_token' => (string)($c['app_token'] ?? ''),
            'type' => 'news',
            'title' => mb_substr((string)$content->title, 0, 40),
            'content' => GeoSiteService::mdToHtml((string)$content->body),
        ]);
        $ch = curl_init('https://baijiahao.baidu.com/builderinner/open/resource/article/publish');
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            throw new \Exception('请求百家号接口失败:' . ($curlErr ?: '网络异常'));
        }
        $data = json_decode((string)$resp, true) ?: [];
        // 官方约定 errno=0 成功;文章进入百家号审核流,审核通过后才有链接
        if ((int)($data['errno'] ?? -1) !== 0) {
            $detail = (string)($data['errmsg'] ?? '');
            if (isset($data['data']['params'])) $detail .= '(参数:' . $data['data']['params'] . ')';
            throw new \Exception('百家号发布失败:' . ($detail ?: substr((string)$resp, 0, 120)));
        }
        return ['status' => 'pending', 'url' => '', 'note' => '已提交百家号,平台审核通过后自动发布(链接请在百家号后台查看)'];
    }
}
