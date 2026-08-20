<?php

namespace app\api\lists\map;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\map\MapLeadConversation;

class MapLeadConversationLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='            => ['status'],
            'between_time' => 'create_time',
        ];
    }

    public function lists(): array
    {
        return $this->baseQuery()
            ->field('id,conversation_id,title,last_content,lead_count,status,fail_reason,create_time,update_time')
            ->order('update_time', 'desc')
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function baseQuery()
    {
        $keyword = trim((string)$this->request->get('keyword', ''));

        return MapLeadConversation::where('user_id', $this->userId)
            ->where($this->searchWhere)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->whereOr('last_content', 'like', '%' . $keyword . '%');
                });
            });
    }
}

