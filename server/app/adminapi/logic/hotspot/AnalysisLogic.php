<?php

namespace app\adminapi\logic\hotspot;

use app\common\logic\BaseLogic;
use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\user\User;
use app\common\service\hotspot\HotspotLog;

class AnalysisLogic extends BaseLogic
{
    public static function detail(int $id): array|false
    {
        $row = HotspotAnalysis::where('id', $id)->findOrEmpty();
        if ($row->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        $persona = is_array($row->persona_json) ? $row->persona_json : [];
        $nickname = '';
        if ((int)$row->user_id > 0) {
            $nickname = (string)User::where('id', (int)$row->user_id)->value('nickname');
        }
        return [
            'id' => (int)$row->id,
            'record_no' => (string)$row->record_no,
            'user' => $nickname !== '' ? $nickname : '体验用户',
            'topic' => (string)$row->topic,
            'platform' => (string)$row->platform,
            'persona' => [
                'id' => (string)($persona['id'] ?? ''),
                'name' => (string)($persona['name'] ?? ''),
                'tag' => (string)($persona['tag'] ?? ''),
                'avatar' => (string)($persona['avatar'] ?? ''),
            ],
            'fit_score' => (int)$row->fit_score,
            'fit_reason' => (string)$row->fit_reason,
            'hooks' => is_array($row->hooks_json) ? $row->hooks_json : [],
            'risks' => is_array($row->risks_json) ? $row->risks_json : [],
            'recommended_goal' => (string)$row->recommended_goal,
            'recommended_direction' => (string)$row->recommended_direction,
            'create_time' => (string)$row->create_time,
        ];
    }

    public static function delete(mixed $id): bool
    {
        $ids = self::normalizeIds($id);
        if ($ids === []) {
            self::setError('id参数缺失');
            return false;
        }
        $rows = HotspotAnalysis::whereIn('id', $ids)->select();
        if ($rows->isEmpty()) {
            self::setError('记录不存在');
            return false;
        }
        foreach ($rows as $row) {
            $row->delete();
        }
        HotspotLog::write('后台删除分析记录：数量=' . count($rows));
        return true;
    }

    private static function normalizeIds(mixed $id): array
    {
        if (is_array($id)) {
            $raw = $id;
        } elseif ($id === '' || $id === null) {
            $raw = [];
        } else {
            $raw = [$id];
        }
        $out = [];
        foreach ($raw as $item) {
            $item = (int)$item;
            if ($item > 0) {
                $out[] = $item;
            }
        }
        return array_values(array_unique($out));
    }
}
