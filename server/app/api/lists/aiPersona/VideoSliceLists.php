<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\api\logic\aiPersona\VideoSliceLogic;
use app\common\lists\ListsSearchInterface;
use app\model\VideoSlice;
use app\model\VideoSliceItem;
use think\db\BaseQuery;

class VideoSliceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['original_video_id'],
            '%like%' => ['original_name'],
        ];
    }

    public function lists(): array
    {
        $list = $this->baseQuery()
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $itemCountMap = $this->getItemCountMap(array_column($list, 'id'));
        foreach ($list as &$item) {
            $item['status_text'] = VideoSliceLogic::statusText((int)($item['status'] ?? 0));
            $item['created_at'] = (string)($item['created_at'] ?? '');
            $item['item_count'] = $itemCountMap[(int)$item['id']] ?? 0;
        }

        return $list;
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function baseQuery()
    {
        $where = $this->searchWhere;
        $where[] = ['user_id', '=', $this->userId];
        if ($this->request->get('status') !== null && $this->request->get('status') !== '') {
            $where[] = ['status', '=', (int)$this->request->get('status')];
        }
        $personaId = (int)$this->request->get('persona_id', 0);

        return VideoSlice::where($where)
            ->when($personaId > 0, function ($query) use ($personaId) {
                $query->where(function ($q) use ($personaId) {
                    $q->where('persona_id', $personaId)
                        ->whereOr(function ($q2) use ($personaId) {
                            $this->wherePersonaOriginalVideo($q2, $personaId);
                        });
                });
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetweenTime('created_at', $this->request->get('start_time'), $this->request->get('end_time'));
            })
            ->when($this->request->get('start_time') && !$this->request->get('end_time'), function ($query) {
                $query->where('created_at', '>=', $this->request->get('start_time'));
            })
            ->when(!$this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->where('created_at', '<=', $this->request->get('end_time'));
            });
    }

    private function wherePersonaOriginalVideo(BaseQuery $query, int $personaId): void
    {
        $userId = $this->userId;
        $query->whereIn('original_video_id', function ($query) use ($userId, $personaId) {
            $query->name('ai_persona_material')
                ->where('user_id', $userId)
                ->where('persona_id', $personaId)
                ->where('source_video_id', '>', 0)
                ->field('DISTINCT source_video_id');
        });
    }

    private function getItemCountMap(array $sliceIds): array
    {
        $sliceIds = array_values(array_filter(array_map('intval', $sliceIds)));
        if (empty($sliceIds)) {
            return [];
        }

        $rows = VideoSliceItem::whereIn('slice_id', $sliceIds)
            ->field('slice_id, COUNT(id) AS item_count')
            ->group('slice_id')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['slice_id']] = (int)$row['item_count'];
        }

        return $map;
    }
}
