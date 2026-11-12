<?php

namespace app\common\model\aiPersona;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class AiPersonaSynthesisConfig extends BaseModel
{
    protected $name = 'ai_persona_synthesis_config';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    //画面素材: 1-纯AI, 2-AI+素材库, 3-纯素材库, 4-素材库+广场
    const VISUAL_MATERIAL_SOURCE_AI = 1;
    const VISUAL_MATERIAL_SOURCE_AI_MATERIAL = 2;
    const VISUAL_MATERIAL_SOURCE_MATERIAL = 3;
    const VISUAL_MATERIAL_SOURCE_SQUARE = 4;

    //闪剪素材使用方式: 1-自动适配(切片) 2-原图
    const MATERIAL_USE_MODE_SLICE = 1;
    const MATERIAL_USE_MODE_ORIGINAL = 2;

    //文案来源: 1-仿写, 2-AI生成, 3-无需, 4-文案库, 5-热点生成
    const COPYWRITING_SOURCE_IMITATE = 1;
    const COPYWRITING_SOURCE_AI = 2;
    const COPYWRITING_SOURCE_NONE = 3;
    const COPYWRITING_SOURCE_LIBRARY = 4;
    const COPYWRITING_SOURCE_HOTSPOT = 5;

    // 热点取用: 1-最新入库优先（id 降序，同设备不重复，其他设备可同拿） 2-每条只用一次（全局，按入库顺序 id 升序）
    const HOTSPOT_PICK_MODE_LATEST = 1;
    const HOTSPOT_PICK_MODE_ONCE_DEVICE = 2;

    /**
     * 校验文案来源取值，与 AiPersona::requireValidRunMode 同款：脏值一律抛错，不静默兜底
     * @throws \Exception
     */
    public static function requireValidCopywritingSource($value): int
    {
        $invalid = is_bool($value) || is_array($value) || is_object($value)
            || (is_string($value) && !is_numeric(trim($value)));
        $source = $invalid ? 0 : (int)$value;
        $allowed = [
            self::COPYWRITING_SOURCE_IMITATE,
            self::COPYWRITING_SOURCE_AI,
            self::COPYWRITING_SOURCE_NONE,
            self::COPYWRITING_SOURCE_LIBRARY,
            self::COPYWRITING_SOURCE_HOTSPOT,
        ];
        if (!in_array($source, $allowed, true)) {
            throw new \Exception('文案来源不正确，1=找爆款仿写 2=AI直接生成 3=不使用 4=文案库 5=热点生成');
        }
        return $source;
    }

    /**
     * 热点取用规则：缺省/0 视为 1；脏值抛错
     * @throws \Exception
     */
    public static function requireValidHotspotPickMode($value): int
    {
        if ($value === null || $value === '' || $value === false) {
            return self::HOTSPOT_PICK_MODE_LATEST;
        }
        $invalid = is_bool($value) || is_array($value) || is_object($value)
            || (is_string($value) && !is_numeric(trim($value)));
        $mode = $invalid ? -1 : (int)$value;
        if ($mode === 0) {
            return self::HOTSPOT_PICK_MODE_LATEST;
        }
        $allowed = [
            self::HOTSPOT_PICK_MODE_LATEST,
            self::HOTSPOT_PICK_MODE_ONCE_DEVICE,
        ];
        if (!in_array($mode, $allowed, true)) {
            throw new \Exception('热点取用规则不正确，1=最新入库优先 2=每条只用一次');
        }
        return $mode;
    }

    //文案库使用方式: 1-随机, 2-顺序
    const LIBRARY_USE_MODE_RANDOM = 1;
    const LIBRARY_USE_MODE_SEQUENCE = 2;

    //文案库重复规则: 1-每条仅用一次, 2-可重复使用
    const LIBRARY_REUSE_MODE_ONCE = 1;
    const LIBRARY_REUSE_MODE_REPEAT = 2;

    //工作方式: 1-AI合成视频, 2-成品库直发
    const WORK_MODE_AI_SYNTHESIS = 1;
    const WORK_MODE_PRODUCT_DIRECT = 2;

    //成品库使用方式: 1-随机, 2-顺序
    const PRODUCT_USE_MODE_RANDOM = 1;
    const PRODUCT_USE_MODE_SEQUENCE = 2;

    //成品库随机规则: 1-每个成品只用一次, 2-可重复使用
    const PRODUCT_REUSE_MODE_ONCE = 1;
    const PRODUCT_REUSE_MODE_REPEAT = 2;

    //视频封面: 1-默认, 2-AI自动, 3-手动
    const VIDEO_COVER_TYPE_DEFAULT = 1;
    const VIDEO_COVER_TYPE_AI = 2;
    const VIDEO_COVER_TYPE_MANUAL = 3;
    const NEWS_MIXCUT_DURATION_DEFAULT = 10;
    const NEWS_MIXCUT_DURATION_MIN = 5;
    const NEWS_MIXCUT_DURATION_MAX = 300;

    //背景音乐来源: 1-系统音乐库, 2-人设音乐库, 3-不使用背景音乐
    const MUSIC_SOURCE_SYSTEM = 1;
    const MUSIC_SOURCE_PERSONA = 2;
    const MUSIC_SOURCE_NONE = 3;
    const MUSIC_VOLUME_DEFAULT = 0.3;
    const SPEECH_RATE_DEFAULT = 1.0;
    const SPEECH_RATE_MIN = 0.5;
    const SPEECH_RATE_MAX = 2.0;

    //文案生成类型: 1干货科普 2带货种草 3观点评论 4剧情段子 5情感共鸣 6揭秘避坑 7自定义
    const COPYWRITING_GENERATION_TYPE_KNOWLEDGE = 1;
    const COPYWRITING_GENERATION_TYPE_SELL = 2;
    const COPYWRITING_GENERATION_TYPE_OPINION = 3;
    const COPYWRITING_GENERATION_TYPE_SKIT = 4;
    const COPYWRITING_GENERATION_TYPE_EMOTION = 5;
    const COPYWRITING_GENERATION_TYPE_PITFALL = 6;
    const COPYWRITING_GENERATION_TYPE_CUSTOM = 7;
    const COPYWRITING_GENERATION_CUSTOM_MAX = 500;

    protected $json = ['generation_types', 'template_config', 'square_group_ids', 'type_config'];
    protected $jsonAssoc = true;

    /** 每日出片条数：0=未配置。仅无手机模式消费，手机模式仍按发布时段数出片 */
    const DAILY_VIDEO_COUNT_UNSET = 0;
    /** 无手机模式未配置条数时的兜底，取 1 而不是 0，避免云端人设一条都不出 */
    const DAILY_VIDEO_COUNT_CLOUD_DEFAULT = 1;
    const DAILY_VIDEO_COUNT_MAX = 20;

    // 生成类型：1-数字人口播 3-纯素材混剪 4-新闻体混剪（与闪剪 shanjian_type 同值）
    const GENERATION_TYPE_DIGITAL_HUMAN = 1;
    const GENERATION_TYPE_MATERIAL = 3;
    const GENERATION_TYPE_NEWS = 4;

    /** type_config 恒存的三个类型 key，顺序即默认轮转顺序 */
    const TYPE_CONFIG_KEYS = [
        self::GENERATION_TYPE_DIGITAL_HUMAN,
        self::GENERATION_TYPE_MATERIAL,
        self::GENERATION_TYPE_NEWS,
    ];

    /** 单类型每日条数上限，与总数上限同口径 */
    const TYPE_VIDEO_COUNT_MAX = 20;

    /** 一镜到底：仅新闻体混剪支持，开启后该类型每条只用 1 条视频素材 */
    const SINGLE_SHOT_OFF = 0;
    const SINGLE_SHOT_ON = 1;

    /** 指定素材分组：开启后该生成类型只从所选素材分组取素材 */
    const MATERIAL_GROUP_OFF = 0;
    const MATERIAL_GROUP_ON = 1;

    /** 归一每日出片条数：非法或越界回落到未配置(0)，由调用方决定兜底 */
    public static function normalizeDailyVideoCount($value): int
    {
        $count = (int)$value;
        if ($count < 0 || $count > self::DAILY_VIDEO_COUNT_MAX) {
            return self::DAILY_VIDEO_COUNT_UNSET;
        }
        return $count;
    }

    public static function normalizeNewsMixcutDuration($duration): int
    {
        $duration = (int)$duration;
        if ($duration < self::NEWS_MIXCUT_DURATION_MIN || $duration > self::NEWS_MIXCUT_DURATION_MAX) {
            return self::NEWS_MIXCUT_DURATION_DEFAULT;
        }

        return $duration;
    }

    public static function normalizeMusicSource($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            self::MUSIC_SOURCE_SYSTEM,
            self::MUSIC_SOURCE_PERSONA,
            self::MUSIC_SOURCE_NONE,
        ], true) ? $value : self::MUSIC_SOURCE_SYSTEM;
    }

    public static function normalizeMusicVolume($value): float
    {
        if (!is_numeric($value)) {
            return self::MUSIC_VOLUME_DEFAULT;
        }
        $value = round((float)$value, 1);
        if ($value < 0 || $value > 1) {
            return self::MUSIC_VOLUME_DEFAULT;
        }
        return $value;
    }

    public static function normalizeSpeechRate($value): float
    {
        if (!is_numeric($value)) {
            return self::SPEECH_RATE_DEFAULT;
        }
        $value = round((float)$value, 1);
        if ($value < self::SPEECH_RATE_MIN || $value > self::SPEECH_RATE_MAX) {
            return self::SPEECH_RATE_DEFAULT;
        }
        return $value;
    }

    public function getNewsMixcutDurationAttr($value): int
    {
        return self::normalizeNewsMixcutDuration($value);
    }

    public static function copywritingGenerationTypeMap(): array
    {
        return [
            self::COPYWRITING_GENERATION_TYPE_KNOWLEDGE => [
                'label' => '干货科普',
                'desc' => '把卖点讲成知识点，先给结论再讲原因，适合建立专业信任。',
            ],
            self::COPYWRITING_GENERATION_TYPE_SELL => [
                'label' => '带货种草',
                'desc' => '突出使用场景与前后对比，结尾直接引导下单或进店。',
            ],
            self::COPYWRITING_GENERATION_TYPE_OPINION => [
                'label' => '观点评论',
                'desc' => '对行业现象亮明态度，争议感强，评论区互动率高。',
            ],
            self::COPYWRITING_GENERATION_TYPE_SKIT => [
                'label' => '剧情段子',
                'desc' => '小剧情加反转带出产品，完播率高，适合泛流量涨粉。',
            ],
            self::COPYWRITING_GENERATION_TYPE_EMOTION => [
                'label' => '情感共鸣',
                'desc' => '讲人和故事，唤起共鸣后自然带出品牌，转化路径更软。',
            ],
            self::COPYWRITING_GENERATION_TYPE_PITFALL => [
                'label' => '揭秘避坑',
                'desc' => '用「内行人才知道」的口吻拆行业内幕，信任感与收藏率高。',
            ],
            self::COPYWRITING_GENERATION_TYPE_CUSTOM => [
                'label' => '自定义',
                'desc' => '',
            ],
        ];
    }

    public static function normalizeCopywritingGenerationType($value): int
    {
        $value = (int)$value;
        return array_key_exists($value, self::copywritingGenerationTypeMap())
            ? $value
            : self::COPYWRITING_GENERATION_TYPE_KNOWLEDGE;
    }

    public static function normalizeCopywritingGenerationCustom($value): string
    {
        $value = trim((string)$value);
        if (mb_strlen($value, 'UTF-8') > self::COPYWRITING_GENERATION_CUSTOM_MAX) {
            return mb_substr($value, 0, self::COPYWRITING_GENERATION_CUSTOM_MAX, 'UTF-8');
        }
        return $value;
    }

    public static function buildCopywritingGenerationVoice(int $type, string $custom): string
    {
        $type = self::normalizeCopywritingGenerationType($type);
        if ($type === self::COPYWRITING_GENERATION_TYPE_CUSTOM) {
            return self::normalizeCopywritingGenerationCustom($custom);
        }
        return (string)(self::copywritingGenerationTypeMap()[$type]['label'] ?? '');
    }

    // ------------------------------------------------------------------
    // 分类型配置（type_config）：条数 + 背景音乐来源/分组/指定曲目/音量
    // ------------------------------------------------------------------

    /** 单类型条数归一：非法或越界按 0（未选择即不出片） */
    public static function normalizeTypeVideoCount($value): int
    {
        if (is_bool($value) || is_array($value) || is_object($value)) {
            return 0;
        }
        if (is_string($value) && !is_numeric(trim($value))) {
            return 0;
        }
        $count = (int)$value;
        if ($count < 0 || $count > self::TYPE_VIDEO_COUNT_MAX) {
            return 0;
        }
        return $count;
    }

    /**
     * 一镜到底归一到 0/1。
     * 只认显式开启（1 / '1' / true），其余（含 2、'yes'、数组、缺失）一律按关闭，
     * 老数据没有该键时行为必须与改造前完全一致。
     */
    public static function normalizeSingleShot($value): int
    {
        if (is_bool($value)) {
            return $value ? self::SINGLE_SHOT_ON : self::SINGLE_SHOT_OFF;
        }
        if (is_int($value) || is_float($value)) {
            return (int)$value === self::SINGLE_SHOT_ON ? self::SINGLE_SHOT_ON : self::SINGLE_SHOT_OFF;
        }
        if (is_string($value) && trim($value) === '1') {
            return self::SINGLE_SHOT_ON;
        }
        return self::SINGLE_SHOT_OFF;
    }

    /** 指定素材分组开关：只认显式开启（1 / '1' / true），老数据缺键按关闭 */
    public static function normalizeMaterialGroupEnabled($value): int
    {
        return self::normalizeSingleShot($value);
    }

    /**
     * 素材分组 ID 归一：去重、剔除非正整数与非标量，兼容数组 / JSON 字符串。
     *
     * @return array<int, int>
     */
    public static function normalizeMaterialGroupIds($value): array
    {
        if (is_string($value)) {
            $value = trim($value) === '' ? [] : (json_decode($value, true) ?: []);
        }
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $id) {
            if (is_array($id) || is_object($id) || is_bool($id)) {
                continue;
            }
            if (is_string($id) && !is_numeric(trim($id))) {
                continue;
            }
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * 结构归一：补全 1/3/4 三类、裁剪枚举与越界值。
     * 分组是否存在、指定曲目是否上架属于业务校验，在配置保存入口做，这里只管形状。
     */
    public static function normalizeTypeConfig($raw): array
    {
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        $normalized = [];
        foreach (self::TYPE_CONFIG_KEYS as $type) {
            $item = $raw[$type] ?? $raw[(string)$type] ?? [];
            if (!is_array($item)) {
                $item = [];
            }
            $source = self::normalizeMusicSource($item['music_source'] ?? self::MUSIC_SOURCE_SYSTEM);
            $pinned = trim((string)($item['system_music_id'] ?? ''));
            $normalized[$type] = [
                'video_count' => self::normalizeTypeVideoCount($item['video_count'] ?? 0),
                'music_source' => $source,
                // 只有系统音乐库才谈分组与指定曲目，其它来源清空避免脏数据带出
                'music_group_id' => $source === self::MUSIC_SOURCE_SYSTEM ? max(0, (int)($item['music_group_id'] ?? 0)) : 0,
                'system_music_id' => $source === self::MUSIC_SOURCE_SYSTEM ? $pinned : '',
                'music_volume' => self::normalizeMusicVolume($item['music_volume'] ?? self::MUSIC_VOLUME_DEFAULT),
                'speech_rate' => self::normalizeSpeechRate($item['speech_rate'] ?? self::SPEECH_RATE_DEFAULT),
                // 一镜到底只有新闻体混剪有意义，其余类型恒 0，避免拖着一个永不生效的开关
                'single_shot' => $type === self::GENERATION_TYPE_NEWS
                    ? self::normalizeSingleShot($item['single_shot'] ?? self::SINGLE_SHOT_OFF)
                    : self::SINGLE_SHOT_OFF,
                // 指定素材分组：每个生成类型独立配置，关闭时保留已选 ID 方便再次开启
                'material_group_enabled' => self::normalizeMaterialGroupEnabled(
                    $item['material_group_enabled'] ?? self::MATERIAL_GROUP_OFF
                ),
                'material_group_ids' => self::normalizeMaterialGroupIds($item['material_group_ids'] ?? []),
            ];
        }
        // 三类合计不得超过每日上限：整条出片链路都按「当日 ≤ 20 条」估算成本与配额，
        // 单类型各自 0~20 也不能让合计溢出。超出部分按 1/3/4 顺序从后往前截断。
        $budget = self::DAILY_VIDEO_COUNT_MAX;
        foreach (self::TYPE_CONFIG_KEYS as $type) {
            $count = min((int)$normalized[$type]['video_count'], max(0, $budget));
            $normalized[$type]['video_count'] = $count;
            $budget -= $count;
        }
        return $normalized;
    }

    /** 读取配置字段：模型实例、数组（toArray 结果）都吃 */
    public static function configField($config, string $field, $default = null)
    {
        if (is_object($config)) {
            return $config->$field ?? $default;
        }
        if (is_array($config)) {
            return $config[$field] ?? $default;
        }
        return $default;
    }

    /** 启用类型及其轮转顺序（generation_types 是唯一来源，去重并剔除未知值） */
    public static function resolveGenerationOrder($config): array
    {
        $types = self::configField($config, 'generation_types', []);
        if (is_string($types)) {
            $types = json_decode($types, true) ?: [];
        }
        if (!is_array($types)) {
            return [];
        }
        $order = [];
        foreach ($types as $type) {
            $type = (int)$type;
            if (in_array($type, self::TYPE_CONFIG_KEYS, true) && !in_array($type, $order, true)) {
                $order[] = $type;
            }
        }
        return $order;
    }

    /** 是否为显式保存过分类型配置（老数据该字段为 null / 空数组） */
    public static function hasExplicitTypeConfig($config): bool
    {
        $raw = self::configField($config, 'type_config');
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        return is_array($raw) && $raw !== [];
    }

    /**
     * 老数据条数分配：daily_video_count 按启用类型顺序整除，余数从前往后各补 1。
     * 5 条 + [1,4,3] → 2 / 2 / 1；6 条 + [1,4] → 3 / 3（未启用恒 0）。
     *
     * @return array<int, int> 三个类型 key 恒存在
     */
    public static function splitLegacyVideoCount(int $total, array $order): array
    {
        $counts = array_fill_keys(self::TYPE_CONFIG_KEYS, 0);
        $lane = [];
        foreach ($order as $type) {
            $type = (int)$type;
            if (in_array($type, self::TYPE_CONFIG_KEYS, true) && !in_array($type, $lane, true)) {
                $lane[] = $type;
            }
        }
        $total = max(0, min($total, self::DAILY_VIDEO_COUNT_MAX));
        if ($lane === [] || $total <= 0) {
            return $counts;
        }
        $base = intdiv($total, count($lane));
        $remainder = $total % count($lane);
        foreach ($lane as $index => $type) {
            $counts[$type] = $base + ($index < $remainder ? 1 : 0);
        }
        return $counts;
    }

    /**
     * 无手机模式的类型条数：显式配置优先，否则按老数据均分。
     *
     * @return array<int, int>
     */
    public static function resolveCloudTypeCounts($config): array
    {
        if (!self::hasExplicitTypeConfig($config)) {
            return self::splitLegacyVideoCount(
                self::normalizeDailyVideoCount(self::configField($config, 'daily_video_count', 0)),
                self::resolveGenerationOrder($config)
            );
        }
        $counts = [];
        foreach (self::normalizeTypeConfig(self::configField($config, 'type_config')) as $type => $item) {
            $counts[$type] = $item['video_count'];
        }
        return $counts;
    }

    /** 无手机模式单类型条数（未配置按老数据分配结果） */
    public static function resolveCloudTypeCount($config, int $type): int
    {
        $counts = self::resolveCloudTypeCounts($config);
        return (int)($counts[$type] ?? 0);
    }

    /**
     * 按条数把类型交错铺满当日建单队列，避免同一类型连续扎堆。
     * counts{1:2,4:2,3:1} + order[1,4,3] → [1,4,3,1,4]；条数为 0 的类型不出现。
     *
     * @param array<int, int> $counts
     * @param array<int, int> $order
     * @return array<int, int>
     */
    public static function buildCloudTypePlan(array $counts, array $order): array
    {
        $remaining = [];
        foreach ($counts as $type => $count) {
            $type = (int)$type;
            $count = (int)$count;
            if (in_array($type, self::TYPE_CONFIG_KEYS, true) && $count > 0) {
                $remaining[$type] = $count;
            }
        }
        // 顺序以 generation_types 为准；它没列到但配了条数的类型补在末尾，不能悄悄丢条数
        $lane = [];
        foreach ($order as $type) {
            $type = (int)$type;
            if (isset($remaining[$type]) && !in_array($type, $lane, true)) {
                $lane[] = $type;
            }
        }
        foreach (array_keys($remaining) as $type) {
            if (!in_array((int)$type, $lane, true)) {
                $lane[] = (int)$type;
            }
        }
        $plan = [];
        while ($lane !== []) {
            $advanced = false;
            foreach ($lane as $type) {
                if (($remaining[$type] ?? 0) <= 0) {
                    continue;
                }
                $plan[] = $type;
                $remaining[$type]--;
                $advanced = true;
            }
            if (!$advanced) {
                break;
            }
        }
        return $plan;
    }

    /**
     * 单类型的背景音乐配置，缺项回落顶层值（老数据 = 三类继承顶层）。
     *
     * @return array{source:int, group_id:int, pinned_id:string, volume:float}
     */
    public static function resolveTypeMusic($config, int $type): array
    {
        if (!in_array($type, self::TYPE_CONFIG_KEYS, true)) {
            $type = self::GENERATION_TYPE_DIGITAL_HUMAN;
        }
        $raw = self::configField($config, 'type_config');
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        $item = $raw[$type] ?? $raw[(string)$type] ?? [];
        if (!is_array($item)) {
            $item = [];
        }

        $topSource = self::normalizeMusicSource(self::configField($config, 'music_source', self::MUSIC_SOURCE_SYSTEM));
        $topVolume = self::normalizeMusicVolume(self::configField($config, 'music_volume', self::MUSIC_VOLUME_DEFAULT));
        $topPinned = trim((string)self::configField($config, 'system_music_id', ''));

        $source = array_key_exists('music_source', $item)
            ? self::normalizeMusicSource($item['music_source'])
            : $topSource;
        $volume = array_key_exists('music_volume', $item)
            ? self::normalizeMusicVolume($item['music_volume'])
            : $topVolume;
        $pinned = array_key_exists('system_music_id', $item)
            ? trim((string)$item['system_music_id'])
            : $topPinned;
        $groupId = array_key_exists('music_group_id', $item)
            ? max(0, (int)$item['music_group_id'])
            : 0;

        if ($source !== self::MUSIC_SOURCE_SYSTEM) {
            $pinned = '';
            $groupId = 0;
        }

        return [
            'source' => $source,
            'group_id' => $groupId,
            'pinned_id' => $pinned,
            'volume' => $volume,
        ];
    }

    /**
     * 新闻体混剪是否开启一镜到底。
     * 仅新闻体（type=4）支持；老数据缺键、非法值都按关闭处理，行为与改造前一致。
     */
    public static function resolveTypeSingleShot($config, int $type): bool
    {
        if ($type !== self::GENERATION_TYPE_NEWS) {
            return false;
        }
        $raw = self::configField($config, 'type_config');
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        if (!is_array($raw)) {
            return false;
        }
        $item = $raw[$type] ?? $raw[(string)$type] ?? [];
        if (!is_array($item) || !array_key_exists('single_shot', $item)) {
            return false;
        }
        return self::normalizeSingleShot($item['single_shot']) === self::SINGLE_SHOT_ON;
    }

    /** 该生成类型是否开启「指定素材分组」 */
    public static function resolveTypeMaterialGroupEnabled($config, int $type): bool
    {
        $item = self::typeConfigItem($config, $type);
        if (!array_key_exists('material_group_enabled', $item)) {
            return false;
        }
        return self::normalizeMaterialGroupEnabled($item['material_group_enabled']) === self::MATERIAL_GROUP_ON;
    }

    /**
     * 该生成类型生效的素材分组 ID：只有显式开启才返回。
     * 关闭 / 老数据缺键返回空数组，出片行为与改造前完全一致。
     *
     * @return array<int, int>
     */
    public static function resolveTypeMaterialGroupIds($config, int $type): array
    {
        if (!self::resolveTypeMaterialGroupEnabled($config, $type)) {
            return [];
        }
        $item = self::typeConfigItem($config, $type);
        return self::normalizeMaterialGroupIds($item['material_group_ids'] ?? []);
    }

    /**
     * 该生成类型保存的素材分组 ID（不论开关是否开启），仅用于配置回显。
     * 关闭状态也原样带出，站长再次打开开关时不用重新挑；出片侧仍走
     * resolveTypeMaterialGroupIds()，关闭时恒返回空数组。
     *
     * @return array<int, int>
     */
    public static function resolveTypeMaterialGroupIdsForDisplay($config, int $type): array
    {
        $item = self::typeConfigItem($config, $type);
        return self::normalizeMaterialGroupIds($item['material_group_ids'] ?? []);
    }

    /** 从 type_config 中取某个类型的原始配置项（结构归一由调用方决定） */
    private static function typeConfigItem($config, int $type): array
    {
        $raw = self::configField($config, 'type_config');
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        if (!is_array($raw)) {
            return [];
        }
        $item = $raw[$type] ?? $raw[(string)$type] ?? [];
        return is_array($item) ? $item : [];
    }

    /**
     * 单类型的口播语速，缺项回落顶层 speech_rate（老数据 = 三类继承顶层，行为不变）。
     *
     * 语速原先是一个全局字段，站长在某个类型 tab 里拖动时会连带改掉另外两类——
     * 三个类型的文案长度、配音节奏并不相同，因此按类型各存一份。
     */
    public static function resolveTypeSpeechRate($config, int $type): float
    {
        if (!in_array($type, self::TYPE_CONFIG_KEYS, true)) {
            $type = self::GENERATION_TYPE_DIGITAL_HUMAN;
        }
        $raw = self::configField($config, 'type_config');
        if (is_string($raw)) {
            $raw = trim($raw) === '' ? [] : (json_decode($raw, true) ?: []);
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        $item = $raw[$type] ?? $raw[(string)$type] ?? [];
        if (is_array($item) && array_key_exists('speech_rate', $item)) {
            return self::normalizeSpeechRate($item['speech_rate']);
        }
        return self::normalizeSpeechRate(self::configField($config, 'speech_rate', self::SPEECH_RATE_DEFAULT));
    }
}
