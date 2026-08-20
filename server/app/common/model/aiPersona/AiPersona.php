<?php
namespace app\common\model\aiPersona;


use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

use app\common\service\FileService;
class AiPersona extends BaseModel
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $name = 'ai_persona';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;

    public const PUBLISH_PLATFORM_SPH = 1; // 视频号
    public const PUBLISH_PLATFORM_XHS = 3; // 小红书
    public const PUBLISH_PLATFORM_DY = 4; // 抖音
    public const PUBLISH_PLATFORM_KS = 5; // 快手
    public const PUBLISH_MEDIA_TYPE_VIDEO = 1;
    public const PUBLISH_MEDIA_TYPE_IMAGE_TEXT = 2;
    public const CONTENT_GENERATE_MODE_AI = 1;
    public const CONTENT_GENERATE_MODE_CUSTOM = 2;
    public const CONTENT_GENERATE_MODE_LIBRARY = 3;
    public const CONTENT_LIBRARY_USE_MODE_RANDOM = 1;
    public const CONTENT_LIBRARY_USE_MODE_SEQUENCE = 2;
    public const CONTENT_LIBRARY_REUSE_MODE_ONCE = 1;
    public const CONTENT_LIBRARY_REUSE_MODE_REPEAT = 2;
    public const TRACKING_MODE_AUTO = 1;
    public const TRACKING_MODE_ACCOUNT = 2;

    /** 追踪视频时长：0不限 1一分钟内 2一到五分钟 35分钟以上；默认1分钟以下 */
    public const TRACKING_DURATION_UNLIMITED = 0;
    public const TRACKING_DURATION_WITHIN_1MIN = 1;
    public const TRACKING_DURATION_1_TO_5MIN = 2;
    public const TRACKING_DURATION_ABOVE_5MIN = 3;
    public const TRACKING_DURATION_DEFAULT = self::TRACKING_DURATION_WITHIN_1MIN;

    public const TRACKING_ACCOUNT_PLATFORMS = [
        self::PUBLISH_PLATFORM_XHS,
        self::PUBLISH_PLATFORM_DY,
    ];
    /** 内容发布分平台配置：小红书/抖音/视频号/快手（后两者默认与抖音一致） */
    public const CONTENT_PUBLISH_PLATFORMS = [
        self::PUBLISH_PLATFORM_XHS,
        self::PUBLISH_PLATFORM_DY,
        self::PUBLISH_PLATFORM_SPH,
        self::PUBLISH_PLATFORM_KS,
    ];

    public static function defaultTrackingAccountConfig(): array
    {
        $config = [];
        foreach (self::TRACKING_ACCOUNT_PLATFORMS as $platform) {
            $config[$platform] = [
                'account' => '',
                'homepage_url' => '',
            ];
        }

        return $config;
    }

    public static function normalizeTrackingMode($value): int
    {
        $mode = (int)$value;
        return in_array($mode, [self::TRACKING_MODE_AUTO, self::TRACKING_MODE_ACCOUNT], true)
            ? $mode
            : self::TRACKING_MODE_AUTO;
    }

    public static function normalizeTrackingFilterValue($value): int
    {
        $value = (int)$value;
        return in_array($value, [0, 1, 2, 3], true) ? $value : 0;
    }

    /**
     * 追踪视频时长：0不限 / 1一分钟内 / 2一到五分钟 / 35分钟以上；非法值回落默认1分钟以下
     */
    public static function normalizeTrackingDuration($value): int
    {
        $value = (int)$value;
        return in_array($value, [
            self::TRACKING_DURATION_UNLIMITED,
            self::TRACKING_DURATION_WITHIN_1MIN,
            self::TRACKING_DURATION_1_TO_5MIN,
            self::TRACKING_DURATION_ABOVE_5MIN,
        ], true) ? $value : self::TRACKING_DURATION_DEFAULT;
    }

    public static function normalizeTrackingAccountConfig($config, bool $withDefault = true): array
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($config)) {
            $config = [];
        }

        $result = $withDefault ? self::defaultTrackingAccountConfig() : [];
        foreach (self::TRACKING_ACCOUNT_PLATFORMS as $platform) {
            $key = (string)$platform;
            if (!array_key_exists($key, $config) && !array_key_exists($platform, $config)) {
                continue;
            }
            $item = $config[$key] ?? $config[$platform];
            if (is_string($item)) {
                $item = ['account' => $item];
            }
            if (!is_array($item)) {
                $item = [];
            }

            $result[$platform] = [
                'account' => trim((string)($item['account'] ?? $item['account_id'] ?? $item['account_no'] ?? '')),
                'homepage_url' => trim((string)($item['homepage_url'] ?? $item['home_url'] ?? $item['url'] ?? '')),
            ];
        }

        return $result;
    }

    public static function buildTrackingConfigData(array $params): array
    {
        $data = [];
        if (array_key_exists('tracking_mode', $params)) {
            $data['tracking_mode'] = self::normalizeTrackingMode($params['tracking_mode']);
        }
        if (array_key_exists('duration', $params)) {
            $data['duration'] = self::normalizeTrackingDuration($params['duration']);
        }
        if (array_key_exists('publish_day', $params)) {
            $data['publish_day'] = self::normalizeTrackingFilterValue($params['publish_day']);
        }
        if (array_key_exists('tracking_account_config', $params)) {
            $data['tracking_account_config'] = self::normalizeTrackingAccountConfig($params['tracking_account_config']);
        }

        return $data;
    }

    public static function defaultContentPublishConfig(): array
    {
        $base = [
            'generate_mode' => 1,
            'publish_copywriting_source' => 1,
            'generate_basis' => 1,
            'custom_direction' => '',
            'is_content_location' => 0,
            'content_location' => '',
            'library_use_mode' => self::CONTENT_LIBRARY_USE_MODE_RANDOM,
            'library_reuse_mode' => self::CONTENT_LIBRARY_REUSE_MODE_ONCE,
            'custom_copywriting' => [
                'title' => '',
                'content' => '',
                'topic_tags' => [],
            ],
        ];

        $platformConfigs = [];
        foreach (self::CONTENT_PUBLISH_PLATFORMS as $platform) {
            $platformConfigs[(string)$platform] = self::normalizePlatformContentPublishConfig(
                ['platform' => $platform],
                $platform,
                $base
            );
        }

        return $base + [
            'version' => 2,
            'platform_configs' => $platformConfigs,
        ];
    }

    public static function normalizeContentPublishConfig($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            $value = [];
        }

        $default = self::defaultContentPublishConfig();
        $base = self::normalizeContentPublishBaseConfig($value, $default);
        $platformConfigs = [];

        if (isset($value['platform_configs']) && is_array($value['platform_configs'])) {
            foreach ($default['platform_configs'] as $platform => $platformConfig) {
                $source = $value['platform_configs'][(string)$platform] ?? $value['platform_configs'][(int)$platform] ?? [];
                $platformConfigs[(string)$platform] = self::normalizePlatformContentPublishConfig(
                    is_array($source) ? $source : [],
                    (int)$platform,
                    $source ? $platformConfig : $base
                );
            }

            foreach ($value['platform_configs'] as $platform => $platformConfig) {
                $platform = (int)$platform;
                if (isset($platformConfigs[(string)$platform])) {
                    continue;
                }
                $platformConfigs[(string)$platform] = self::normalizePlatformContentPublishConfig(
                    is_array($platformConfig) ? $platformConfig : [],
                    $platform,
                    $base
                );
            }
        } else {
            foreach (self::CONTENT_PUBLISH_PLATFORMS as $platform) {
                $platformConfigs[(string)$platform] = self::normalizePlatformContentPublishConfig(
                    ['platform' => $platform],
                    $platform,
                    $base
                );
            }
        }

        $xhsConfig = $platformConfigs[(string)self::PUBLISH_PLATFORM_XHS] ?? self::normalizePlatformContentPublishConfig(
            [],
            self::PUBLISH_PLATFORM_XHS,
            $base
        );

        return [
            'version' => 2,
            'generate_mode' => $xhsConfig['generate_mode'],
            'publish_copywriting_source' => $xhsConfig['publish_copywriting_source'],
            'generate_basis' => $xhsConfig['generate_basis'],
            'custom_direction' => $xhsConfig['custom_direction'],
            'is_content_location' => $xhsConfig['is_content_location'],
            'content_location' => $xhsConfig['content_location'],
            'library_use_mode' => $xhsConfig['library_use_mode'],
            'library_reuse_mode' => $xhsConfig['library_reuse_mode'],
            'custom_copywriting' => $xhsConfig['custom_copywriting'],
            'platform_configs' => $platformConfigs,
        ];
    }

    public static function getPlatformContentPublishConfig($config, int $platform): array
    {
        $config = self::normalizeContentPublishConfig($config);
        if (isset($config['platform_configs'][(string)$platform])) {
            return $config['platform_configs'][(string)$platform];
        }

        return self::normalizePlatformContentPublishConfig([], $platform, $config);
    }

    private static function normalizeContentPublishBaseConfig(array $value, array $default): array
    {
        $customCopywriting = $value['custom_copywriting'] ?? $default['custom_copywriting'] ?? [];
        if (!is_array($customCopywriting)) {
            $customCopywriting = [];
        }

        $generateMode = (int)($value['generate_mode'] ?? $default['generate_mode'] ?? self::CONTENT_GENERATE_MODE_AI);
        if (array_key_exists('publish_copywriting_source', $value)
            && $generateMode !== self::CONTENT_GENERATE_MODE_CUSTOM
        ) {
            // publish_copywriting_source: 2/4=文案库, 1=非文案库；自定义模式也落成 1，
            // 二次归一化时不得把已明确的 CUSTOM 覆盖成 AI
            $publishCopywritingSource = (int)$value['publish_copywriting_source'];
            if (in_array($publishCopywritingSource, [2, 4], true)) {
                $generateMode = self::CONTENT_GENERATE_MODE_LIBRARY;
            } elseif ($publishCopywritingSource === 1) {
                $generateMode = self::CONTENT_GENERATE_MODE_AI;
            }
        }
        $hasLibraryPayload = array_key_exists('library_use_mode', $value)
            || array_key_exists('library_reuse_mode', $value)
            || array_key_exists('publish_library_use_mode', $value)
            || array_key_exists('publish_library_reuse_mode', $value);
        if ($generateMode === self::CONTENT_GENERATE_MODE_CUSTOM
            && $hasLibraryPayload
            && trim((string)($customCopywriting['title'] ?? '')) === ''
            && trim((string)($customCopywriting['content'] ?? '')) === ''
            && empty($customCopywriting['topic_tags'] ?? [])
        ) {
            $generateMode = self::CONTENT_GENERATE_MODE_LIBRARY;
        }
        if (!in_array($generateMode, [
            self::CONTENT_GENERATE_MODE_AI,
            self::CONTENT_GENERATE_MODE_CUSTOM,
            self::CONTENT_GENERATE_MODE_LIBRARY,
        ], true)) {
            $generateMode = self::CONTENT_GENERATE_MODE_AI;
        }

        $generateBasis = (int)($value['generate_basis'] ?? $default['generate_basis'] ?? 1);
        if (!in_array($generateBasis, [1, 2], true)) {
            $generateBasis = 1;
        }

        $isContentLocation = (int)self::pickFirstValue($value, [
            'is_content_location',
            'is_publish_location',
            'is_content_position',
            'is_publish_position',
        ], $default['is_content_location'] ?? 0);
        $isContentLocation = $isContentLocation === 1 ? 1 : 0;

        $contentLocation = trim((string)self::pickFirstValue($value, [
            'content_location',
            'publish_location',
            'content_position',
            'publish_position',
        ], $default['content_location'] ?? ''));

        $libraryUseMode = (int)self::pickFirstValue($value, [
            'library_use_mode',
            'publish_library_use_mode',
        ], $default['library_use_mode'] ?? self::CONTENT_LIBRARY_USE_MODE_RANDOM);
        if (!in_array($libraryUseMode, [self::CONTENT_LIBRARY_USE_MODE_RANDOM, self::CONTENT_LIBRARY_USE_MODE_SEQUENCE], true)) {
            $libraryUseMode = self::CONTENT_LIBRARY_USE_MODE_RANDOM;
        }

        $libraryReuseMode = (int)self::pickFirstValue($value, [
            'library_reuse_mode',
            'publish_library_reuse_mode',
        ], $default['library_reuse_mode'] ?? self::CONTENT_LIBRARY_REUSE_MODE_ONCE);
        if (!in_array($libraryReuseMode, [self::CONTENT_LIBRARY_REUSE_MODE_ONCE, self::CONTENT_LIBRARY_REUSE_MODE_REPEAT], true)) {
            $libraryReuseMode = self::CONTENT_LIBRARY_REUSE_MODE_ONCE;
        }

        return [
            'generate_mode' => $generateMode,
            'publish_copywriting_source' => $generateMode === self::CONTENT_GENERATE_MODE_LIBRARY ? 2 : 1,
            'generate_basis' => $generateBasis,
            'custom_direction' => trim((string)($value['custom_direction'] ?? $default['custom_direction'] ?? '')),
            'is_content_location' => $isContentLocation,
            'content_location' => $contentLocation,
            'library_use_mode' => $libraryUseMode,
            'library_reuse_mode' => $libraryReuseMode,
            'custom_copywriting' => [
                'title' => trim((string)($customCopywriting['title'] ?? '')),
                'content' => trim((string)($customCopywriting['content'] ?? '')),
                'topic_tags' => self::normalizeTopicTags($customCopywriting['topic_tags'] ?? []),
            ],
        ];
    }

    public static function normalizePlatformContentPublishConfig(array $value, int $platform, array $fallback = []): array
    {
        $default = self::defaultPlatformBaseConfig($platform);
        $fallback = array_replace_recursive($default, $fallback);
        $base = self::normalizeContentPublishBaseConfig($value, $fallback);

        $publishMediaType = (int)($value['publish_media_type'] ?? $fallback['publish_media_type'] ?? self::PUBLISH_MEDIA_TYPE_VIDEO);
        if (!in_array($publishMediaType, [self::PUBLISH_MEDIA_TYPE_VIDEO, self::PUBLISH_MEDIA_TYPE_IMAGE_TEXT], true)) {
            $publishMediaType = self::PUBLISH_MEDIA_TYPE_VIDEO;
        }

        return [
            'platform' => $platform,
            'publish_media_type' => $publishMediaType,
            'generate_mode' => $base['generate_mode'],
            'publish_copywriting_source' => $base['publish_copywriting_source'],
            'generate_basis' => $base['generate_basis'],
            'custom_direction' => $base['custom_direction'],
            'is_content_location' => $base['is_content_location'],
            'content_location' => $base['content_location'],
            'library_use_mode' => $base['library_use_mode'],
            'library_reuse_mode' => $base['library_reuse_mode'],
            'custom_copywriting' => $base['custom_copywriting'],
        ];
    }

    private static function defaultPlatformBaseConfig(int $platform): array
    {
        return [
            'platform' => $platform,
            'publish_media_type' => self::PUBLISH_MEDIA_TYPE_VIDEO,
            'generate_mode' => 1,
            'publish_copywriting_source' => 1,
            'generate_basis' => 1,
            'custom_direction' => '',
            'is_content_location' => 0,
            'content_location' => '',
            'library_use_mode' => self::CONTENT_LIBRARY_USE_MODE_RANDOM,
            'library_reuse_mode' => self::CONTENT_LIBRARY_REUSE_MODE_ONCE,
            'custom_copywriting' => [
                'title' => '',
                'content' => '',
                'topic_tags' => [],
            ],
        ];
    }

    private static function normalizeTopicTags($topicTags): array
    {
        if (is_string($topicTags)) {
            $topicTags = explode(',', $topicTags);
        }
        if (!is_array($topicTags)) {
            $topicTags = [];
        }

        return array_values(array_filter(array_map(function ($tag) {
            return trim((string)$tag);
        }, $topicTags), function ($tag) {
            return $tag !== '';
        }));
    }

    public static function mergeContentPublishConfigOverrides($config, array $params): array
    {
        $config = self::normalizeContentPublishConfig($config);
        $incoming = $params['content_publish_config'] ?? null;
        $hasIncomingPlatformConfigs = false;
        if ($incoming !== null) {
            if (is_string($incoming)) {
                $decoded = json_decode($incoming, true);
                $incoming = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($incoming)) {
                $incoming = [];
            }

            if (isset($incoming['platform_configs']) && is_array($incoming['platform_configs'])) {
                $hasIncomingPlatformConfigs = true;
                foreach ($incoming['platform_configs'] as $platform => $platformConfig) {
                    $platform = (int)$platform;
                    $current = $config['platform_configs'][(string)$platform] ?? self::getPlatformContentPublishConfig($config, $platform);
                    $config['platform_configs'][(string)$platform] = self::normalizePlatformContentPublishConfig(
                        is_array($platformConfig) ? $platformConfig : [],
                        $platform,
                        $current
                    );
                }
                $config = self::normalizeContentPublishConfig($config);
            } else {
                $config = self::normalizeContentPublishConfig($incoming);
            }
        }

        $overrideMap = [
            'generate_mode' => ['generate_mode'],
            'publish_copywriting_source' => ['publish_copywriting_source'],
            'generate_basis' => ['generate_basis'],
            'custom_direction' => ['custom_direction'],
            'library_use_mode' => ['library_use_mode', 'publish_library_use_mode'],
            'library_reuse_mode' => ['library_reuse_mode', 'publish_library_reuse_mode'],
            'custom_copywriting' => ['custom_copywriting'],
            'is_content_location' => [
                'is_content_location',
                'is_publish_location',
                'is_content_position',
                'is_publish_position',
            ],
            'content_location' => [
                'content_location',
                'publish_location',
                'content_position',
                'publish_position',
            ],
        ];

        $legacyOverrides = [];
        foreach ($overrideMap as $target => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $params)) {
                    $legacyOverrides[$target] = $params[$key];
                    break;
                }
            }
        }

        // 已传 platform_configs 时以分平台配置为准。
        // 否则顶层空的 content_location/is_content_location 会覆盖各平台里已填的定位。
        if (!empty($legacyOverrides) && !$hasIncomingPlatformConfigs) {
            $legacyConfig = self::normalizeContentPublishBaseConfig(array_merge($config, $legacyOverrides), self::defaultContentPublishConfig());
            foreach (self::CONTENT_PUBLISH_PLATFORMS as $platform) {
                $current = $config['platform_configs'][(string)$platform] ?? [];
                $config['platform_configs'][(string)$platform] = self::normalizePlatformContentPublishConfig(
                    array_merge($current, $legacyConfig),
                    $platform,
                    $current
                );
            }
        }

        return self::normalizeContentPublishConfig($config);
    }

    public static function validateContentPublishConfig(array $config): string
    {
        $config = self::normalizeContentPublishConfig($config);
        foreach ($config['platform_configs'] as $platformConfig) {
            if ((int)$platformConfig['generate_mode'] === self::CONTENT_GENERATE_MODE_CUSTOM && $platformConfig['custom_copywriting']['title'] === '') {
                return '发布标题不能为空';
            }

            if ((int)$platformConfig['generate_mode'] === self::CONTENT_GENERATE_MODE_AI
                && (int)$platformConfig['generate_basis'] === 2
                && $platformConfig['custom_direction'] === ''
            ) {
                return '自定义方向不能为空';
            }

            if ((int)$platformConfig['is_content_location'] === 1 && $platformConfig['content_location'] === '') {
                return '发布内容定位地址不能为空';
            }
        }

        return '';
    }

    private static function pickFirstValue(array $source, array $keys, $default)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source)) {
                return $source[$key];
            }
        }

        return $default;
    }

    // 关联个人IP扩展表
    public function individual()
    {
        return $this->hasOne(AiPersonaIndividual::class, 'persona_id', 'id');
    }

    // 关联企业服务扩展表
    public function enterprise()
    {
        return $this->hasOne(AiPersonaEnterprise::class, 'persona_id', 'id');
    }

    // 关联本地商家扩展表
    public function local()
    {
        return $this->hasOne(AiPersonaLocal::class, 'persona_id', 'id');
    }

    // 获取器：处理报告内容JSON
    public function getReportContentAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // 修改器：处理报告内容JSON
    public function setReportContentAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    public function getContentPublishConfigAttr($value)
    {
        return self::normalizeContentPublishConfig($value);
    }

    public function setContentPublishConfigAttr($value)
    {
        return json_encode(self::normalizeContentPublishConfig($value), JSON_UNESCAPED_UNICODE);
    }

    public function getTrackingModeAttr($value)
    {
        return self::normalizeTrackingMode($value);
    }

    public function getDurationAttr($value)
    {
        return self::normalizeTrackingDuration($value);
    }

    public function getPublishDayAttr($value)
    {
        return self::normalizeTrackingFilterValue($value);
    }

    public function getTrackingAccountConfigAttr($value)
    {
        return self::normalizeTrackingAccountConfig($value);
    }

    public function setTrackingAccountConfigAttr($value)
    {
        return json_encode(self::normalizeTrackingAccountConfig($value), JSON_UNESCAPED_UNICODE);
    }

    public function searchStartTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('create_time', '>=', strtotime($value));
        }
    }

    public function searchEndTimeAttr($query, $value)
    {
        if ($value) {
            $query->where('create_time', '<=', strtotime($value));
        }
    }

    public function getAvatarUrlAttr($value)
    {
        return $value ? FileService::getFileUrl($value, '', true) : '';
    }

  
    public function setAvatarUrlAttr($value)
    {
        return $value ? FileService::setFileUrl($value) : '';
    }

    /**
     * 格式化人设展示标签：名称(ID:xx)，无名称时仅返回 ID:xx
     */
    public static function formatLabel(mixed $persona = null, int $personaId = 0): string
    {
        if (is_object($persona) && method_exists($persona, 'isEmpty') && !$persona->isEmpty()) {
            return self::buildLabel((int)($persona->id ?? 0), trim((string)($persona->persona_name ?? '')));
        }

        if (is_array($persona)) {
            return self::buildLabel((int)($persona['id'] ?? $personaId), trim((string)($persona['persona_name'] ?? '')));
        }

        $id = $personaId > 0 ? $personaId : 0;
        if ($id <= 0) {
            return '未知人设';
        }

        $name = trim((string)self::where('id', $id)->value('persona_name'));

        return self::buildLabel($id, $name);
    }

    private static function buildLabel(int $id, string $name): string
    {
        if ($id <= 0) {
            return $name !== '' ? $name : '未知人设';
        }

        if ($name !== '') {
            return $name . '(ID:' . $id . ')';
        }

        return 'ID:' . $id;
    }
}
