<?php

namespace app\api\logic\geo;

use app\common\logic\BaseLogic;
use app\common\model\geo\GeoAuthAccount;
use app\common\model\user\User;
use app\common\service\geo\GeoCredentialService;

/**
 * GEO 授权账号(Web API 发布通道):商家把自己在各内容平台的开发者凭据
 * 授权给系统,媒体库投稿时若对应平台已授权且开启,则用商家自己的账号
 * 通过平台官方 API 直发(0 费用);未授权则不可投递(媒体代发已下线)。
 *
 * 平台清单基于 2025-2026 官方开放能力调研:
 * - ok      有官方发布 API,可授权接入
 * - limited 官方接口严重受限(如微博仅"第三方分享")
 * - closed  曾有开放平台但已暂停新接入(如企鹅号)
 * - aiphone 无官方发布 API,但系统的「AI 手机」可代为发布(媒体库置顶那四家)
 * - none    无官方发布 API(仅 cookie 逆向灰色方案,不接入)
 * 凭据存 geo_auth_account.credentials(JSON),按创建用户归属,不随个人/企业空间切换隔离。
 * secret=1 的敏感字段经 GeoCredentialService(AES-256-GCM)加密后入库,读取统一在
 * GeoAuthPublishService::cred() 解密;列表/详情只回 has_fields 布尔映射,永不回传明文。
 * 目标库无 GEO 历史数据,不做旧明文迁移。
 */
class GeoAuthLogic extends BaseLogic
{
    public const PLATFORMS = [
        // ---- 有官方发布 API ----
        'wechat_oa' => [
            'label' => '微信公众号',
            'status' => 'ok',
            'forms' => ['article'],
            'auth_mode' => 'key',
            'publish' => 1,
            'auth_only' => 1,
            'fields' => [
                ['key' => 'app_id', 'label' => 'AppID(开发者ID)', 'placeholder' => '以 wx 开头的一串字符'],
                ['key' => 'app_secret', 'label' => 'AppSecret(开发者密钥)', 'secret' => 1, 'placeholder' => '32 位字符,只在重置时显示一次'],
            ],
            'need' => '已认证的服务号/订阅号(个人主体无发布接口)',
            'doc' => 'https://mp.weixin.qq.com',
            'steps' => [
                '用公众号管理员微信,登录 mp.weixin.qq.com',
                '左下角「设置与开发」→「开发接口管理」→「基本配置」',
                '复制「开发者ID(AppID)」填到下面;再点 AppSecret 的「重置」,扫码后复制新密钥填到下面(密钥只显示这一次,务必先粘贴过来)',
                '同一页往下找到「IP白名单」,点「修改」把服务器出口 IP 加进去,否则接口会报 40164',
                '保存后点本页的「检测」,显示"凭据有效"即接入成功',
            ],
            'tip' => '需已认证的组织主体公众号;发布走草稿箱+自动群发,服务器 IP 需加入公众号后台「IP 白名单」'
        ],
        'cnblogs' => [
            'label' => '博客园',
            'status' => 'ok',
            'forms' => ['article'],
            'auth_mode' => 'key',
            'publish' => 1,
            'fields' => [
                ['key' => 'blog_url', 'label' => 'MetaWeblog 地址', 'placeholder' => 'https://rpc.cnblogs.com/metaweblog/你的博客名'],
                ['key' => 'username', 'label' => '登录用户名', 'placeholder' => '博客园登录名(不是昵称)'],
                ['key' => 'token', 'label' => 'MetaWeblog 访问令牌', 'secret' => 1, 'placeholder' => '在设置页开启后复制'],
            ],
            'need' => '任意博客园账号(个人号即可,无需企业资质)',
            'doc' => 'https://i.cnblogs.com/settings',
            'steps' => [
                '登录博客园,进入「管理」→「设置」页(i.cnblogs.com/settings)',
                '找到「MetaWeblog 访问令牌」,点开启并复制这串令牌',
                '同一页可看到「MetaWeblog 访问地址」,整条复制填到下面',
                '「登录用户名」填你的博客园登录名',
                '保存后点「检测」,显示"MetaWeblog 凭据有效"即接入成功',
            ],
            'tip' => '需先开通博客园博客；再在后台「设置」页开启 MetaWeblog 访问令牌。未开通博客时发布会失败'
        ],
        'baijiahao' => [
            'label' => '百度百家号',
            'status' => 'ok',
            'forms' => ['article', 'video'],
            'auth_mode' => 'key',
            'publish' => 1,
            'fields' => [
                ['key' => 'app_id', 'label' => 'app_id', 'placeholder' => '开发者中心「接入信息」里的 app_id'],
                ['key' => 'app_token', 'label' => 'app_token', 'secret' => 1, 'placeholder' => '同页的 app_token'],
            ],
            'need' => '已通过百家号「自主开发接入」审核的账号(需企业资质,审核约 1-3 个工作日)',
            'doc' => 'https://baijiahao.baidu.com',
            'steps' => [
                '登录百家号后台 baijiahao.baidu.com,进入「开发者中心」',
                '申请「自主开发接入」权限,按指引提交主体资质,等待审核通过',
                '通过后在「接入信息 / 密钥管理」页拿到 app_id 与 app_token,填到下面',
                '保存即可(百家号没有免费的校验接口,以第一次发布结果为准)',
                '注意:发出去的文章会进入百家号统一审核,审核通过后才有链接,需到百家号后台查看',
            ],
            'tip' => '百家号后台申请「自主开发接入」开发者权限后获取凭据;发文走平台统一审核'
        ],
        'yuque' => [
            'label' => '语雀',
            'status' => 'ok',
            'forms' => ['article'],
            'auth_mode' => 'key',
            'publish' => 1,
            'auth_only' => 1,
            'fields' => [
                ['key' => 'token', 'label' => '个人 Token', 'secret' => 1, 'placeholder' => '账户设置 → Token → 新建'],
                ['key' => 'repo_id', 'label' => '知识库路径', 'placeholder' => '形如 你的用户名/知识库路径'],
            ],
            'need' => '任意语雀账号(个人号即可)',
            'doc' => 'https://www.yuque.com/settings/tokens',
            'steps' => [
                '登录语雀,打开 www.yuque.com/settings/tokens',
                '点「新建 Token」,勾选知识库的读写权限,创建后复制这串 Token 填到下面(只显示一次)',
                '打开你要发文的知识库,看浏览器地址栏 www.yuque.com/后面的部分,例如 zhangsan/geo,整段填到「知识库路径」',
                '保存后点「检测」,会显示你的语雀账号名,说明接入成功',
            ],
            'tip' => '语雀开放 API:个人 Token 即可发文档,发布后可公开访问,适合做品牌知识沉淀'
        ],
        'xhs' => [
            'label' => '小红书',
            'status' => 'aiphone',
            'forms' => ['article', 'video'],
            'auth_mode' => '',
            'publish' => 0,
            'fields' => [],
            'tip' => '小红书未开放内容发布 API;本系统通过「AI 手机」用你自己的账号发布图文/视频笔记'
        ],
        'douyin_phone' => [
            'label' => '抖音',
            'status' => 'aiphone',
            'forms' => ['video', 'article'],
            'auth_mode' => '',
            'publish' => 0,
            'fields' => [],
            'tip' => '走「AI 手机」用你自己的抖音账号发布(视频/图文)'
        ],
        'kuaishou_phone' => [
            'label' => '快手',
            'status' => 'aiphone',
            'forms' => ['video', 'article'],
            'auth_mode' => '',
            'publish' => 0,
            'fields' => [],
            'tip' => '走「AI 手机」用你自己的快手账号发布'
        ],
        'wx_channels' => [
            'label' => '微信视频号',
            'status' => 'aiphone',
            'forms' => ['video'],
            'auth_mode' => '',
            'publish' => 0,
            'fields' => [],
            'tip' => '视频号无对外发布 API(助手仅限本人后台);本系统通过「AI 手机」代发'
        ],
        // 「暂不可接入」目录已移除(2026-08-18):只展示可授权平台与 AI 手机渠道,
        // 无官方发布 API 的平台不再罗列
    ];

    protected static function scope(int $userId): array
    {
        $teamId = (int)User::where('id', $userId)->value('team_id');
        return [$userId, $teamId];
    }

    protected static function spaceQuery(int $userId)
    {
        // 不能用 newQuery():它会在软删过滤之上另起全新查询,把 delete_time 条件丢掉,
        // 已解除授权的行会继续出现在列表里(解除后仍显示"已授权"的根因)
        return GeoAuthAccount::where('user_id', $userId);
    }

    /** 平台目录 + 当前用户的授权状态(设置-授权账号页数据源,不随空间切换变化) */
    public static function platforms(int $userId): array
    {
        $accounts = self::spaceQuery($userId)->order('id', 'asc')->select()->toArray();
        $byPlatform = [];
        foreach ($accounts as $a) {
            $byPlatform[$a['platform']] = $a;
        }
        $out = [];
        foreach (self::PLATFORMS as $code => $meta) {
            $acc = $byPlatform[$code] ?? null;
            // 凭据脱敏:只回传"各字段是否已填"的布尔映射,永不回传值(密钥已是密文也不回)
            $hasFields = [];
            if ($acc) {
                $stored = json_decode((string)$acc['credentials'], true) ?: [];
                foreach ($meta['fields'] as $f) {
                    $hasFields[$f['key']] = trim((string)($stored[$f['key']] ?? '')) !== '';
                }
            }
            $out[] = [
                'platform' => $code,
                'label' => $meta['label'],
                'status' => $meta['status'],
                'forms' => $meta['forms'],
                'auth_mode' => $meta['auth_mode'],
                'can_publish' => (int)$meta['publish'],
                'fields' => array_map(fn($f) => [
                    'key' => $f['key'],
                    'label' => $f['label'],
                    'secret' => (int)($f['secret'] ?? 0),
                    'placeholder' => (string)($f['placeholder'] ?? '')
                ], $meta['fields']),
                'tip' => $meta['tip'],
                // 授权引导:need=需要什么资质、doc=去哪拿凭据、steps=一步步怎么做
                'need' => (string)($meta['need'] ?? ''),
                'doc' => (string)($meta['doc'] ?? ''),
                'steps' => (array)($meta['steps'] ?? []),
                // 1=只能用商家自己的账号发(未授权时媒体库里不可投递,平台无法代发)
                'auth_only' => (int)($meta['auth_only'] ?? 0),
                'authorized' => $acc ? 1 : 0,
                'enabled' => $acc ? (int)$acc['enabled'] : 0,
                'account_id' => $acc ? (int)$acc['id'] : 0,
                'account_name' => $acc ? (string)$acc['name'] : '',
                'last_check' => $acc ? (string)$acc['last_check'] : '',
                // 凭据脱敏:只回传"哪些字段已填"(布尔映射),不回传值
                'has_fields' => $hasFields,
            ];
        }
        return $out;
    }

    /** 保存(新增/编辑)授权;任一字段留空表示沿用旧值,密钥有值则加密后入库 */
    public static function save(int $userId, array $p)
    {
        [$uid, $teamId] = self::scope($userId);
        $platform = (string)($p['platform'] ?? '');
        $meta = self::PLATFORMS[$platform] ?? null;
        if (!$meta) {
            self::setError('未知平台');
            return false;
        }
        if ($meta['status'] === 'aiphone') {
            self::setError($meta['label'] . ' 无需 Web 授权,请在「AI 手机」中绑定该平台账号后从媒体库投稿');
            return false;
        }
        if (!in_array($meta['status'], ['ok', 'limited'])) {
            self::setError($meta['label'] . ' 未开放官方发布接口,暂不支持授权');
            return false;
        }

        // 优先取活跃行;无则恢复该用户同平台最近一条软删行,避免 uk_active_key 下堆重复历史
        $row = self::spaceQuery($userId)->where('platform', $platform)->order('id', 'desc')->findOrEmpty();
        if ($row->isEmpty()) {
            $trashed = GeoAuthAccount::withTrashed()
                ->where('platform', $platform)
                ->where('user_id', $uid)
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$trashed->isEmpty()) {
                $trashed->restore();
                $row = $trashed;
            }
        }
        $old = $row->isEmpty() ? [] : (json_decode((string)$row->credentials, true) ?: []);
        $cred = $old;
        $in = (array)($p['credentials'] ?? []);
        try {
            foreach ($meta['fields'] as $f) {
                $v = trim((string)($in[$f['key']] ?? ''));
                if ($v !== '') {
                    // 密钥字段一律加密后入库:不降级明文,也不做 isCipher 前缀旁路
                    // (用户新填的 secret 恰好以 v1: 开头时会被误判为密文而明文落库)
                    $cred[$f['key']] = !empty($f['secret'])
                        ? GeoCredentialService::encrypt($v) : $v;
                }
                // 留空 → 沿用旧值(编辑页约定「留空表示不修改」)
            }
        } catch (\Throwable $e) {
            // 密钥未配置/长度错误:宁可保存失败,绝不落明文
            self::setError($e->getMessage());
            return false;
        }
        // 必填校验看合并后的值:新建须填齐;编辑留空已沿用旧值,旧值也空才报错
        foreach ($meta['fields'] as $f) {
            if (trim((string)($cred[$f['key']] ?? '')) === '') {
                self::setError('请填写 ' . $f['label']);
                return false;
            }
        }
        $data = [
            'name' => trim((string)($p['name'] ?? '')) ?: $meta['label'],
            'credentials' => json_encode($cred, JSON_UNESCAPED_UNICODE),
            // 编辑时未传 enabled 保持原状态(新建默认启用),避免编辑凭据把已停用授权静默开启
            'enabled' => isset($p['enabled']) ? (int)(bool)$p['enabled'] : (int)($row->isEmpty() ? 1 : $row->enabled),
            'update_time' => time(),
        ];
        if (!$row->isEmpty()) {
            $row->save($data);
            return ['id' => (int)$row->id];
        }
        $row = GeoAuthAccount::create($data + [
            'user_id' => $uid,
            'team_id' => $teamId,
            'platform' => $platform,
            'last_check' => '',
            'create_time' => time(),
        ]);
        return ['id' => (int)$row->id];
    }

    /** 开关(仅切换 enabled,不动凭据) */
    public static function toggle(int $userId, int $id)
    {
        $row = self::spaceQuery($userId)->where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('授权不存在');
            return false;
        }
        $row->enabled = (int)$row->enabled ? 0 : 1;
        $row->update_time = time();
        $row->save();
        return ['enabled' => (int)$row->enabled];
    }

    public static function delete(int $userId, int $id): bool
    {
        $row = self::spaceQuery($userId)->where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('授权不存在');
            return false;
        }
        $row->delete();
        return true;
    }

    /** 连通性检测(能真实校验的平台就校验,其余标记"已保存") */
    public static function check(int $userId, int $id): array
    {
        $row = self::spaceQuery($userId)->where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) return ['ok' => false, 'msg' => '授权不存在'];
        $res = \app\common\service\geo\GeoAuthPublishService::check($row);
        $row->last_check = mb_substr(($res['ok'] ? '✓ ' : '✗ ') . $res['msg'] . ' @' . date('m-d H:i'), 0, 250);
        $row->update_time = time();
        $row->save();
        return $res;
    }

    /**
     * 只能用商家自己账号发布的平台 code => label(如公众号、语雀)。
     * 这类媒体平台方无法代发,未授权时必须在投稿前拦下并引导去授权。
     */
    public static function authOnlyPlatforms(): array
    {
        $out = [];
        foreach (self::PLATFORMS as $code => $meta) {
            if (!empty($meta['auth_only'])) $out[$code] = $meta['label'];
        }
        return $out;
    }

    /** 已打通官方直发的平台 code => label(公众号/博客园/百家号/语雀) */
    public static function publishablePlatforms(): array
    {
        $out = [];
        foreach (self::PLATFORMS as $code => $meta) {
            if (!empty($meta['publish'])) $out[$code] = $meta['label'];
        }
        return $out;
    }

    /** 当前用户已授权且开启的平台 => 账号行(投稿路由用,不随空间切换变化) */
    public static function enabledMap(int $userId): array
    {
        $rows = self::spaceQuery($userId)->where('enabled', 1)->select()->toArray();
        $map = [];
        foreach ($rows as $r) {
            $meta = self::PLATFORMS[$r['platform']] ?? null;
            if ($meta && $meta['publish']) $map[$r['platform']] = $r; // 只路由到已实现自动发布的平台
        }
        return $map;
    }
}
