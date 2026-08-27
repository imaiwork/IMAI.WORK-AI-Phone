<?php

namespace app\adminapi\lists\hotspot;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hotspot\HotspotAnalysis;
use app\common\model\user\User;

class AnalysisLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    public function lists(): array
    {
        $query = $this->buildQuery();
        $rows = $query->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        if ($rows === []) {
            return [];
        }

        $userMap = User::whereIn('id', array_column($rows, 'user_id'))->column('nickname', 'id');
        $out = [];
        foreach ($rows as $row) {
            $persona = is_array($row['persona_json'] ?? null) ? $row['persona_json'] : [];
            $out[] = [
                'id' => (int)$row['id'],
                'record_no' => (string)($row['record_no'] ?? ''),
                'user' => $this->displayUser((int)($row['user_id'] ?? 0), $userMap),
                'topic' => (string)($row['topic'] ?? ''),
                'platform' => (string)($row['platform'] ?? ''),
                'persona' => [
                    'id' => (string)($persona['id'] ?? ''),
                    'name' => (string)($persona['name'] ?? ''),
                    'tag' => (string)($persona['tag'] ?? ''),
                    'avatar' => (string)($persona['avatar'] ?? ''),
                ],
                'fit_score' => (int)($row['fit_score'] ?? 0),
                'recommended_goal' => (string)($row['recommended_goal'] ?? ''),
                'create_time' => (string)($row['create_time'] ?? ''),
            ];
        }
        return $out;
    }

    public function count(): int
    {
        return $this->buildQuery()->count();
    }

    private function buildQuery()
    {
        $query = HotspotAnalysis::field([
            'id', 'record_no', 'user_id', 'topic', 'platform', 'persona_json',
            'fit_score', 'recommended_goal', 'create_time',
        ]);

        $platform = trim((string)($this->params['platform'] ?? ''));
        if ($platform !== '') {
            $query->where('platform', $platform);
        }

        $keyword = trim((string)($this->params['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->whereLike('topic', '%' . $keyword . '%');
        }

        $user = trim((string)($this->params['user'] ?? ''));
        if ($user !== '') {
            $userIds = User::where('mobile|nickname', 'like', '%' . $user . '%')->column('id');
            if ($userIds === []) {
                $query->where('user_id', -1);
            } else {
                $query->whereIn('user_id', $userIds);
            }
        }

        $startTime = trim((string)($this->params['start_time'] ?? ''));
        $endTime = trim((string)($this->params['end_time'] ?? ''));
        if ($startTime !== '' && $endTime !== '') {
            $query->whereBetween('create_time', [strtotime($startTime), strtotime($endTime)]);
        }

        return $query;
    }

    private function displayUser(int $userId, array $userMap): string
    {
        $name = trim((string)($userMap[$userId] ?? ''));
        return $name !== '' ? $name : '体验用户';
    }
}
