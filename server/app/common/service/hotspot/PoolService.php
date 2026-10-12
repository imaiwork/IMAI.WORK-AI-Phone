<?php

namespace app\common\service\hotspot;

use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\hotspot\HotspotCreation;
use app\common\model\hotspot\HotspotPool;
use app\common\model\hotspot\HotspotPoolUse;
use app\common\model\hotspot\HotspotTask;
use think\facade\Db;

class PoolService
{
    /**
     * @var array<string, mixed>
     */
    private static array $testHooks = [];

    public static function setTestHooks(array $hooks): void
    {
        self::$testHooks = $hooks;
    }

    public static function clearTestHooks(): void
    {
        self::$testHooks = [];
    }

    public static function isTitleOnly(string $script): bool
    {
        return trim($script) === '';
    }

    /** 入库分析快照：缺字段、非数组、空数组都视为没有分析 */
    public static function analysisPayloadEmpty(mixed $analysis): bool
    {
        return !is_array($analysis) || $analysis === [];
    }

    /**
     * 口播生成用的分析快照：优先池内 analysis_json，没有则按 analysis_id 回读分析台账。
     * analysis_id 与当前库行人设/话题不一致时忽略快照，避免跨人设串味。
     */
    public static function resolveAnalysis(HotspotPool $pool): array
    {
        $analysisId = (int)($pool->analysis_id ?? 0);
        $row = null;
        if ($analysisId > 0) {
            $found = HotspotAnalysis::where('id', $analysisId)->findOrEmpty();
            if (!$found->isEmpty()) {
                $row = [
                    'user_id' => (int)($found->user_id ?? 0),
                    'persona_id' => (int)($found->persona_id ?? 0),
                    'topic' => (string)($found->topic ?? ''),
                    'platform' => (string)($found->platform ?? ''),
                    'fit_score' => (int)($found->fit_score ?? 0),
                    'fit_reason' => (string)($found->fit_reason ?? ''),
                    'hooks' => self::asArray($found->hooks_json ?? []),
                    'risks' => self::asArray($found->risks_json ?? []),
                    'recommended_goal' => (string)($found->recommended_goal ?? ''),
                    'recommended_direction' => (string)($found->recommended_direction ?? ''),
                ];
            }
        }
        $compatible = $analysisId <= 0 || ($row !== null && self::isAnalysisCompatible(
            $row,
            (int)($pool->user_id ?? 0),
            (int)($pool->persona_id ?? 0),
            (string)($pool->topic ?? ''),
            (string)($pool->platform ?? '')
        ));
        $picked = self::pickResolvedAnalysis(
            [
                'id' => (int)($pool->id ?? 0),
                'user_id' => (int)($pool->user_id ?? 0),
                'persona_id' => (int)($pool->persona_id ?? 0),
                'topic' => (string)($pool->topic ?? ''),
                'platform' => (string)($pool->platform ?? ''),
                'analysis_id' => $analysisId,
            ],
            $row,
            is_array($pool->analysis_json ?? null) ? $pool->analysis_json : []
        );
        if ($analysisId > 0 && !$compatible) {
            HotspotLog::write(sprintf(
                '热点分析快照已忽略：编号=%d 分析编号=%d 与当前人设或话题不匹配',
                (int)($pool->id ?? 0),
                $analysisId
            ));
        }
        return $picked;
    }

    /**
     * @param array{id?:int,user_id?:int,persona_id?:int,topic?:string,platform?:string,analysis_id?:int} $pool
     * @param array<string, mixed>|null $analysisRow
     * @param array<string, mixed> $storedJson
     * @return array<string, mixed>
     */
    public static function pickResolvedAnalysis(array $pool, ?array $analysisRow, array $storedJson): array
    {
        $analysisId = (int)($pool['analysis_id'] ?? 0);
        if ($analysisId > 0 && ($analysisRow === null || !self::isAnalysisCompatible(
            $analysisRow,
            (int)($pool['user_id'] ?? 0),
            (int)($pool['persona_id'] ?? 0),
            (string)($pool['topic'] ?? ''),
            (string)($pool['platform'] ?? '')
        ))) {
            return [];
        }
        if ($storedJson !== []) {
            return $storedJson;
        }
        if ($analysisRow === null) {
            return [];
        }
        return [
            'fit_score' => (int)($analysisRow['fit_score'] ?? 0),
            'fit_reason' => (string)($analysisRow['fit_reason'] ?? ''),
            'hooks' => self::asArray($analysisRow['hooks'] ?? []),
            'risks' => self::asArray($analysisRow['risks'] ?? []),
            'recommended_goal' => (string)($analysisRow['recommended_goal'] ?? ''),
            'recommended_direction' => (string)($analysisRow['recommended_direction'] ?? ''),
        ];
    }

    public static function visualMaterialMode(int $visualMaterialSource): string
    {
        return match ($visualMaterialSource) {
            1 => 'ai',
            3 => 'persona',
            default => 'ai_persona',
        };
    }

    public static function stock(int $userId, array $params): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再入库');
        }
        $personaId = (int)($params['persona_id'] ?? 0);
        $personaName = '';
        if ($personaId > 0) {
            $persona = self::requireEnabledPersona($userId, $personaId);
            $personaId = (int)$persona->id;
            $personaName = trim((string)($persona->persona_name ?? ''));
        }
        $topic = RecordService::normalizeTopic($params['topic'] ?? '');
        if ($topic === '') {
            throw new HotspotUpstreamException('话题不能为空');
        }
        $platform = trim((string)($params['platform'] ?? 'douyin'));
        if ($platform === '') {
            $platform = 'douyin';
        }

        $script = trim((string)($params['script'] ?? ''));
        $titleInput = trim((string)($params['title'] ?? ''));
        $title = $titleInput !== '' ? $titleInput : $topic;
        $summary = (string)($params['summary'] ?? '');
        $corePoints = self::asArray($params['core_points'] ?? []);
        $analysis = self::asArray($params['analysis'] ?? []);
        $analysisId = (int)($params['analysis_id'] ?? 0);
        $creationId = (int)($params['creation_id'] ?? 0);

        if ($analysisId > 0) {
            $row = HotspotAnalysis::where('id', $analysisId)
                ->where('user_id', $userId)
                ->findOrEmpty();
            $bind = !$row->isEmpty() && self::isAnalysisCompatible(
                [
                    'user_id' => (int)($row->user_id ?? 0),
                    'persona_id' => (int)($row->persona_id ?? 0),
                    'topic' => (string)($row->topic ?? ''),
                    'platform' => (string)($row->platform ?? ''),
                ],
                $userId,
                $personaId,
                $topic,
                $platform
            );
            if (!$bind) {
                HotspotLog::write(sprintf(
                    '热点库入库忽略分析关联：用户=%d 人设id=%d 话题=%s 分析编号=%d 记录缺失或与当前人设/话题不匹配',
                    $userId,
                    $personaId,
                    $topic,
                    $analysisId
                ));
                $analysisId = 0;
            } else {
                if ($analysis === []) {
                    $analysis = [
                        'fit_score' => (int)($row->fit_score ?? 0),
                        'fit_reason' => (string)($row->fit_reason ?? ''),
                        'hooks' => self::asArray($row->hooks_json ?? []),
                        'risks' => self::asArray($row->risks_json ?? []),
                        'recommended_goal' => (string)($row->recommended_goal ?? ''),
                        'recommended_direction' => (string)($row->recommended_direction ?? ''),
                    ];
                }
                if ($summary === '') {
                    $summary = (string)($row->fit_reason ?? '');
                }
            }
        }

        // 入库和更新入库共用本方法：请求没带分析、台账也补不出来时拒绝，不落空快照
        if (self::analysisPayloadEmpty($analysis)) {
            throw new HotspotUpstreamException('分析不能为空');
        }

        if ($creationId > 0) {
            $creation = HotspotCreation::where('id', $creationId)
                ->where('user_id', $userId)
                ->findOrEmpty();
            $creationId = self::resolveStockCreationId(
                $userId,
                $personaId,
                $personaName,
                $topic,
                $platform,
                $creationId,
                $creation->isEmpty() ? [] : [
                    'id' => (int)$creation->id,
                    'user_id' => (int)($creation->user_id ?? 0),
                    'topic' => (string)($creation->topic ?? ''),
                    'platform' => (string)($creation->platform ?? ''),
                    'persona_id' => (int)($creation->persona_id ?? 0),
                    'persona_name' => (string)($creation->persona_name ?? ''),
                ]
            );
            if ((int)($params['creation_id'] ?? 0) > 0 && $creationId <= 0) {
                HotspotLog::write(sprintf(
                    '热点库入库忽略创作关联：用户=%d 人设id=%d 话题=%s 创作编号=%d 与当前人设或话题不匹配',
                    $userId,
                    $personaId,
                    $topic,
                    (int)($params['creation_id'] ?? 0)
                ));
            }
        }

        if (self::scriptCopiesOtherPersona(
            $script,
            $personaId,
            self::otherPersonaScriptRows($userId, $personaId, $platform, $topic, $script)
        )) {
            HotspotLog::write(sprintf(
                '热点库入库丢弃串稿口播：用户=%d 人设id=%d 话题=%s 与其他人设逐字相同',
                $userId,
                $personaId,
                $topic
            ));
            $script = '';
            $titleInput = '';
            $title = $topic;
        }

        $goal = trim((string)($params['goal'] ?? ($analysis['recommended_goal'] ?? '')));
        $direction = trim((string)($params['direction'] ?? ($analysis['recommended_direction'] ?? '')));

        $now = time();
        $data = [
            'user_id' => $userId,
            'persona_id' => $personaId,
            'platform' => $platform,
            'topic' => $topic,
            'analysis_id' => $analysisId,
            'creation_id' => $creationId,
            'title' => mb_substr($title, 0, 200),
            'script' => $script,
            'summary' => $summary,
            'core_points_json' => $corePoints,
            'analysis_json' => $analysis,
            'goal' => mb_substr($goal, 0, 16),
            'direction' => mb_substr($direction, 0, 32),
            'update_time' => $now,
        ];

        $existing = self::findPending($userId, $personaId, $platform, $topic);
        if ($existing !== null) {
            $data['_title_from_topic'] = $titleInput === '' || $titleInput === $topic;
            $data = self::mergeStockUpdate($data, [
                'script' => (string)($existing->script ?? ''),
                'title' => (string)($existing->title ?? ''),
                'topic' => (string)($existing->topic ?? $topic),
                'summary' => (string)($existing->summary ?? ''),
                'goal' => (string)($existing->goal ?? ''),
                'direction' => (string)($existing->direction ?? ''),
                'core_points_json' => is_array($existing->core_points_json ?? null) ? $existing->core_points_json : [],
                'analysis_json' => is_array($existing->analysis_json ?? null) ? $existing->analysis_json : [],
                'creation_id' => (int)($existing->creation_id ?? 0),
                'analysis_id' => (int)($existing->analysis_id ?? 0),
            ]);
            unset($data['_title_from_topic']);
            $resetUse = false;
            Db::transaction(function () use ($existing, $data, &$resetUse) {
                $resetUse = self::resetStockUse($existing);
                if ($resetUse) {
                    $data['use_count'] = 0;
                    $data['used_time'] = 0;
                    $data['related_video_task_id'] = 0;
                }
                $existing->save($data);
            });
            HotspotLog::write(sprintf(
                '热点库待用更新：用户=%d 人设id=%d 话题=%s 编号=%d 仅标题=%s 重置占用=%s',
                $userId,
                $personaId,
                $topic,
                (int)$existing->id,
                self::isTitleOnly((string)($data['script'] ?? '')) ? '是' : '否',
                $resetUse ? '是' : '否'
            ));
            return self::toApi($existing->refresh()) + ['updated' => true];
        }

        $row = HotspotPool::create($data + [
            'use_count' => 0,
            'used_time' => 0,
            'related_video_task_id' => 0,
            'create_time' => $now,
        ]);
        HotspotLog::write(sprintf(
            '热点库待用写入：用户=%d 人设id=%d 话题=%s 编号=%d 仅标题=%s',
            $userId,
            $personaId,
            $topic,
            (int)$row->id,
            self::isTitleOnly($script) ? '是' : '否'
        ));
        return self::toApi($row) + ['updated' => false];
    }

    /** 待用详情：同一用户 + 人设 + 平台 + 话题且未使用的记录 */
    public static function stockDetail(int $userId, array $params): array
    {
        $row = self::findPending(
            $userId,
            (int)($params['persona_id'] ?? 0),
            (string)($params['platform'] ?? ''),
            (string)($params['topic'] ?? '')
        );
        if ($row === null) {
            return ['found' => false];
        }
        return ['found' => true] + self::toApi($row);
    }

    public static function lists(int $userId, int $personaId): array
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再查看热点库');
        }
        if ($personaId > 0) {
            self::requireEnabledPersona($userId, $personaId);
        }
        $rows = self::catalogPoolQuery($userId, $personaId)
            ->order('id', 'desc')
            ->select();
        $occupiedIds = self::occupiedPoolIdSet($rows);
        $pending = [];
        $used = [];
        foreach ($rows as $row) {
            $item = self::toApi($row, $occupiedIds);
            if (self::isPendingStockRow($item)) {
                $pending[] = $item;
            } else {
                $used[] = $item;
            }
        }
        $pendingCount = count($pending);
        $usedCount = count($used);
        return [
            'count' => $pendingCount + $usedCount,
            'pending_count' => $pendingCount,
            'used_count' => $usedCount,
            'lists' => array_merge($pending, $used),
        ];
    }

    public static function delete(int $userId, int $id): void
    {
        if ($userId <= 0) {
            throw new HotspotUpstreamException('请先登录后再移出热点库');
        }
        $row = HotspotPool::where('id', $id)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($row->isEmpty()) {
            throw new HotspotUpstreamException('待用记录不存在');
        }
        $row->delete();
        HotspotLog::write(sprintf('热点库移出：用户=%d 编号=%d 话题=%s', $userId, $id, (string)$row->topic));
    }

    public static function catalogCount(int $userId, int $personaId): int
    {
        if (array_key_exists('catalogCount', self::$testHooks)) {
            return (int)self::$testHooks['catalogCount'];
        }
        if ($userId <= 0 || $personaId <= 0) {
            return 0;
        }
        return (int)self::catalogPoolQuery($userId, $personaId)->count();
    }

    /**
     * 规则1 最新入库优先：id 降序；规则2 每条只用一次：按入库顺序 id 升序。
     */
    public static function consumeIdOrder(int $pickMode): string
    {
        return $pickMode === AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_ONCE_DEVICE ? 'asc' : 'desc';
    }

    /**
     * 按设备取用。规则1拿本设备未用过的最新条（id 降序，其他设备可同拿）；
     * 规则2全局每条只用一次，且按入库顺序先用（id 升序）。
     * $onLocked 在池行锁内执行（现写标题和口播并写入占用行），抛错则整笔占用回滚。
     * 回调签名：function (HotspotPool $row, HotspotPoolUse $log): void
     *
     * @return array{pool: HotspotPool, use_log_id: int, title: string, script: string}|null
     */
    public static function consumeForDevice(
        int $userId,
        int $personaId,
        string $deviceCode,
        int $pickMode,
        array $excludeIds = [],
        ?callable $onLocked = null
    ): ?array {
        if (array_key_exists('consumeForDevice', self::$testHooks)) {
            $hook = self::$testHooks['consumeForDevice'];
            return is_callable($hook)
                ? $hook($userId, $personaId, $deviceCode, $pickMode, $excludeIds, $onLocked)
                : $hook;
        }
        if ($userId <= 0 || $personaId <= 0 || $deviceCode === '') {
            return null;
        }
        $pickMode = AiPersonaSynthesisConfig::requireValidHotspotPickMode($pickMode);
        $excludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeIds))));

        return Db::transaction(function () use ($userId, $personaId, $deviceCode, $pickMode, $excludeIds, $onLocked) {
            $skipIds = $excludeIds;
            if ($pickMode === AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_ONCE_DEVICE) {
                $skipIds = array_values(array_unique(array_merge($skipIds, self::usedPoolIdsForUser($userId, $personaId))));
            } else {
                $skipIds = array_values(array_unique(array_merge(
                    $skipIds,
                    self::usedPoolIdsForDevice($userId, $deviceCode, $personaId)
                )));
            }

            $row = null;
            for ($try = 0; $try < 32; $try++) {
                $query = self::catalogPoolQuery($userId, $personaId);
                if ($skipIds !== []) {
                    $query->whereNotIn('id', $skipIds);
                }
                $candidate = $query->order('id', self::consumeIdOrder($pickMode))
                    ->lock(true)
                    ->findOrEmpty();
                if ($candidate->isEmpty()) {
                    return null;
                }
                $poolId = (int)$candidate->id;
                $occupiedQuery = HotspotPoolUse::where('pool_id', $poolId);
                if ($pickMode === AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_LATEST) {
                    $occupiedQuery->where('device_code', $deviceCode);
                }
                if (!$occupiedQuery->findOrEmpty()->isEmpty()) {
                    $skipIds[] = $poolId;
                    $skipIds = array_values(array_unique($skipIds));
                    continue;
                }
                $row = $candidate;
                break;
            }
            if ($row === null) {
                return null;
            }

            $now = time();
            $log = HotspotPoolUse::create([
                'pool_id' => (int)$row->id,
                'user_id' => $userId,
                'persona_id' => $personaId,
                'device_code' => $deviceCode,
                'hotspot_pick_mode' => $pickMode,
                'related_video_task_id' => 0,
                'create_time' => $now,
            ]);
            $row->use_count = (int)$row->use_count + 1;
            $row->used_time = $now;
            $row->update_time = $now;
            $row->save();

            if ($onLocked !== null) {
                try {
                    $onLocked($row, $log);
                    $row->refresh();
                } catch (HotspotUpstreamException $e) {
                    $e->poolId = (int)$row->id;
                    throw $e;
                } catch (\Throwable $e) {
                    $wrap = new HotspotUpstreamException($e->getMessage(), (int)$e->getCode(), $e);
                    $wrap->poolId = (int)$row->id;
                    throw $wrap;
                }
            }

            return [
                'pool' => $row,
                'use_log_id' => (int)$log->id,
                'title' => (string)($log->title ?? ''),
                'script' => (string)($log->script ?? ''),
            ];
        });
    }

    public static function consumeLatest(int $userId, int $personaId, array $excludeIds = []): ?HotspotPool
    {
        if (array_key_exists('consumeLatest', self::$testHooks)) {
            $hook = self::$testHooks['consumeLatest'];
            return is_callable($hook) ? $hook($userId, $personaId, $excludeIds) : $hook;
        }
        $consumed = self::consumeForDevice(
            $userId,
            $personaId,
            'legacy',
            AiPersonaSynthesisConfig::HOTSPOT_PICK_MODE_LATEST,
            $excludeIds
        );
        return $consumed['pool'] ?? null;
    }

    /**
     * 仅标题现写未点到话题：按设备写入占用，不增加 use_count，避免下一轮再捞同一条烧算力。
     */
    public static function parkMissTopicForDevice(
        int $userId,
        int $personaId,
        string $deviceCode,
        int $poolId,
        int $pickMode
    ): void {
        if (array_key_exists('parkMissTopicForDevice', self::$testHooks)) {
            $hook = self::$testHooks['parkMissTopicForDevice'];
            if (is_callable($hook)) {
                $hook($userId, $personaId, $deviceCode, $poolId, $pickMode);
            }
            return;
        }
        if ($userId <= 0 || $poolId <= 0 || $deviceCode === '') {
            return;
        }
        $pickMode = AiPersonaSynthesisConfig::requireValidHotspotPickMode($pickMode);
        try {
            $exists = HotspotPoolUse::where('user_id', $userId)
                ->where('device_code', $deviceCode)
                ->where('pool_id', $poolId)
                ->findOrEmpty();
            if (!$exists->isEmpty()) {
                return;
            }
            HotspotPoolUse::create([
                'pool_id' => $poolId,
                'user_id' => $userId,
                'persona_id' => max(0, $personaId),
                'device_code' => $deviceCode,
                'hotspot_pick_mode' => $pickMode,
                'related_video_task_id' => 0,
                'create_time' => time(),
            ]);
        } catch (\Throwable $e) {
            HotspotLog::write(sprintf(
                '热点编号=%d 口播未点到话题停用写入失败：%s',
                $poolId,
                $e->getMessage()
            ));
        }
    }

    public static function revertUse(int $id, bool $keepScript = true): void
    {
        self::revertDeviceUse($id, '', 0, $keepScript);
    }

    public static function revertDeviceUse(int $poolId, string $deviceCode, int $useLogId = 0, bool $keepScript = true): void
    {
        if (array_key_exists('revertUse', self::$testHooks)) {
            $hook = self::$testHooks['revertUse'];
            if (is_callable($hook)) {
                $hook($poolId, $keepScript, $deviceCode, $useLogId);
                return;
            }
        }
        if (array_key_exists('revertDeviceUse', self::$testHooks)) {
            $hook = self::$testHooks['revertDeviceUse'];
            if (is_callable($hook)) {
                $hook($poolId, $deviceCode, $useLogId, $keepScript);
                return;
            }
        }
        if ($poolId <= 0) {
            return;
        }
        try {
            Db::transaction(function () use ($poolId, $deviceCode, $useLogId, $keepScript) {
                $logQuery = HotspotPoolUse::where('pool_id', $poolId);
                if ($useLogId > 0) {
                    $logQuery->where('id', $useLogId);
                }
                if ($deviceCode !== '') {
                    $logQuery->where('device_code', $deviceCode);
                } elseif ($useLogId <= 0) {
                    return;
                }
                $log = $logQuery->order('id', 'desc')->findOrEmpty();
                if (!$log->isEmpty()) {
                    $log->delete();
                }

                $row = HotspotPool::where('id', $poolId)->lock(true)->findOrEmpty();
                if ($row->isEmpty()) {
                    return;
                }
                $row->use_count = max(0, (int)$row->use_count - 1);
                $latest = HotspotPoolUse::where('pool_id', $poolId)->order('id', 'desc')->findOrEmpty();
                if ($latest->isEmpty()) {
                    $row->used_time = 0;
                    $row->related_video_task_id = 0;
                } else {
                    $row->used_time = (int)($latest->create_time ?? 0);
                    $row->related_video_task_id = (int)($latest->related_video_task_id ?? 0);
                }
                if (!$keepScript) {
                    $row->script = '';
                }
                $row->update_time = time();
                $row->save();
            });
        } catch (\Throwable $e) {
            HotspotLog::exception('热点库占用回滚失败', $e);
        }
    }

    public static function saveScript(int $id, string $title, string $script, int $personaId = 0): void
    {
        if ($id <= 0) {
            return;
        }
        $row = HotspotPool::where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            return;
        }
        if (!self::claimPoolForPersona($row, $personaId)) {
            HotspotLog::write(sprintf(
                '热点库口播回写跳过：编号=%d 行人人设=%d 当前人设=%d',
                $id,
                (int)($row->persona_id ?? 0),
                $personaId
            ));
            return;
        }
        $row->title = mb_substr(trim($title) !== '' ? trim($title) : (string)$row->topic, 0, 200);
        $row->script = trim($script);
        $row->update_time = time();
        $row->save();
    }

    /**
     * 写回口播前：未绑行认领为当前人设；已属别人则拒绝。
     * $personaId<=0 时只允许写未绑行。
     */
    public static function claimPoolForPersona(HotspotPool $row, int $personaId): bool
    {
        $current = (int)($row->persona_id ?? 0);
        if ($personaId > 0) {
            if ($current === 0) {
                $row->persona_id = $personaId;
                return true;
            }
            return $current === $personaId;
        }
        return $current === 0;
    }

    public static function bindVideoTask(int $id, int $taskId, int $useLogId = 0): void
    {
        if (array_key_exists('bindVideoTask', self::$testHooks)) {
            $hook = self::$testHooks['bindVideoTask'];
            if (is_callable($hook)) {
                $hook($id, $taskId, $useLogId);
            }
            return;
        }
        if ($id <= 0 || $taskId <= 0) {
            return;
        }
        HotspotPool::where('id', $id)->update([
            'related_video_task_id' => $taskId,
            'update_time' => time(),
        ]);
        if ($useLogId > 0) {
            HotspotPoolUse::where('id', $useLogId)->update([
                'related_video_task_id' => $taskId,
            ]);
        }
    }

    /**
     * @param array<int, true> $occupiedIds
     */
    public static function toApi(HotspotPool $row, array $occupiedIds = []): array
    {
        $id = (int)$row->id;
        $useCount = (int)$row->use_count;
        $script = (string)($row->script ?? '');
        return [
            'id' => $id,
            'persona_id' => (int)$row->persona_id,
            'platform' => (string)$row->platform,
            'topic' => (string)$row->topic,
            'title' => (string)$row->title,
            'script' => $script,
            'has_script' => self::isTitleOnly($script) ? 0 : 1,
            'goal' => (string)$row->goal,
            'direction' => (string)$row->direction,
            'use_count' => $useCount,
            'use_status' => self::useStatusForRow($id, $useCount, $occupiedIds),
            'create_time' => self::toUnixTime($row->create_time),
        ];
    }

    /** 清缓存后还原分析现场：把热点库行转成 lastFlow 同构结构 */
    public static function toLastFlow(HotspotPool $row): array
    {
        $corePoints = [];
        foreach (self::asArray($row->core_points_json ?? []) as $item) {
            if (is_string($item)) {
                $text = trim($item);
                if ($text !== '') {
                    $corePoints[] = ['label' => $text, 'detail' => ''];
                }
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string)($item['label'] ?? ''));
            $detail = trim((string)($item['detail'] ?? ''));
            if ($label === '' && $detail === '') {
                continue;
            }
            $corePoints[] = [
                'label' => $label !== '' ? $label : $detail,
                'detail' => $label !== '' ? $detail : '',
            ];
        }
        $personaId = (int)($row->persona_id ?? 0);
        $created = self::toUnixTime($row->create_time);
        return [
            'found' => true,
            'topic' => (string)($row->topic ?? ''),
            'title' => (string)($row->title ?? ''),
            'script' => (string)($row->script ?? ''),
            'summary' => (string)($row->summary ?? ''),
            'core_points' => $corePoints,
            'citations' => [],
            'analysis' => self::asArray($row->analysis_json ?? []),
            'persona' => $personaId > 0 ? ['id' => $personaId] : [],
            'options' => [
                'goal' => (string)($row->goal ?? ''),
                'direction' => (string)($row->direction ?? ''),
            ],
            'created_at' => $created > 0 ? date('Y-m-d H:i:s', $created) : '',
        ];
    }

    private static function toUnixTime(mixed $value): int
    {
        if (is_numeric($value)) {
            $stamp = (int)$value;
            if ($stamp >= 1_000_000_000_000) {
                $stamp = intdiv($stamp, 1000);
            }
            return $stamp >= 1_000_000_000 ? $stamp : 0;
        }
        $text = trim((string)$value);
        if ($text === '') {
            return 0;
        }
        $stamp = strtotime($text);
        return ($stamp !== false && $stamp >= 1_000_000_000) ? $stamp : 0;
    }

    public static function requireEnabledPersona(int $userId, int $personaId): AiPersona
    {
        if ($personaId <= 0) {
            throw new HotspotUpstreamException('人设不能为空');
        }
        $persona = AiPersona::where('id', $personaId)
            ->where('user_id', $userId)
            ->findOrEmpty();
        if ($persona->isEmpty()) {
            throw new HotspotUpstreamException('人设不存在');
        }
        if ((int)$persona->status === AiPersona::STATUS_FROZEN) {
            throw new HotspotUpstreamException(AiPersona::FROZEN_USAGE_MESSAGE);
        }
        return $persona;
    }

    /**
     * 目录/去重只认这些人设 id。有人设时不含 0：未绑行不进自动化取用、也不被入库认领。
     *
     * @return list<int>
     */
    public static function scopedPersonaIds(int $personaId): array
    {
        return [$personaId];
    }

    /** 目录：精确人设（含未绑 persona_id=0 自己的列表），不按 use_count 过滤 */
    public static function catalogPoolQuery(int $userId, int $personaId)
    {
        $ids = self::scopedPersonaIds($personaId);
        $query = HotspotPool::where('user_id', $userId);
        if (count($ids) === 1) {
            $query->where('persona_id', $ids[0]);
        } else {
            $query->whereIn('persona_id', $ids);
        }
        return $query;
    }

    /**
     * 未使用判定：无占用日志且 use_count=0。仅标题未点到话题也会写占用日志、不增加 use_count，仍计为已使用。
     *
     * @param array<string, mixed> $row
     */
    public static function isPendingStockRow(array $row): bool
    {
        if (array_key_exists('use_status', $row)) {
            return (int)$row['use_status'] === HotspotPool::USE_STATUS_PENDING;
        }
        return self::useStatusFromCount((int)($row['use_count'] ?? 0)) === HotspotPool::USE_STATUS_PENDING;
    }

    public static function useStatusFromCount(int $useCount): int
    {
        return $useCount > 0 ? HotspotPool::USE_STATUS_USED : HotspotPool::USE_STATUS_PENDING;
    }

    /**
     * @param array<int, true> $occupiedIds
     */
    public static function useStatusForRow(int $poolId, int $useCount, array $occupiedIds = []): int
    {
        if ($poolId > 0 && isset($occupiedIds[$poolId])) {
            return HotspotPool::USE_STATUS_USED;
        }
        return self::useStatusFromCount($useCount);
    }

    /**
     * @param iterable<HotspotPool> $rows
     * @return array<int, true>
     */
    private static function occupiedPoolIdSet(iterable $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int)($row->id ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }
        $used = HotspotPoolUse::whereIn('pool_id', array_values(array_unique($ids)))->column('pool_id');
        $set = [];
        foreach ($used as $poolId) {
            $set[(int)$poolId] = true;
        }
        return $set;
    }

    /**
     * 更新入库：已使用则清占用日志并重置计数，使其重新待用。
     */
    private static function resetStockUse(HotspotPool $row): bool
    {
        $id = (int)$row->id;
        if ($id <= 0) {
            return false;
        }
        $occupied = (int)$row->use_count > 0
            || (int)HotspotPoolUse::where('pool_id', $id)->count() > 0;
        if (!$occupied) {
            return false;
        }
        HotspotPoolUse::where('pool_id', $id)->delete();
        $row->use_count = 0;
        $row->used_time = 0;
        $row->related_video_task_id = 0;
        return true;
    }

    /** 入库去重口径：同一用户、人设、平台、话题；不认领未绑行 */
    private static function findPending(int $userId, int $personaId, string $platform, string $topic): ?HotspotPool
    {
        $topic = RecordService::normalizeTopic($topic);
        $platform = self::normalizePlatform($platform);
        if ($userId <= 0 || $topic === '') {
            return null;
        }
        foreach (self::scopedPersonaIds($personaId) as $pid) {
            $row = self::findPendingByPersona($userId, $pid, $platform, $topic);
            if ($row !== null) {
                return $row;
            }
        }
        return null;
    }

    private static function findPendingByPersona(int $userId, int $personaId, string $platform, string $topic): ?HotspotPool
    {
        $row = HotspotPool::where('user_id', $userId)
            ->where('persona_id', $personaId)
            ->where('platform', $platform)
            ->where('topic', $topic)
            ->order('id', 'desc')
            ->findOrEmpty();
        return $row->isEmpty() ? null : $row;
    }

    /** @return int[] 该用户当前人设已占用的池 id（规则2全局只用一次） */
    private static function usedPoolIdsForUser(int $userId, int $personaId = 0): array
    {
        if ($userId <= 0) {
            return [];
        }
        $query = HotspotPoolUse::where('user_id', $userId);
        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }
        $ids = $query->column('pool_id');
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /** @return int[] 该设备当前人设已占用的池 id（规则1同设备不重复） */
    private static function usedPoolIdsForDevice(int $userId, string $deviceCode, int $personaId = 0): array
    {
        if ($userId <= 0 || $deviceCode === '') {
            return [];
        }
        $query = HotspotPoolUse::where('user_id', $userId)
            ->where('device_code', $deviceCode);
        if ($personaId > 0) {
            $query->where('persona_id', $personaId);
        }
        $ids = $query->column('pool_id');
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    public static function normalizePlatform(string $platform): string
    {
        $platform = trim($platform);
        return $platform === '' ? 'douyin' : $platform;
    }

    /**
     * 分析台账是否属于当前用户+人设+话题（空平台按入库口径视为抖音）。
     *
     * @param array{user_id?:int,persona_id?:int,topic?:string,platform?:string} $row
     */
    public static function isAnalysisCompatible(
        array $row,
        int $userId,
        int $personaId,
        string $topic,
        string $platform = ''
    ): bool {
        if ($userId <= 0 || (int)($row['user_id'] ?? 0) !== $userId) {
            return false;
        }
        if ((int)($row['persona_id'] ?? 0) !== $personaId) {
            return false;
        }
        if (RecordService::normalizeTopic($row['topic'] ?? '') !== RecordService::normalizeTopic($topic)) {
            return false;
        }
        $rowPlatform = trim((string)($row['platform'] ?? ''));
        if ($rowPlatform !== '' && self::normalizePlatform($rowPlatform) !== self::normalizePlatform($platform)) {
            return false;
        }
        return true;
    }

    /**
     * 入库创作关联：同用户、同话题、同平台，且创作台账人设 id 与当前人设一致。
     *
     * @param array<string, mixed> $creation
     */
    public static function resolveStockCreationId(
        int $userId,
        int $personaId,
        string $personaName,
        string $topic,
        string $platform,
        int $creationId,
        array $creation
    ): int {
        if ($creationId <= 0 || $creation === []) {
            return 0;
        }
        if ((int)($creation['id'] ?? 0) !== $creationId) {
            return 0;
        }
        if ((int)($creation['user_id'] ?? 0) !== $userId) {
            return 0;
        }
        if (RecordService::normalizeTopic($creation['topic'] ?? '') !== RecordService::normalizeTopic($topic)) {
            return 0;
        }
        if (self::normalizePlatform((string)($creation['platform'] ?? '')) !== self::normalizePlatform($platform)) {
            return 0;
        }
        if (!self::creationPersonaMatches($creation, $personaId, $personaName)) {
            return 0;
        }
        return $creationId;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function creationPersonaMatches(array $row, int $personaId, string $personaName = ''): bool
    {
        $rowPersonaId = (int)($row['persona_id'] ?? $row['personaId'] ?? 0);
        if ($rowPersonaId <= 0 || $personaId <= 0) {
            return false;
        }
        return $rowPersonaId === $personaId;
    }

    /**
     * 仅标题二次入库不冲空已有口播；空快照不覆盖非空快照。
     *
     * @param array<string, mixed> $incoming
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    public static function mergeStockUpdate(array $incoming, array $existing): array
    {
        $incomingScript = (string)($incoming['script'] ?? '');
        $existingScript = (string)($existing['script'] ?? '');
        if (self::isTitleOnly($incomingScript) && !self::isTitleOnly($existingScript)) {
            $incoming['script'] = $existingScript;
            $topic = (string)($incoming['topic'] ?? $existing['topic'] ?? '');
            $incomingTitle = trim((string)($incoming['title'] ?? ''));
            $titleFromTopic = !empty($incoming['_title_from_topic']) || $incomingTitle === '' || $incomingTitle === $topic;
            if ($titleFromTopic && trim((string)($existing['title'] ?? '')) !== '') {
                $incoming['title'] = (string)$existing['title'];
            }
        }
        unset($incoming['_title_from_topic']);

        foreach (['summary', 'goal', 'direction'] as $key) {
            if (trim((string)($incoming[$key] ?? '')) === '' && trim((string)($existing[$key] ?? '')) !== '') {
                $incoming[$key] = $existing[$key];
            }
        }
        foreach (['core_points_json', 'analysis_json'] as $key) {
            $in = $incoming[$key] ?? [];
            $ex = $existing[$key] ?? [];
            if ((!is_array($in) || $in === []) && is_array($ex) && $ex !== []) {
                $incoming[$key] = $ex;
            }
        }
        foreach (['creation_id', 'analysis_id'] as $key) {
            if ((int)($incoming[$key] ?? 0) <= 0 && (int)($existing[$key] ?? 0) > 0) {
                $incoming[$key] = (int)$existing[$key];
            }
        }
        return $incoming;
    }

    /**
     * 同一用户、平台、话题下，口播与其他人设热点库逐字相同则是串稿。
     * 空文案、以及只和当前人设自己的记录相同，都不算。
     *
     * @param list<array{persona_id?:int,script?:string}> $rows
     */
    public static function scriptCopiesOtherPersona(string $script, int $personaId, array $rows): bool
    {
        if (self::isTitleOnly($script)) {
            return false;
        }
        $script = trim($script);
        foreach ($rows as $row) {
            if ((int)($row['persona_id'] ?? 0) === $personaId) {
                continue;
            }
            if (trim((string)($row['script'] ?? '')) === $script) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<array{persona_id:int,script:string}>
     */
    private static function otherPersonaScriptRows(
        int $userId,
        int $personaId,
        string $platform,
        string $topic,
        string $script
    ): array {
        if (self::isTitleOnly($script) || $userId <= 0 || $topic === '') {
            return [];
        }
        if (array_key_exists('otherPersonaScripts', self::$testHooks)) {
            $hook = self::$testHooks['otherPersonaScripts'];
            return is_array($hook) ? $hook : [];
        }
        $found = HotspotPool::where('user_id', $userId)
            ->where('platform', $platform)
            ->where('topic', $topic)
            ->where('persona_id', '<>', $personaId)
            ->where('script', $script)
            ->field(['persona_id', 'script'])
            ->limit(1)
            ->select();
        $rows = [];
        foreach ($found as $row) {
            $rows[] = [
                'persona_id' => (int)($row->persona_id ?? 0),
                'script' => (string)($row->script ?? ''),
            ];
        }
        return $rows;
    }

    /**
     * 24h 仅标题占用时回填追踪口播。找不到返回 null，由调用方现写。
     *
     * @return array{title: string, script: string, source: string, source_id: int}|null
     */
    public static function findReusableScript(HotspotPool $pool, int $personaId = 0, string $personaName = ''): ?array
    {
        if (array_key_exists('findReusableScript', self::$testHooks)) {
            $hook = self::$testHooks['findReusableScript'];
            return is_callable($hook) ? $hook($pool, $personaId, $personaName) : $hook;
        }

        $userId = (int)($pool->user_id ?? 0);
        if ($userId <= 0) {
            return null;
        }
        $topic = RecordService::normalizeTopic($pool->topic ?? '');
        $platform = self::normalizePlatform((string)($pool->platform ?? ''));
        $creationId = (int)($pool->creation_id ?? 0);

        $creationRows = [];
        if ($creationId > 0) {
            $one = HotspotCreation::where('id', $creationId)->findOrEmpty();
            if (!$one->isEmpty()) {
                $creationRows[] = self::creationToCandidate($one);
            }
        }
        if ($personaId > 0 && $topic !== '') {
            $moreCreations = HotspotCreation::where('user_id', $userId)
                ->where('topic', $topic)
                ->where('platform', $platform)
                ->where('persona_id', $personaId)
                ->order('id', 'desc')
                ->limit(50)
                ->select();
            foreach ($moreCreations as $one) {
                if ($creationId > 0 && (int)$one->id === $creationId) {
                    continue;
                }
                $creationRows[] = self::creationToCandidate($one);
            }
        }

        $taskRows = [];
        if ($topic !== '') {
            $tasks = HotspotTask::where('user_id', $userId)
                ->where('topic', $topic)
                ->where('platform', $platform)
                ->order('id', 'desc')
                ->limit(50)
                ->select();
            foreach ($tasks as $one) {
                $taskRows[] = self::taskToCandidate($one);
            }
        }

        return self::selectReusableFromCandidates(
            [
                'user_id' => $userId,
                'topic' => $topic,
                'platform' => $platform,
                'creation_id' => $creationId,
            ],
            $creationRows,
            $taskRows,
            $personaId,
            $personaName
        );
    }

    /**
     * @param array{user_id?:int,topic?:string,platform?:string,creation_id?:int} $pool
     * @param list<array<string, mixed>> $creations 按 id 降序
     * @param list<array<string, mixed>> $tasks 按 id 降序
     * @return array{title: string, script: string, source: string, source_id: int}|null
     */
    public static function selectReusableFromCandidates(
        array $pool,
        array $creations,
        array $tasks,
        int $personaId = 0,
        string $personaName = '',
        bool $logWiden = true
    ): ?array {
        $userId = (int)($pool['user_id'] ?? 0);
        $topic = RecordService::normalizeTopic($pool['topic'] ?? '');
        $platform = self::normalizePlatform((string)($pool['platform'] ?? ''));
        $creationId = (int)($pool['creation_id'] ?? 0);

        $usable = static function (array $row) use ($userId, $topic, $platform): bool {
            if ($userId <= 0 || (int)($row['user_id'] ?? 0) !== $userId) {
                return false;
            }
            if (RecordService::normalizeTopic($row['topic'] ?? '') !== $topic) {
                return false;
            }
            if (self::normalizePlatform((string)($row['platform'] ?? '')) !== $platform) {
                return false;
            }
            return !self::isTitleOnly((string)($row['script'] ?? ''));
        };

        $pack = static function (array $row, string $source): array {
            return [
                'title' => trim((string)($row['title'] ?? '')),
                'script' => trim((string)($row['script'] ?? '')),
                'source' => $source,
                'source_id' => (int)($row['id'] ?? 0),
            ];
        };

        if ($creationId > 0) {
            foreach ($creations as $row) {
                if ((int)($row['id'] ?? 0) !== $creationId) {
                    continue;
                }
                if ($usable($row) && self::creationPersonaMatches($row, $personaId, $personaName)) {
                    return $pack($row, 'creation');
                }
                break;
            }
        }

        foreach ($tasks as $row) {
            if (!$usable($row)) {
                continue;
            }
            if ((int)($row['persona_id'] ?? 0) === $personaId) {
                return $pack($row, 'task');
            }
        }

        foreach ($creations as $row) {
            if ($creationId > 0 && (int)($row['id'] ?? 0) === $creationId) {
                continue;
            }
            if (!$usable($row)) {
                continue;
            }
            if (self::creationPersonaMatches($row, $personaId, $personaName)) {
                return $pack($row, 'creation');
            }
        }
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function creationToCandidate(HotspotCreation $row): array
    {
        return [
            'id' => (int)$row->id,
            'user_id' => (int)($row->user_id ?? 0),
            'topic' => (string)($row->topic ?? ''),
            'platform' => (string)($row->platform ?? ''),
            'title' => (string)($row->title ?? ''),
            'script' => (string)($row->script ?? ''),
            'persona_id' => (int)($row->persona_id ?? 0),
            'persona_name' => (string)($row->persona_name ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function taskToCandidate(HotspotTask $row): array
    {
        $persona = is_array($row->persona_json ?? null) ? $row->persona_json : [];
        return [
            'id' => (int)$row->id,
            'user_id' => (int)($row->user_id ?? 0),
            'topic' => (string)($row->topic ?? ''),
            'platform' => (string)($row->platform ?? ''),
            'title' => (string)($row->title ?? ''),
            'script' => (string)($row->script ?? ''),
            'persona_id' => (int)($persona['id'] ?? 0),
        ];
    }

    private static function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
