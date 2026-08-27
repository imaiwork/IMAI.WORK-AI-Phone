<?php

namespace app\common\service\hotspot;

use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\hotspot\HotspotCreation;

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
                'update_time' => $now,
            ];

            // 同一流程内反复「重新生成文案」更新同一条台账（仅文案且未关联任务的最新记录），
            // 避免后台创作记录膨胀出大量重复行，与小程序侧的创作感知保持一致
            $existing = HotspotCreation::where('user_id', $userId)
                ->where('topic', $topic)
                ->where('platform', $platform)
                ->where('persona_name', $personaName)
                ->where('status', 'script')
                ->where('task_no', '')
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$existing->isEmpty()) {
                $existing->save($data);
                HotspotLog::write(sprintf(
                    '创作记录更新成功：用户=%d 话题=%s 标题=%s 编号=%s',
                    $userId,
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

    public static function attachAnalyzed(array $topics, int $userId, string $platform): array
    {
        if ($topics === []) {
            return [];
        }

        $marks = [];
        try {
            $marks = self::loadAnalyzedMap($userId, $platform, $topics);
        } catch (\Throwable $e) {
            HotspotLog::exception('热榜回填已分析失败', $e);
            $marks = [];
        }

        $out = [];
        foreach ($topics as $topic) {
            if (!is_array($topic)) {
                continue;
            }
            $title = self::normalizeTopic($topic['title'] ?? '');
            $analysisId = (int)($marks[$title] ?? 0);
            $topic['analyzed'] = $analysisId > 0;
            $topic['analysis_id'] = $analysisId;
            $out[] = $topic;
        }
        return $out;
    }

    public static function resolvePersonaId(array $persona): int
    {
        return max(0, (int)($persona['id'] ?? 0));
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
            $options = self::asArray($taskApi['options'] ?? []);
            $now = time();

            $row = HotspotCreation::where('task_no', '')
                ->where('user_id', $userId)
                ->where('topic', $topic)
                ->where('platform', $platform)
                ->where('persona_name', $personaName)
                ->order('id', 'desc')
                ->findOrEmpty();
            if (!$row->isEmpty()) {
                $row->task_no = $taskNo;
                $row->status = 'video';
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

    private static function loadAnalyzedMap(int $userId, string $platform, array $topics): array
    {
        if ($userId <= 0) {
            return [];
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
            return [];
        }

        $rows = HotspotAnalysis::where('user_id', $userId)
            ->where('platform', $platform)
            ->whereIn('topic', array_keys($titles))
            ->field(['id', 'topic'])
            ->order('id', 'desc')
            ->select();

        $map = [];
        foreach ($rows as $row) {
            $topic = self::normalizeTopic($row->topic ?? '');
            if ($topic === '' || isset($map[$topic])) {
                continue;
            }
            $map[$topic] = (int)$row->id;
        }

        HotspotLog::write(sprintf(
            '热榜回填已分析：用户=%d 平台=%s 话题数=%d 命中=%d',
            $userId,
            $platform,
            count($titles),
            count($map)
        ));
        return $map;
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
