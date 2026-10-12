<?php

namespace app\common\service\hotspot;

use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\hotspot\HotspotCreation;
use app\common\model\hotspot\HotspotPool;
use app\common\model\hotspot\HotspotTask;

class RecordService
{
    public const MATERIAL_LABELS = [
        'ai' => 'AI找素材',
        'ai_persona' => 'AI+人设素材',
        'persona' => '纯人设素材',
    ];

    public static function recordAnalysis(int $userId, array $params, array $result): void
    {
        try {
            $persona = self::asArray($params['persona'] ?? []);
            $personaId = self::resolvePersonaId($persona);
            $now = time();
            $topic = self::normalizeTopic($result['topic'] ?? $params['topic'] ?? '');
            HotspotAnalysis::create([
                'record_no' => self::newRecordNo('ANA', 'hotspot_analysis'),
                'user_id' => $userId,
                'persona_id' => $personaId,
                'topic' => $topic,
                'platform' => (string)($params['platform'] ?? 'douyin'),
                'persona_json' => [
                    'id' => (string)($persona['id'] ?? ''),
                    'name' => (string)($persona['name'] ?? ($result['persona_name'] ?? '')),
                    'tag' => (string)($persona['tag'] ?? ''),
                    'avatar' => (string)($persona['avatar'] ?? ''),
                ],
                'fit_score' => max(0, min(100, (int)($result['fit_score'] ?? 0))),
                'fit_reason' => mb_substr((string)($result['fit_reason'] ?? ''), 0, 1000),
                'hooks_json' => self::normalizeHooks($result['hooks'] ?? []),
                'risks_json' => self::normalizeRisks($result['risks'] ?? []),
                'recommended_goal' => (string)($result['recommended_goal'] ?? ''),
                'recommended_direction' => (string)($result['recommended_direction'] ?? ''),
                'create_time' => $now,
                'update_time' => $now,
            ]);
            HotspotLog::write(sprintf(
                '分析记录写入成功：用户=%d 人设id=%d 话题=%s 分数=%d',
                $userId,
                $personaId,
                $topic,
                (int)($result['fit_score'] ?? 0)
            ));
        } catch (\Throwable $e) {
            HotspotLog::exception('分析记录写入失败', $e);
        }
    }

    public static function recordCreation(int $userId, array $params, array $result): void
    {
        try {
            $persona = self::asArray($params['persona'] ?? []);
            $options = ScriptService::normalizeOptions(self::asArray($params['options'] ?? []));
            $topic = (string)($result['topic'] ?? $params['topic'] ?? '');
            $platform = (string)($params['platform'] ?? 'douyin');
            $personaName = (string)($persona['name'] ?? '');
            $personaId = self::resolvePersonaId($persona);
            $now = time();

            $data = [
                'goal' => (string)($options['goal'] ?? ''),
                'direction' => (string)($options['direction'] ?? ''),
                'material_mode' => (string)($options['material_mode'] ?? ''),
                'duration_sec' => (int)($options['duration_sec'] ?? 0),
                'video_type' => (string)($options['video_type'] ?? ''),
                'avatar' => (string)($options['avatar'] ?? ''),
                'title' => (string)($result['title'] ?? ''),
                'script' => (string)($result['script'] ?? ''),
                'word_count' => (int)($result['word_count'] ?? 0),
                'est_duration_sec' => (int)($result['est_duration_sec'] ?? 0),
                'hashtags_json' => self::asStringList($result['hashtags'] ?? []),
                'shots_json' => self::asStringList($result['shots'] ?? []),
                'persona_id' => $personaId,
                'update_time' => $now,
            ];

            // 同一流程内反复「重新生成文案」更新同一条台账（仅文案且未关联任务的最新记录），
            // 避免后台创作记录膨胀出大量重复行，与小程序侧的创作感知保持一致
            $existing = HotspotCreation::where(self::creationLookupWhere($userId, $personaId, $topic, $platform))
                ->where('status', 'script')
                ->where('task_no', '')
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$existing->isEmpty()) {
                $existing->save($data);
                HotspotLog::write(sprintf(
                    '创作记录更新成功：用户=%d 人设id=%d 话题=%s 标题=%s 编号=%s',
                    $userId,
                    $personaId,
                    $topic,
                    $data['title'],
                    (string)$existing->record_no
                ));
                return;
            }

            HotspotCreation::create($data + [
                'record_no' => self::newRecordNo('CRT', 'hotspot_creation'),
                'user_id' => $userId,
                'topic' => $topic,
                'platform' => $platform,
                'persona_id' => $personaId,
                'persona_name' => $personaName,
                'task_no' => '',
                'status' => 'script',
                'create_time' => $now,
            ]);
            HotspotLog::write(sprintf(
                '创作记录写入成功：用户=%d 话题=%s 标题=%s',
                $userId,
                $topic,
                $data['title']
            ));
        } catch (\Throwable $e) {
            HotspotLog::exception('创作记录写入失败', $e);
        }
    }

    public static function attachAnalyzed(array $topics, int $userId, string $platform, int $personaId = 0): array
    {
        if ($topics === []) {
            return [];
        }

        $marks = self::emptyAnalyzedMap();
        try {
            $marks = self::loadAnalyzedMap($userId, $platform, $topics, $personaId);
        } catch (\Throwable $e) {
            HotspotLog::exception('热榜回填已分析失败', $e);
            $marks = self::emptyAnalyzedMap();
        }

        $out = [];
        foreach ($topics as $topic) {
            if (!is_array($topic)) {
                continue;
            }
            $title = self::normalizeTopic($topic['title'] ?? '');
            $analysisId = (int)($marks['analysis'][$title] ?? 0);
            // 不选人设走「搜索→文案→合成」，不产生人设契合记录：有任务同样代表这条热点做过。
            // 入库（热点库）也算做过：全局热榜不传人设，只看分析台账会漏标。
            $topic['analyzed'] = $analysisId > 0
                || !empty($marks['task'][$title])
                || !empty($marks['pool'][$title]);
            // 全局热榜不传人设：analysis_id 不能填别人设的台账，否则更新入库会误报不匹配
            $topic['analysis_id'] = self::resolveHotListAnalysisId($personaId, $analysisId);
            $out[] = $topic;
        }
        return $out;
    }

    public static function resolvePersonaId(array $persona): int
    {
        return max(0, (int)($persona['id'] ?? $persona['persona_id'] ?? 0));
    }

    /** 全局热榜（persona_id<=0）只点亮已分析，不把跨人设 analysis_id 回填到列表 */
    public static function resolveHotListAnalysisId(int $personaId, int $analysisId): int
    {
        return $personaId > 0 ? max(0, $analysisId) : 0;
    }

    /**
     * 创作台账查找键：同用户、人设、话题、平台。同名不同人设互不覆盖。
     *
     * @return array{user_id:int,persona_id:int,topic:string,platform:string}
     */
    public static function creationLookupKey(int $userId, int $personaId, string $topic, string $platform): array
    {
        return [
            'user_id' => $userId,
            'persona_id' => max(0, $personaId),
            'topic' => self::normalizeTopic($topic),
            'platform' => $platform === '' ? 'douyin' : $platform,
        ];
    }

    /**
     * @return array<int, array{0:string,1:string,2:int|string}>
     */
    public static function creationLookupWhere(int $userId, int $personaId, string $topic, string $platform): array
    {
        $key = self::creationLookupKey($userId, $personaId, $topic, $platform);
        return [
            ['user_id', '=', $key['user_id']],
            ['persona_id', '=', $key['persona_id']],
            ['topic', '=', $key['topic']],
            ['platform', '=', $key['platform']],
        ];
    }

    public static function personaIdFromJson(mixed $personaJson): int
    {
        $persona = is_array($personaJson) ? $personaJson : [];
        return max(0, (int)($persona['id'] ?? $persona['persona_id'] ?? 0));
    }

    /**
     * 同话题任务按人设取最新一条（tasks 须已按 id desc）。
     *
     * @param list<array<string, mixed>> $tasks
     * @return array<string, mixed>|null
     */
    public static function pickLastFlowTask(array $tasks, int $personaId): ?array
    {
        foreach ($tasks as $row) {
            $pid = array_key_exists('persona_id', $row)
                ? (int)$row['persona_id']
                : self::personaIdFromJson($row['persona_json'] ?? []);
            if ($pid === $personaId) {
                return $row;
            }
        }
        return null;
    }

    /**
     * $personaId<=0 视为全局热榜：任意人设的分析/任务/入库都点亮「已分析」。
     * $personaId>0 仍按人设隔离。
     *
     * @param list<array<string, mixed>> $analysisRows 须已按 id desc，同话题只取第一条
     * @param list<array<string, mixed>> $taskRows
     * @param list<array<string, mixed>> $poolRows
     * @return array{analysis: array<string,int>, task: array<string,bool>, pool: array<string,bool>}
     */
    public static function pickAnalyzedMaps(
        array $analysisRows,
        array $taskRows,
        int $personaId,
        array $poolRows = []
    ): array {
        $map = [];
        foreach ($analysisRows as $row) {
            if ($personaId > 0 && (int)($row['persona_id'] ?? 0) !== $personaId) {
                continue;
            }
            $topic = self::normalizeTopic($row['topic'] ?? '');
            if ($topic === '' || isset($map[$topic])) {
                continue;
            }
            $map[$topic] = (int)($row['id'] ?? 0);
        }
        $taskMap = [];
        foreach ($taskRows as $row) {
            $pid = array_key_exists('persona_id', $row)
                ? (int)$row['persona_id']
                : self::personaIdFromJson($row['persona_json'] ?? []);
            if ($personaId > 0 && $pid !== $personaId) {
                continue;
            }
            $taskTopic = self::normalizeTopic($row['topic'] ?? '');
            if ($taskTopic !== '') {
                $taskMap[$taskTopic] = true;
            }
        }
        $poolMap = [];
        foreach ($poolRows as $row) {
            if ($personaId > 0 && (int)($row['persona_id'] ?? 0) !== $personaId) {
                continue;
            }
            $poolTopic = self::normalizeTopic($row['topic'] ?? '');
            if ($poolTopic !== '') {
                $poolMap[$poolTopic] = true;
            }
        }
        return ['analysis' => $map, 'task' => $taskMap, 'pool' => $poolMap];
    }

    public static function normalizeTopic(mixed $topic): string
    {
        $topic = trim((string)$topic);
        if ($topic === '') {
            return '';
        }
        return mb_substr($topic, 0, 120);
    }

    public static function attachTask(array $taskApi): void
    {
        try {
            $taskNo = (string)($taskApi['id'] ?? '');
            if ($taskNo === '') {
                HotspotLog::write('创作记录挂任务跳过：任务号为空');
                return;
            }
            $userId = (int)($taskApi['user_id'] ?? 0);
            if ($userId <= 0) {
                HotspotLog::write('创作记录挂任务跳过：用户为空 任务号=' . $taskNo);
                return;
            }
            $topic = (string)($taskApi['topic'] ?? '');
            $platform = (string)($taskApi['platform'] ?? '');
            $persona = self::asArray($taskApi['persona'] ?? []);
            $personaName = (string)($persona['name'] ?? '');
            $personaId = self::resolvePersonaId($persona);
            $options = self::asArray($taskApi['options'] ?? []);
            $now = time();

            $row = HotspotCreation::where('task_no', '')
                ->where(self::creationLookupWhere($userId, $personaId, $topic, $platform))
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$row->isEmpty()) {
                $row->task_no = $taskNo;
                $row->status = 'video';
                $row->persona_id = $personaId;
                $row->update_time = $now;
                $row->save();
                HotspotLog::write('创作记录已挂任务：任务号=' . $taskNo . ' 记录号=' . (string)$row->record_no);
                return;
            }

            $script = (string)($taskApi['script'] ?? '');
            HotspotCreation::create([
                'record_no' => self::newRecordNo('CRT', 'hotspot_creation'),
                'user_id' => $userId,
                'topic' => $topic,
                'platform' => $platform,
                'persona_id' => $personaId,
                'persona_name' => $personaName,
                'goal' => (string)($options['goal'] ?? ''),
                'direction' => (string)($options['direction'] ?? ''),
                'material_mode' => (string)($options['material_mode'] ?? ''),
                'duration_sec' => (int)($options['duration_sec'] ?? 0),
                'video_type' => (string)($options['video_type'] ?? ''),
                'avatar' => (string)($options['avatar'] ?? ''),
                'title' => (string)($taskApi['title'] ?? $topic),
                'script' => $script,
                'word_count' => mb_strlen($script),
                'est_duration_sec' => (int)($options['duration_sec'] ?? 0),
                'hashtags_json' => self::asStringList($options['hashtags'] ?? []),
                'shots_json' => self::asStringList($options['shots'] ?? []),
                'task_no' => $taskNo,
                'status' => 'video',
                'create_time' => $now,
                'update_time' => $now,
            ]);
            HotspotLog::write('创作记录补插并挂任务：任务号=' . $taskNo);
        } catch (\Throwable $e) {
            HotspotLog::exception('创作记录挂任务失败', $e);
        }
    }

    public static function materialLabel(string $mode): string
    {
        return self::MATERIAL_LABELS[$mode] ?? '';
    }

    public static function resolveTaskStatus(string $creationStatus, string $taskNo, string $taskStatus): string
    {
        $taskStatus = trim($taskStatus);
        if (in_array($taskStatus, ['done', 'fail', 'running', 'wait'], true)) {
            return $taskStatus;
        }
        if ($creationStatus === 'video' || $taskNo !== '') {
            return 'running';
        }
        return '';
    }

    public static function buildRemark(string $status, string $taskStatus, string $taskError, string $materialMode): string
    {
        // 各状态备注都带上素材来源，已完成/失败的记录也能看出当时选的是纯人设素材还是AI找素材
        $label = self::materialLabel($materialMode);
        $suffix = $label !== '' ? '（' . $label . '）' : '';
        if ($taskStatus === 'fail') {
            return ($taskError !== '' ? $taskError : '视频合成失败') . $suffix;
        }
        if ($taskStatus === 'done') {
            return '成片已生成' . $suffix;
        }
        if ($status === 'video' || $taskStatus !== '') {
            return '排队合成中' . $suffix;
        }
        return $label !== '' ? $label : '—';
    }

    /** 「已分析」三个来源：人设契合记录、视频任务、热点库入库 */
    private static function emptyAnalyzedMap(): array
    {
        return ['analysis' => [], 'task' => [], 'pool' => []];
    }

    private static function loadAnalyzedMap(int $userId, string $platform, array $topics, int $personaId = 0): array
    {
        if ($userId <= 0) {
            return self::emptyAnalyzedMap();
        }

        $titles = [];
        foreach ($topics as $topic) {
            if (!is_array($topic)) {
                continue;
            }
            $title = self::normalizeTopic($topic['title'] ?? '');
            if ($title !== '') {
                $titles[$title] = true;
            }
        }
        if ($titles === []) {
            return self::emptyAnalyzedMap();
        }

        $titleList = array_keys($titles);
        // 全局热榜（persona_id=0）不按人设过滤：小程序热榜不传人设，分析/入库都绑真实人设
        $analysisQuery = HotspotAnalysis::where('user_id', $userId)
            ->where('platform', $platform)
            ->whereIn('topic', $titleList)
            ->field(['id', 'topic', 'persona_id'])
            ->order('id', 'desc');
        if ($personaId > 0) {
            $analysisQuery->where('persona_id', $personaId);
        }
        $rows = $analysisQuery->select();

        $analysisRows = [];
        foreach ($rows as $row) {
            $analysisRows[] = [
                'id' => (int)$row->id,
                'topic' => (string)($row->topic ?? ''),
                'persona_id' => (int)($row->persona_id ?? 0),
            ];
        }

        $taskRows = [];
        $taskModels = HotspotTask::where('user_id', $userId)
            ->where('platform', $platform)
            ->whereIn('topic', $titleList)
            ->field(['topic', 'persona_json'])
            ->select();
        foreach ($taskModels as $taskRow) {
            $taskRows[] = [
                'topic' => (string)($taskRow->topic ?? ''),
                'persona_id' => self::personaIdFromJson($taskRow->persona_json ?? []),
            ];
        }

        $poolQuery = HotspotPool::where('user_id', $userId)
            ->where('platform', $platform)
            ->whereIn('topic', $titleList)
            ->field(['topic', 'persona_id']);
        if ($personaId > 0) {
            $poolQuery->where('persona_id', $personaId);
        }
        $poolRows = [];
        foreach ($poolQuery->select() as $poolRow) {
            $poolRows[] = [
                'topic' => (string)($poolRow->topic ?? ''),
                'persona_id' => (int)($poolRow->persona_id ?? 0),
            ];
        }

        $marks = self::pickAnalyzedMaps($analysisRows, $taskRows, $personaId, $poolRows);
        HotspotLog::write(sprintf(
            '热榜回填已分析：用户=%d 人设id=%d 平台=%s 话题数=%d 分析命中=%d 任务命中=%d 入库命中=%d',
            $userId,
            $personaId,
            $platform,
            count($titles),
            count($marks['analysis']),
            count($marks['task']),
            count($marks['pool'])
        ));
        return $marks;
    }

    private static function newRecordNo(string $prefix, string $table): string
    {
        $model = $table === 'hotspot_analysis' ? new HotspotAnalysis() : new HotspotCreation();
        for ($i = 0; $i < 5; $i++) {
            $no = $prefix . '_' . strtoupper(bin2hex(random_bytes(5)));
            $exists = $model->where('record_no', $no)->findOrEmpty();
            if ($exists->isEmpty()) {
                return $no;
            }
        }
        return $prefix . '_' . strtoupper(bin2hex(random_bytes(5)));
    }

    private static function normalizeHooks(mixed $hooks): array
    {
        $out = [];
        if (!is_array($hooks)) {
            return $out;
        }
        foreach ($hooks as $h) {
            if (!is_array($h) || empty($h['label'])) {
                continue;
            }
            $out[] = [
                'label' => trim((string)$h['label']),
                'detail' => trim((string)($h['detail'] ?? '')),
            ];
        }
        return $out;
    }

    private static function normalizeRisks(mixed $risks): array
    {
        $out = [];
        if (!is_array($risks)) {
            return $out;
        }
        foreach ($risks as $r) {
            $r = trim((string)$r);
            if ($r !== '') {
                $out[] = $r;
            }
        }
        return $out;
    }

    private static function asStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }

    private static function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
