<?php

namespace app\common\service\auto;

use app\common\enum\DeviceEnum;
use app\common\model\auto\AutoTaskSceneConfig;
use think\facade\Db;
use think\facade\Log;

/**
 * 自动任务场景开关配置服务
 * Class AutoTaskSceneConfigService
 * @package app\common\service\auto
 */
class AutoTaskSceneConfigService
{
    /** 场景范围（不含爆款仿写16、精准获客17） */
    const SCENE_MIN = 1;
    const SCENE_MAX = 15;

    /**
     * 默认场景名称（与业务展示文案一致）
     *
     * @return array<int, string>
     */
    public static function getDefaultSceneNames(): array
    {
        return [
            1  => '截流评论获客',
            2  => '截流私信获客',
            3  => '留痕获客/同城触达',
            4  => '视频号获客',
            5  => '视频发布',
            6  => '私信接管',
            7  => '朋友圈发布',
            8  => '朋友圈互动',
            9  => '自动加好友',
            10 => '自动养号',
            11 => '评论接管',
            12 => '同城曝光',
            13 => '同城截流',
            14 => '团购截流',
            15 => '评论点赞',
        ];
    }

    /**
     * 各任务类型支持的平台（account_type），顺序即后台回显顺序
     *
     * @return array<int, int[]>
     */
    public static function getSupportPlatformMap(): array
    {
        return [
            1  => [DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_KS],
            2  => [DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_KS],
            3  => [DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_DY],
            4  => [DeviceEnum::ACCOUNT_TYPE_SPH],
            5  => [DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_KS, DeviceEnum::ACCOUNT_TYPE_SPH],
            6  => [DeviceEnum::ACCOUNT_TYPE_SPH, DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_KS],
            7  => [DeviceEnum::ACCOUNT_TYPE_SPH],
            8  => [DeviceEnum::ACCOUNT_TYPE_SPH],
            9  => [DeviceEnum::ACCOUNT_TYPE_SPH],
            10 => [DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_KS],
            11 => [DeviceEnum::ACCOUNT_TYPE_XHS, DeviceEnum::ACCOUNT_TYPE_DY, DeviceEnum::ACCOUNT_TYPE_KS],
            12 => [DeviceEnum::ACCOUNT_TYPE_DY],
            13 => [DeviceEnum::ACCOUNT_TYPE_DY],
            14 => [DeviceEnum::ACCOUNT_TYPE_DY],
            15 => [DeviceEnum::ACCOUNT_TYPE_SPH],
        ];
    }

    /**
     * @param int $scene
     * @return int[]
     */
    public static function getSupportPlatforms(int $scene): array
    {
        $map = self::getSupportPlatformMap();
        return $map[$scene] ?? [];
    }

    /**
     * 获取场景展示名
     *
     * @param int $scene
     * @param array|null $configMap
     * @return string
     */
    public static function getSceneName(int $scene, ?array $configMap = null): string
    {
        $defaults = self::getDefaultSceneNames();
        if ($configMap !== null) {
            $name = (string)($configMap[$scene]['name'] ?? '');
            return $name !== '' ? $name : ($defaults[$scene] ?? (string)$scene);
        }
        try {
            $name = (string)AutoTaskSceneConfig::where('scene', $scene)->value('name');
            if ($name !== '') {
                return $name;
            }
        } catch (\Throwable $e) {
            // 表不存在时回退默认名
        }
        return $defaults[$scene] ?? (string)$scene;
    }

    /**
     * 获取全部配置项（含 name、allow_platforms）
     * 无记录或表不存在时返回默认全开 15 项
     *
     * @return array
     */
    public static function getItems(): array
    {
        $defaults = self::getDefaultSceneNames();
        try {
            $rows = AutoTaskSceneConfig::order('scene', 'asc')->select()->toArray();
        } catch (\Throwable $e) {
            return self::buildDefaultItems();
        }

        if (empty($rows)) {
            return self::buildDefaultItems();
        }

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['scene']] = $row;
        }

        $items = [];
        for ($scene = self::SCENE_MIN; $scene <= self::SCENE_MAX; $scene++) {
            $row = $map[$scene] ?? null;
            $name = '';
            if ($row && isset($row['name']) && (string)$row['name'] !== '') {
                $name = (string)$row['name'];
            } else {
                $name = $defaults[$scene] ?? '';
            }
            $rawPlatforms = $row['allow_platforms'] ?? null;
            $items[] = [
                'scene'           => $scene,
                'name'            => $name,
                'allow_add'       => $row ? (int)$row['allow_add'] : 1,
                'allow_platforms' => self::normalizeAllowPlatforms($scene, $rawPlatforms, true),
            ];
        }
        return $items;
    }

    /**
     * 获取以 scene 为 key 的配置映射
     *
     * @return array
     */
    public static function getConfigMap(): array
    {
        return array_column(self::getItems(), null, 'scene');
    }

    /**
     * 保存配置：仅更新入参中出现的 scene，避免漏传时把其它类型误重置为开放
     *
     * @param array $items
     * @return bool
     */
    public static function saveItems(array $items): bool
    {
        Db::startTrans();
        try {
            $now = time();
            $defaults = self::getDefaultSceneNames();

            $inputMap = [];
            foreach ($items as $item) {
                $scene = (int)($item['scene'] ?? 0);
                if ($scene >= self::SCENE_MIN && $scene <= self::SCENE_MAX) {
                    $hasPlatforms = array_key_exists('allow_platforms', $item);
                    $inputMap[$scene] = [
                        'allow_add'       => (int)($item['allow_add'] ?? 1),
                        'name'            => isset($item['name']) && (string)$item['name'] !== ''
                            ? (string)$item['name']
                            : null,
                        'allow_platforms' => $hasPlatforms
                            ? self::normalizeAllowPlatforms($scene, $item['allow_platforms'], false)
                            : null,
                    ];
                }
            }

            if (empty($inputMap)) {
                Db::rollback();
                return false;
            }

            $oldMap = self::getConfigMap();
            $writtenMap = [];

            foreach ($inputMap as $scene => $data) {
                $allowAdd  = $data['allow_add'];
                $inputName = $data['name'];

                $existing = AutoTaskSceneConfig::where('scene', $scene)->findOrEmpty();
                $payloadPlatforms = $data['allow_platforms'];
                if ($payloadPlatforms === null) {
                    $payloadPlatforms = $existing->isEmpty()
                        ? self::normalizeAllowPlatforms($scene, null, true)
                        : self::normalizeAllowPlatforms($scene, $existing->allow_platforms ?? null, true);
                }

                $writtenMap[$scene] = [
                    'scene'           => $scene,
                    'allow_add'       => $allowAdd,
                    'allow_platforms' => $payloadPlatforms,
                ];

                if ($existing->isEmpty()) {
                    AutoTaskSceneConfig::create([
                        'scene'           => $scene,
                        'name'            => $inputName ?: ($defaults[$scene] ?? ''),
                        'allow_add'       => $allowAdd,
                        'allow_platforms' => $payloadPlatforms,
                        'create_time'     => $now,
                        'update_time'     => $now,
                    ]);
                } else {
                    $existing->allow_add = $allowAdd;
                    $existing->allow_platforms = $payloadPlatforms;
                    if ($inputName !== null) {
                        $existing->name = $inputName;
                    } elseif (!isset($existing->name) || (string)$existing->name === '') {
                        $existing->name = $defaults[$scene] ?? '';
                    }
                    $existing->update_time = $now;
                    $existing->save();
                }
            }

            $fullMap = $oldMap;
            foreach ($writtenMap as $scene => $item) {
                $fullMap[$scene] = array_merge($oldMap[$scene] ?? [], $item);
            }
            // 按当前关闭状态剥离，兼容「快手早已关闭、本次保存无 1→0 增量」的历史脏节点
            $closedByScene = AutoTaskSceneScheduleSyncService::collectCurrentlyClosedPlatforms($fullMap);
            if ($closedByScene !== []) {
                AutoTaskSceneScheduleSyncService::stripClosedPlatformsFromTemplates($closedByScene);
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 是否允许添加/生成。
     * 兼容旧调用：canAdd($scene) / canAdd($scene, $configMap) 只看类型总开关。
     * 24h 生成：canAdd($scene, $platform) / canAdd($scene, $platform, $configMap) 再看平台 status。
     *
     * @param int $scene
     * @param int|array $platformOrConfigMap
     * @param array|null $configMap
     * @return bool
     */
    public static function canAdd(int $scene, $platformOrConfigMap = 0, ?array $configMap = null): bool
    {
        $platform = 0;
        if (is_array($platformOrConfigMap)) {
            $configMap = $platformOrConfigMap;
        } else {
            $platform = (int)$platformOrConfigMap;
        }

        if ($scene < self::SCENE_MIN || $scene > self::SCENE_MAX) {
            // 16/17 不在开关范围：带平台时不拦截；旧工作流无平台调用仍视为不可添加
            return $platform > 0;
        }

        $config = self::resolveConfig($scene, $configMap);
        $allowAdd = $config === null ? 1 : (int)($config['allow_add'] ?? 1);
        if ($allowAdd !== 1) {
            return false;
        }

        if ($platform <= 0) {
            return true;
        }

        $support = self::getSupportPlatforms($scene);
        if (!in_array($platform, $support, true)) {
            return false;
        }

        $raw = $config['allow_platforms'] ?? null;
        if ($raw === null) {
            return true;
        }
        $list = self::decodeAllowPlatforms($raw);
        if ($list === []) {
            return false;
        }

        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            if ((int)($item['account_type'] ?? 0) === $platform) {
                return (int)($item['status'] ?? 0) === 1;
            }
        }
        return false;
    }

    /**
     * 24h 生成守卫：关闭则写中文日志并返回 true（调用方 continue）
     *
     * @param int $scene
     * @param int $platform
     * @param string $deviceCode
     * @param string $taskLabel
     * @param array|null $configMap
     * @return bool
     */
    public static function shouldSkipDailyCreate(
        int $scene,
        int $platform,
        string $deviceCode,
        string $taskLabel = '任务',
        ?array $configMap = null
    ): bool {
        if ($scene < self::SCENE_MIN || $scene > self::SCENE_MAX) {
            return false;
        }
        if (self::canAdd($scene, $platform, $configMap)) {
            return false;
        }
        $name = self::getSceneName($scene, $configMap);
        $platformName = DeviceEnum::getAccountTypeDesc($platform);
        if ($platformName === '') {
            $platformName = '平台' . $platform;
        }
        try {
            Log::channel('auto')->write(
                $deviceCode . '任务类型「' . $name . '」在' . $platformName . '暂未开放，跳过' . $taskLabel . '生成',
                'create'
            );
        } catch (\Throwable $e) {
            // 日志通道异常不影响 24h 跳过
        }
        return true;
    }

    /**
     * 场景当前开放的平台（status=1），未开放返回空数组
     *
     * @param int $scene
     * @param array|null $configMap
     * @return int[]
     */
    public static function getOpenPlatforms(int $scene, ?array $configMap = null): array
    {
        $config = self::resolveConfig($scene, $configMap);
        if ($config === null) {
            return self::getSupportPlatforms($scene);
        }
        $result = [];
        foreach (self::normalizeAllowPlatforms($scene, $config['allow_platforms'] ?? null, true) as $item) {
            if ((int)$item['status'] === 1) {
                $result[] = (int)$item['account_type'];
            }
        }
        return $result;
    }

    /**
     * 可添加场景列表：allow_add=1 且至少有一个平台开放，附带开放平台列表供端上过滤
     *
     * @return array
     */
    public static function getAddableSceneList(): array
    {
        $configMap = self::getConfigMap();
        $result = [];
        foreach ($configMap as $scene => $item) {
            if ((int)$item['allow_add'] !== 1) {
                continue;
            }
            $platforms = self::getOpenPlatforms((int)$scene, $configMap);
            // 平台全部关闭等同于不可添加：加了也不会生成 24h 任务
            if (empty($platforms)) {
                continue;
            }
            $result[] = [
                'scene'     => (int)$scene,
                'name'      => $item['name'],
                'platforms' => $platforms,
            ];
        }
        return $result;
    }

    /**
     * 规范化平台开关。defaultOpen=true 时漏传支持平台补 status=1，否则补 0。
     *
     * @param int $scene
     * @param mixed $raw
     * @param bool $defaultOpen
     * @return array<int, array{account_type:int,status:int}>
     */
    public static function normalizeAllowPlatforms(int $scene, $raw, bool $defaultOpen = true): array
    {
        $support = self::getSupportPlatforms($scene);
        if (self::isExplicitEmptyAllowPlatforms($raw)) {
            $defaultOpen = false;
            $list = [];
        } else {
            $list = self::decodeAllowPlatforms($raw);
        }
        $statusMap = [];
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }
            $accountType = (int)($item['account_type'] ?? 0);
            if (!in_array($accountType, $support, true)) {
                continue;
            }
            $statusMap[$accountType] = in_array($item['status'] ?? null, [1, '1'], true) ? 1 : 0;
        }

        $result = [];
        foreach ($support as $accountType) {
            $result[] = [
                'account_type' => $accountType,
                'status'       => array_key_exists($accountType, $statusMap)
                    ? $statusMap[$accountType]
                    : ($defaultOpen ? 1 : 0),
            ];
        }
        return $result;
    }

    /**
     * 校验入参 allow_platforms，成功返回 true，失败返回中文原因
     *
     * @param int $scene
     * @param mixed $value
     * @return bool|string
     */
    public static function validateAllowPlatformsInput(int $scene, $value)
    {
        if (!is_array($value)) {
            return '平台开关格式错误';
        }
        $support = self::getSupportPlatforms($scene);
        $seen = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                return '平台开关格式错误';
            }
            $accountType = filter_var($item['account_type'] ?? null, FILTER_VALIDATE_INT);
            if (false === $accountType || !in_array($accountType, $support, true)) {
                return '平台类型不合法';
            }
            if (in_array($accountType, $seen, true)) {
                return '平台类型不能重复';
            }
            $seen[] = $accountType;
            if (!in_array($item['status'] ?? null, [0, 1, '0', '1'], true)) {
                return '平台开关状态错误';
            }
        }
        return true;
    }

    /**
     * @param int $scene
     * @param array|null $configMap
     * @return array|null
     */
    private static function resolveConfig(int $scene, ?array $configMap = null): ?array
    {
        if ($configMap !== null) {
            return $configMap[$scene] ?? null;
        }
        $configMap = self::getConfigMap();
        return $configMap[$scene] ?? null;
    }

    /**
     * 显式空数组表示全部平台关闭；NULL / 空字符串按未升级处理
     *
     * @param mixed $raw
     * @return bool
     */
    private static function isExplicitEmptyAllowPlatforms($raw): bool
    {
        if (is_array($raw) && $raw === []) {
            return true;
        }
        return is_string($raw) && trim($raw) === '[]';
    }

    /**
     * @param mixed $raw
     * @return array
     */
    private static function decodeAllowPlatforms($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        return is_array($raw) ? $raw : [];
    }

    /**
     * @return array
     */
    private static function buildDefaultItems(): array
    {
        $defaults = self::getDefaultSceneNames();
        $items = [];
        for ($scene = self::SCENE_MIN; $scene <= self::SCENE_MAX; $scene++) {
            $items[] = [
                'scene'           => $scene,
                'name'            => $defaults[$scene] ?? '',
                'allow_add'       => 1,
                'allow_platforms' => self::normalizeAllowPlatforms($scene, null, true),
            ];
        }
        return $items;
    }
}
