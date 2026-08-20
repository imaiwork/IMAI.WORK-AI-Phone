<?php

namespace app\common\service\auto;

use app\common\model\auto\AutoTaskSceneConfig;
use think\facade\Db;

/**
 * 自动任务场景开关配置服务
 * Class AutoTaskSceneConfigService
 * @package app\common\service\auto
 */
class AutoTaskSceneConfigService
{
    /** 场景范围 */
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
            // 16 => '爆款仿写',
            // 17 => '精准获客',
        ];
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
     * 获取全部配置项（含 name）
     * 无记录或表不存在时返回默认全开 17 项
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
            $items[] = [
                'scene'     => $scene,
                'name'      => $name,
                'allow_add' => $row ? (int)$row['allow_add'] : 1,
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
                    $inputMap[$scene] = [
                        'allow_add' => (int)($item['allow_add'] ?? 1),
                        'name'      => isset($item['name']) && (string)$item['name'] !== ''
                            ? (string)$item['name']
                            : null,
                    ];
                }
            }

            if (empty($inputMap)) {
                Db::rollback();
                return false;
            }

            foreach ($inputMap as $scene => $data) {
                $allowAdd  = $data['allow_add'];
                $inputName = $data['name'];

                $existing = AutoTaskSceneConfig::where('scene', $scene)->findOrEmpty();
                if ($existing->isEmpty()) {
                    AutoTaskSceneConfig::create([
                        'scene'       => $scene,
                        'name'        => $inputName ?: ($defaults[$scene] ?? ''),
                        'allow_add'   => $allowAdd,
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                } else {
                    $existing->allow_add = $allowAdd;
                    if ($inputName !== null) {
                        $existing->name = $inputName;
                    } elseif (!isset($existing->name) || (string)$existing->name === '') {
                        $existing->name = $defaults[$scene] ?? '';
                    }
                    $existing->update_time = $now;
                    $existing->save();
                }
            }

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @param int $scene
     * @param array|null $configMap
     * @return bool
     */
    public static function canAdd(int $scene, ?array $configMap = null): bool
    {
        if ($scene < self::SCENE_MIN || $scene > self::SCENE_MAX) {
            return false;
        }
        $config = self::resolveConfig($scene, $configMap);
        // 无配置记录时默认允许（兼容未初始化）
        return $config === null ? true : ((int)$config['allow_add'] === 1);
    }

    /**
     * @return array
     */
    public static function getAddableSceneList(): array
    {
        $items = self::getItems();
        $result = [];
        foreach ($items as $item) {
            if ((int)$item['allow_add'] === 1) {
                $result[] = [
                    'scene' => (int)$item['scene'],
                    'name'  => $item['name'],
                ];
            }
        }
        return $result;
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
     * @return array
     */
    private static function buildDefaultItems(): array
    {
        $defaults = self::getDefaultSceneNames();
        $items = [];
        for ($scene = self::SCENE_MIN; $scene <= self::SCENE_MAX; $scene++) {
            $items[] = [
                'scene'     => $scene,
                'name'      => $defaults[$scene] ?? '',
                'allow_add' => 1,
            ];
        }
        return $items;
    }
}
