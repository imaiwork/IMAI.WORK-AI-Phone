<?php

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\model\distribution\DistributionAgent;
use app\common\service\ConfigService;

/**
 * 分销代理配置逻辑
 *
 * 代理等级不是独立数据表，等级清单与下级人数上限都以 JSON 存在 la_config：
 * - distribution_agent_level_names: [{level, name, remark}, ...]，level 越小等级越高
 * - distribution_agent_sub_limits:  {"L": {"M": 上限}}，仅保留 M > L 的组合，0 表示不限
 *
 * 等级数量可由后台增删，因此两份配置必须始终由本类归一化后再落库，
 * 保证 sub_limits 的键集合与当前等级清单严格对应。
 *
 * Class DistributionAgentConfigLogic
 * @package app\adminapi\logic\setting
 */
class DistributionAgentConfigLogic extends BaseLogic
{
    const CONFIG_TYPE = 'distribution_agent';
    const KEY_LEVEL_NAMES = 'distribution_agent_level_names';
    const KEY_SUB_LIMITS = 'distribution_agent_sub_limits';

    /** 等级数量上限，避免下级上限矩阵无限膨胀 */
    const MAX_LEVEL_COUNT = 10;

    /**
     * @notes 获取代理等级清单
     * @return array [{level, name, remark}, ...]
     */
    public static function getConfig(): array
    {
        return self::normalizeLevels(ConfigService::get(self::CONFIG_TYPE, self::KEY_LEVEL_NAMES));
    }

    /**
     * @notes 当前已配置的等级值，升序（不含 0 普通用户）
     * @return array
     */
    public static function getLevelValues(): array
    {
        return array_column(self::getConfig(), 'level');
    }

    /**
     * @notes 等级名称，未配置的等级返回空串
     * @param int $level
     * @return string
     */
    public static function getLevelName(int $level): string
    {
        foreach (self::getConfig() as $item) {
            if ((int)$item['level'] === $level) {
                return (string)$item['name'];
            }
        }
        return '';
    }

    /**
     * @notes 编辑等级名称与备注（不改变等级数量）
     * @param array $params
     */
    public static function setConfig(array $params): void
    {
        $current = self::getConfig();
        $incoming = [];
        foreach ($params['config'] ?? [] as $item) {
            $level = (int)($item['level'] ?? 0);
            if ($level > 0) {
                $incoming[$level] = $item;
            }
        }

        foreach ($current as &$item) {
            $level = (int)$item['level'];
            if (!isset($incoming[$level])) {
                continue;
            }
            $name = trim((string)($incoming[$level]['name'] ?? ''));
            if ($name !== '') {
                $item['name'] = $name;
            }
            if (array_key_exists('remark', $incoming[$level])) {
                $item['remark'] = trim((string)$incoming[$level]['remark']);
            }
        }
        unset($item);

        self::saveLevels($current);
    }

    /**
     * @notes 追加一个代理等级，新等级值为当前最大等级 + 1（即新增最低一级）
     * @param array $params
     * @return array 新增的等级
     * @throws \Exception
     */
    public static function addLevel(array $params): array
    {
        $levels = self::getConfig();
        if (count($levels) >= self::MAX_LEVEL_COUNT) {
            throw new \Exception('代理等级最多 ' . self::MAX_LEVEL_COUNT . ' 级');
        }

        $newLevel = max(array_column($levels, 'level')) + 1;
        $name = trim((string)($params['name'] ?? ''));
        if ($name === '') {
            throw new \Exception('请输入等级名称');
        }

        $levels[] = [
            'level' => $newLevel,
            'name' => $name,
            'remark' => trim((string)($params['remark'] ?? '')),
        ];

        self::saveLevels($levels);
        return end($levels);
    }

    /**
     * @notes 删除一个代理等级，仅允许删除没有用户占用的等级
     * @param array $params
     * @throws \Exception
     */
    public static function delLevel(array $params): void
    {
        $level = (int)($params['level'] ?? 0);
        $levels = self::getConfig();

        if (!in_array($level, array_column($levels, 'level'), true)) {
            throw new \Exception('代理等级不存在');
        }
        if (count($levels) <= 1) {
            throw new \Exception('至少保留一个代理等级');
        }

        $used = DistributionAgent::where('level', $level)->count();
        if ($used > 0) {
            throw new \Exception("该等级下还有 {$used} 个代理用户，请先调整他们的等级");
        }

        $levels = array_values(array_filter($levels, static function ($item) use ($level) {
            return (int)$item['level'] !== $level;
        }));

        self::saveLevels($levels);
    }

    /**
     * @notes 获取每级代理可发展的下级数量上限
     *   返回结构 {"1": {"2": int, "3": int}, "2": {"3": int}}，0 表示不限
     *   最低一级没有可发展的下级，不会出现在返回结果中
     * @return array
     */
    public static function getSubLimits(): array
    {
        return self::normalizeSubLimits(
            self::getLevelValues(),
            ConfigService::get(self::CONFIG_TYPE, self::KEY_SUB_LIMITS)
        );
    }

    /**
     * @notes 设置每级代理可发展的下级数量上限
     * @param array $params
     */
    public static function setSubLimits(array $params): void
    {
        $incoming = self::decodeArray($params['limits'] ?? []);
        // 只覆盖本次提交到的等级，其余沿用已存库的配置
        $merged = array_replace(self::getSubLimits(), $incoming);

        ConfigService::set(
            self::CONFIG_TYPE,
            self::KEY_SUB_LIMITS,
            json_encode(self::normalizeSubLimits(self::getLevelValues(), $merged))
        );
    }

    /**
     * @notes 绑定上级场景：按「被绑用户当前代理等级」对应校验上级对该类型的人数上限
     * 上限读取 getSubLimits（后台可动态调整），与 setLevel 同口径：
     * - 上级等级无配置（如最低一级）：不可发展下级
     * - 被绑用户 level=0（普通用户）：不做类型人数限制
     * - 上级对该下级等级无配置项：不可邀请该等级
     * - 对应类型上限为 0：不限；>0 则统计同 parent_id + 同 level 人数
     * @param int $parentUserId 上级用户 ID
     * @param int $childUserId  被绑定用户 ID
     * @throws \Exception
     */
    public static function checkCanAcceptBind(int $parentUserId, int $childUserId): void
    {
        $parentAgent = DistributionAgent::where('user_id', $parentUserId)->findOrEmpty();
        if ($parentAgent->isEmpty() || (int)$parentAgent->status !== 1) {
            throw new \Exception('上级用户还不是代理');
        }

        $parentLevel = (int)$parentAgent->level;
        $allLimits = self::getSubLimits();
        $typeLimits = $allLimits[(string)$parentLevel] ?? null;
        if (!is_array($typeLimits) || $typeLimits === []) {
            throw new \Exception('该上级无法邀请下级');
        }

        $childAgent = DistributionAgent::where('user_id', $childUserId)->findOrEmpty();
        $childLevel = $childAgent->isEmpty() ? 0 : (int)$childAgent->level;
        // 普通用户（level=0）绑定不占用「可发展 X 级代理」名额
        if ($childLevel <= 0) {
            return;
        }

        if (!array_key_exists((string)$childLevel, $typeLimits)) {
            throw new \Exception('该上级无法邀请该等级下级');
        }

        $limit = (int)$typeLimits[(string)$childLevel];
        // 与后台约定一致：0 表示不限
        if ($limit <= 0) {
            return;
        }

        $existing = DistributionAgent::where('parent_id', $parentUserId)
            ->where('level', $childLevel)
            ->where('user_id', '<>', $childUserId)
            ->count();
        if ($existing >= $limit) {
            throw new \Exception("上级的{$childLevel}级下级已达上限（{$limit}人），无法绑定");
        }
    }

    /**
     * @notes 落库等级清单，并同步收敛下级上限矩阵的键集合
     * @param array $levels
     */
    private static function saveLevels(array $levels): void
    {
        $levels = self::normalizeLevels($levels);

        ConfigService::set(
            self::CONFIG_TYPE,
            self::KEY_LEVEL_NAMES,
            json_encode($levels, JSON_UNESCAPED_UNICODE)
        );
        ConfigService::set(
            self::CONFIG_TYPE,
            self::KEY_SUB_LIMITS,
            json_encode(self::normalizeSubLimits(
                array_column($levels, 'level'),
                ConfigService::get(self::CONFIG_TYPE, self::KEY_SUB_LIMITS)
            ))
        );
    }

    /**
     * @notes 归一化等级清单：补齐 remark、去重、按等级升序
     * @param mixed $raw
     * @return array
     */
    private static function normalizeLevels($raw): array
    {
        $levels = [];
        foreach (self::decodeArray($raw) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $level = (int)($item['level'] ?? 0);
            if ($level <= 0 || isset($levels[$level])) {
                continue;
            }
            $name = trim((string)($item['name'] ?? ''));
            $levels[$level] = [
                'level' => $level,
                'name' => $name !== '' ? $name : $level . '级代理',
                'remark' => trim((string)($item['remark'] ?? '')),
            ];
        }

        if ($levels === []) {
            return [
                ['level' => 1, 'name' => '高级代理', 'remark' => ''],
                ['level' => 2, 'name' => '中级代理', 'remark' => ''],
                ['level' => 3, 'name' => '初级代理', 'remark' => ''],
            ];
        }

        ksort($levels);
        return array_values($levels);
    }

    /**
     * @notes 归一化下级上限矩阵：键集合严格跟随当前等级清单，值取非负整数
     * @param array $levelValues 当前等级值，升序
     * @param mixed $raw
     * @return array
     */
    private static function normalizeSubLimits(array $levelValues, $raw): array
    {
        $raw = self::decodeArray($raw);
        sort($levelValues);

        $result = [];
        foreach ($levelValues as $level) {
            $subs = [];
            foreach ($levelValues as $subLevel) {
                if ($subLevel <= $level) {
                    continue;
                }
                $subs[(string)$subLevel] = max(0, (int)($raw[(string)$level][(string)$subLevel] ?? 0));
            }
            if ($subs !== []) {
                $result[(string)$level] = $subs;
            }
        }
        return $result;
    }

    /**
     * @notes 配置值可能是 JSON 字符串或数组，统一取数组
     * @param mixed $value
     * @return array
     */
    private static function decodeArray($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }
}
