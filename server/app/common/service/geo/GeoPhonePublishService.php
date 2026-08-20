<?php

namespace app\common\service\geo;

use app\common\enum\DeviceEnum;
use app\common\model\geo\GeoContent;
use app\common\model\geo\GeoMedia;
use app\common\model\geo\GeoPublish;
use app\common\model\geo\GeoVideoTask;
use app\common\model\sv\SvAccount;
use app\common\model\sv\SvMatrixMediaSetting;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvPublishSettingDetail;

/**
 * GEO → AI 手机发布桥接。
 *
 * 背景：geo_media 里小红书/抖音/快手/视频号(type=ai_phone)此前是【假接入】——
 * GeoLogic::publishCreate() 直接 continue 跳过它们，publishPhoneRegister() 只往
 * geo_publish 写一条台账记录，error_msg 却写死"已下发 AI 手机发布任务"，
 * 后端实际上一个设备任务都没下发，内容永远到不了任何真机。
 *
 * 本服务把 GEO 内容接到系统已有的矩阵发布链路上：
 *   GEO 素材 → sv_matrix_media_setting(媒体集) → PublishLogic::add(发布计划)
 *   → sv_publish_setting_account(按账号) → sv_publish_setting_detail(明细，设备执行)
 * 发布结果由 syncBack() 回填 geo_publish.status / publish_time
 * (设备端不回传文章链接,published_url 只能留空)。
 */
class GeoPhonePublishService
{
    /**
     * geo_media.provider_code → 设备侧账号类型 + 支持的内容形态。
     *
     * 键用 provider_code 而不是 platform_code：前端 content-manage.vue 的
     * AI_APP_TYPE 一直是按 provider_code 映射的(sph/xhs/douyin/kuaishou)，
     * 两边必须同源，否则就是两套会各自漂移的平台映射。
     * 账号类型取 DeviceEnum::ACCOUNT_TYPE_*（视频号=1 小红书=3 抖音=4 快手=5）。
     */
    public const PLATFORMS = [
        'xhs'      => ['type' => DeviceEnum::ACCOUNT_TYPE_XHS, 'label' => '小红书', 'forms' => ['article', 'video']],
        'douyin'   => ['type' => DeviceEnum::ACCOUNT_TYPE_DY,  'label' => '抖音',   'forms' => ['article', 'video']],
        'kuaishou' => ['type' => DeviceEnum::ACCOUNT_TYPE_KS,  'label' => '快手',   'forms' => ['video']],
        'sph'      => ['type' => DeviceEnum::ACCOUNT_TYPE_SPH, 'label' => '视频号', 'forms' => ['video']],
    ];

    /** 媒体名兜底：provider_code 缺失的老数据按名称认 */
    protected const NAME_FALLBACK = [
        '小红书' => 'xhs', '抖音' => 'douyin', '快手' => 'kuaishou', '视频号' => 'sph',
    ];

    /** 解析 geo_media → provider_code；认不出返回 '' */
    public static function platformCodeOf(GeoMedia $media): string
    {
        $code = (string)$media->provider_code;
        if (isset(self::PLATFORMS[$code])) {
            return $code;
        }
        foreach (self::NAME_FALLBACK as $name => $c) {
            if (str_contains((string)$media->name, $name)) {
                return $c;
            }
        }
        return '';
    }

    /**
     * 该媒体是否支持所选内容形态。
     * 以 geo_media.content_form 为准(运营可在后台调整),常量里的 forms 只作兜底。
     */
    public static function supports(GeoMedia $media, string $platformCode, string $mediaType): bool
    {
        $forms = array_values(array_filter(explode(',', (string)$media->content_form)));
        if (!$forms) {
            $forms = self::PLATFORMS[$platformCode]['forms'] ?? [];
        }
        return in_array($mediaType, $forms, true);
    }

    /**
     * 用户在某平台已绑定、且设备在线可用的账号。
     * 前端投稿弹窗据此让用户勾选要发到哪几个号。
     */
    public static function accounts(int $userId, string $platformCode): array
    {
        $type = self::PLATFORMS[$platformCode]['type'] ?? 0;
        if (!$type) {
            return [];
        }
        return SvAccount::where('user_id', $userId)->where('type', $type)
            ->field('id,account,account_no,nickname,avatar,device_code,status,is_verified')
            ->order('id desc')->select()->toArray();
    }

    /**
     * 把一条 GEO 投递真正下发到 AI 手机。
     *
     * @param array $accounts 前端选中的账号，元素形如 ['account'=>..., 'type'=>...]
     * @param array $opt      ['images'=>string[] 图文配图, 'publish_date'=>'Y-m-d', 'times'=>['HH:MM-HH:MM']]
     * @return int 建出来的 sv_publish_setting.id
     * @throws \Exception 素材缺失/账号无效/平台不支持
     */
    public static function dispatch(int $userId, GeoPublish $rec, array $accounts, array $opt = []): int
    {
        $media = GeoMedia::findOrEmpty((int)$rec->media_id);
        $code = self::platformCodeOf($media);
        if ($code === '') {
            throw new \Exception('该媒体未配置 AI 手机平台标识');
        }
        $mediaType = (string)$rec->media_type ?: 'article';
        if (!self::supports($media, $code, $mediaType)) {
            $label = self::PLATFORMS[$code]['label'];
            throw new \Exception("「{$label}」不支持" . ($mediaType === 'video' ? '视频' : '图文') . '投稿');
        }
        if (!$accounts) {
            throw new \Exception('请先选择要发布的账号');
        }

        // geo 分支 batchPushlishAccount 的账号分配(allocateAllToTimeSlots)按 acc['id']
        // 计数与轮转:只传 account/type 会让全部账号挤在同一个空键上互相覆盖,
        // 多选账号时实际只发一个号。这里回查 sv_account 补 id,顺带核验归属与平台类型。
        $accType = self::PLATFORMS[$code]['type'];
        $label = self::PLATFORMS[$code]['label'];
        $want = array_values(array_unique(array_filter(array_map(
            fn($a) => trim((string)($a['account'] ?? '')), $accounts))));
        if (!$want) {
            throw new \Exception('请先选择要发布的账号');
        }
        $rows = SvAccount::where('user_id', $userId)->where('type', $accType)
            ->whereIn('account', $want)->field('id,account,type')->select()->toArray();
        $byAccount = array_column($rows, null, 'account');
        $accList = [];
        foreach ($want as $acc) {
            if (!isset($byAccount[$acc])) {
                throw new \Exception("账号 {$acc} 不存在,请先在「AI 手机」设备关联中添加该{$label}账号");
            }
            $accList[] = ['id' => (int)$byAccount[$acc]['id'], 'account' => $acc, 'type' => $accType];
        }

        // ---- 组素材(按 geo 分支矩阵链路的结构约定) ----
        [$mediaGroup, $copywriting, $svMediaType] = self::buildAssets($rec, $mediaType, $opt);

        // geo 分支同一时段同一平台只能排一个号(allocateAllToTimeSlots:每时段每 type 一个,
        // 且每个账号的发布次数=分给它的媒体组数)。一篇内容发 N 个号 ⇒ 媒体组复制 N 份
        // 并把时段铺成 N 个,否则多选的账号会被静默丢弃(任务建成但永远不下发)。
        $n = count($accList);
        $set = SvMatrixMediaSetting::create([
            'user_id' => $userId,
            'name' => 'GEO-' . mb_substr((string)$rec->title, 0, 30),
            'media_type' => $svMediaType,
            'media_count' => $n,
            'media_url' => json_encode(array_fill(0, $n, $mediaGroup), JSON_UNESCAPED_UNICODE),
            'copywriting' => json_encode([$copywriting], JSON_UNESCAPED_UNICODE),
            'extra' => json_encode(['from' => 'geo', 'geo_publish_id' => (int)$rec->id], JSON_UNESCAPED_UNICODE),
            'create_time' => time(), 'update_time' => time(),
        ]);

        // ---- 建发布计划 ----
        // 默认今天立即发；前端可传 publish_date/times 让用户排期(GeoLogic 空值传 '')
        $date = (string)($opt['publish_date'] ?? '') ?: date('Y-m-d');
        $times = array_values(array_filter(array_map('strval', (array)($opt['times'] ?? []))));
        $params = [
            'name' => 'GEO投稿-' . mb_substr((string)$rec->title, 0, 20),
            'accounts' => $accList,
            'matrix_media_setting_id' => (int)$set->id,
            'video_setting_id' => 0,
            'media_type' => $svMediaType,
            'time_config' => self::expandTimeConfig($date, $times, $n),
            'scene' => 0,
            'status' => 1,
            'date_type' => 0,
            'data_type' => 2,     // 2=矩阵媒体集
            'publish_frep' => 2,
            // 立即执行:同设备同时段旧任务标失败(任务被其他任务覆盖)
            'task_exec_type' => 1,
        ];

        // PublishLogic::add() 全程以 self::$uid 静态上下文为操作人(媒体集归属校验、
        // 账号校验、落库 user_id 全看它)。web 请求里 LoginMiddleware 已赋过值,
        // 这里显式再钉一次,防止队列/cron 等无登录态场景 uid=0 导致直接建单失败。
        \app\api\logic\device\PublishLogic::$uid = $userId;
        $ok = \app\api\logic\device\PublishLogic::add($params);
        if (!$ok) {
            throw new \Exception('下发 AI 手机发布任务失败:' . \app\api\logic\device\PublishLogic::getError());
        }
        $publishId = (int)(\app\api\logic\device\PublishLogic::getReturnData()['id'] ?? 0);
        if (!$publishId) {
            throw new \Exception('下发成功但未拿到任务号');
        }

        // 回写关联，供 syncBack 反查
        $rec->provider_order = (string)$publishId;
        $rec->account = implode(',', array_column($accList, 'account'));
        $rec->error_msg = '';
        $rec->update_time = time();
        $rec->save();

        return $publishId;
    }

    /**
     * 取素材,组装成 geo 分支矩阵链路约定的结构。
     * video → geo_video_task.video_url（合成完成才有）
     * article → 必须由调用方给图（geo_content 只有 markdown 正文，没有任何图片字段），
     *           给不出图就明确报错，不能像旧逻辑那样静默登记一条永远不会发的记录。
     *
     * geo 分支约定(见 PublishLogic::batchPushlishAccount/_getMedias、建表注释):
     *  - media_type:1=视频 2=图文(与 product 旧桥接的 1图文/2视频 正好相反,勿回填);
     *  - media_url 元素:['url'=>[...]],视频组 url[0]=封面 url[1]=视频地址,
     *    图文组 url=整组图片(明细 material_url 逗号拼接,pic 取 url[0]);
     *  - copywriting 元素:['title','content','topic'=>[],'is_title_show'],设备协议里
     *    title/body/tag 是三个独立字段,不能再像旧版那样揉成一个字符串。
     * @return array [mediaGroup, copywritingItem, svMediaType(1=视频 2=图文)]
     */
    protected static function buildAssets(GeoPublish $rec, string $mediaType, array $opt): array
    {
        $content = GeoContent::findOrEmpty((int)$rec->content_id);
        $copywriting = self::copywriting((string)$rec->title, (string)$content->body, (string)$content->tags);

        if ($mediaType === 'video') {
            // 视频素材优先取直传 URL(本地上传/素材库/创作库选择,GEO 短视频合成已下线);
            // 兼容历史记录仍按 video_id 反查旧短视频任务
            $url = trim((string)($opt['video_url'] ?? ''));
            $cover = trim((string)($opt['video_cover'] ?? ''));
            if ($url === '' && (int)$rec->video_id > 0) {
                $vt = GeoVideoTask::findOrEmpty((int)$rec->video_id);
                $url = (string)$vt->video_url;
            }
            if ($url === '') {
                throw new \Exception('请先选择视频素材(本地上传或从素材库/创作库选择)');
            }
            if ($cover === '') {
                $cover = (string)$content->cover_url;
            }
            // url[0]=封面(没有就不给,设备按空封面处理),url[1]=视频地址
            return [['url' => [$cover, $url]], $copywriting, 1];
        }

        $images = array_values(array_filter(array_map('strval', (array)($opt['images'] ?? []))));
        // 没手动选图就用文章生成时那张 AI 封面图
        if (!$images && (string)$content->cover_url !== '') {
            $images = [(string)$content->cover_url];
        }
        if (!$images) {
            $hint = (string)$content->cover_status === 'pending'
                ? '封面图还在生成中,请稍候再投稿'
                : '请先为该文章生成或上传封面图';
            throw new \Exception('图文投稿需要配图:' . $hint);
        }
        return [['url' => $images], $copywriting, 2];
    }

    /** markdown 正文压成平台文案结构:标题/去标记正文/话题标签 */
    protected static function copywriting(string $title, string $body, string $tagsJson): array
    {
        $plain = (string)preg_replace('/[#*>`\[\]()\-]+/u', ' ', $body);
        $plain = trim((string)preg_replace('/\s+/u', ' ', $plain));
        $tags = json_decode($tagsJson, true) ?: [];
        return [
            'title' => mb_substr($title, 0, 50),
            'content' => mb_substr($plain, 0, 800),
            'topic' => array_slice(array_map('strval', $tags), 0, 8),
            'is_title_show' => '1',
        ];
    }

    /**
     * 把"1 篇内容发 N 个号"铺成 N 个连续时段:geo 分支每个时段同一平台只能排一个号,
     * 时段数 < 账号数时多选的账号会被静默跳过。首个时段用用户指定值(默认 5 分钟后开始、
     * 30 分钟宽),后续时段按"上一个结束 +5 分钟"顺排,跨天自动滚入下一天。
     * @return array time_config([['date'=>'Y-m-d','times'=>['HH:MM-HH:MM',...]],...])
     */
    protected static function expandTimeConfig(string $date, array $times, int $n): array
    {
        $day = strtotime($date) ?: strtotime(date('Y-m-d'));
        $windows = [];
        foreach ($times as $t) {
            if (preg_match('/^(\d{1,2}:\d{2})-(\d{1,2}:\d{2})$/', trim($t), $m)) {
                $st = strtotime(date('Y-m-d', $day) . ' ' . $m[1]);
                $et = strtotime(date('Y-m-d', $day) . ' ' . $m[2]);
                if ($et > $st) $windows[] = [$st, $et];
            }
        }
        if (!$windows) {
            $st = time() + 300;
            if ($day > strtotime(date('Y-m-d'))) {
                // 排期到未来日期却没给时段:落在当天上午,而不是"现在时刻"
                $st = strtotime(date('Y-m-d', $day) . ' 09:00');
            }
            $windows[] = [$st, $st + 1800];
        }
        while (count($windows) < $n) {
            [$st, $et] = end($windows);
            $len = max(600, $et - $st);
            $nst = $et + 300;
            $windows[] = [$nst, $nst + $len];
        }
        $config = [];
        foreach ($windows as [$st, $et]) {
            $d = date('Y-m-d', $st);
            $config[$d][] = date('H:i', $st) . '-' . date('H:i', $et);
        }
        $out = [];
        foreach ($config as $d => $ts) {
            $out[] = ['date' => $d, 'times' => $ts];
        }
        return $out;
    }

    /**
     * 回执回填：把设备侧的发布结果同步回 geo_publish。
     * 由 GeoPublishSync 定时命令调用(每 30 分钟)。
     *
     * geo 分支 sv_publish_setting_detail.status 状态机(建表注释+设备回调
     * MediaStatusHandler/SphPostTaskOpt 实测):0=待发布 1=已发布 2=发布失败(终态)
     * 3=发布中(已下发待回调) 5=下发失败待重试。与 DeviceEnum::TASK_STATUS_* 不是一套
     * —— product 旧桥接直接套 DeviceEnum 恰好全盘相反(2 是失败被当成成功、3 是发布中
     * 被当成失败),且明细表没有 publish_url/error_msg 列(结果文案在 remark),旧查询
     * 在 geo 分支会直接 SQL 报错。设备端不回传文章链接,published_url 只能留空。
     * @return int 更新条数
     */
    public static function syncBack(): int
    {
        $pending = GeoPublish::where('channel', 'phone')
            ->where('status', 'pending')->where('provider_order', '<>', '')
            ->limit(200)->select();
        $n = 0;
        foreach ($pending as $rec) {
            $setting = (int)$rec->provider_order;
            if (!$setting) continue;
            $rows = SvPublishSettingDetail::where('publish_id', $setting)
                ->field('status,remark,publish_time,exec_time')->select()->toArray();
            if (!$rows) continue;

            $done = array_values(array_filter($rows, fn($r) => (int)$r['status'] === 1));
            $failed = array_values(array_filter($rows, fn($r) => (int)$r['status'] === 2));
            if ($done) {
                $rec->status = 'published';
                // 部分账号成功即按已发布处理(与旧逻辑一致);链接设备侧给不了,保持空
                $rec->published_url = '';
                $rec->publish_time = (int)($done[0]['exec_time'] ?? 0)
                    ?: (strtotime((string)($done[0]['publish_time'] ?? '')) ?: time());
                $rec->save();
                $n++;
            } elseif ($failed && count($failed) === count($rows)) {
                // 全部失败才判失败：发布中(3)/待重试(5)的明细还没终态,继续等
                $rec->status = 'failed';
                $rec->error_msg = mb_substr((string)($failed[0]['remark'] ?? '设备发布失败'), 0, 300);
                $rec->save();
                $n++;
            }
        }
        return $n;
    }
}
