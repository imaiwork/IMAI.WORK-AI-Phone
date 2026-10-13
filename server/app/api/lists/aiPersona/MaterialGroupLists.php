<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\api\logic\aiPersona\MaterialGroupLogic;
use app\common\lists\ListsExtendInterface;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\MaterialGroup;

class MaterialGroupLists extends BaseApiDataLists implements ListsExtendInterface
{
    public function lists(): array
    {
        $personaId = $this->personaId();
        if ($personaId <= 0) {
            return [];
        }
        $list = $this->applyKind(MaterialGroup::where('user_id', $this->userId)
            ->where('persona_id', $personaId))
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        foreach ($list as $key => $row) {
            $list[$key]['material_count'] = MaterialGroupLogic::countBase($this->userId, $personaId, $this->params)
                ->where('group_id', (int)$row['id'])
                ->count();
        }
        return $list;
    }

    public function count(): int
    {
        $personaId = $this->personaId();
        if ($personaId <= 0) {
            return 0;
        }
        return $this->applyKind(MaterialGroup::where('user_id', $this->userId)
            ->where('persona_id', $personaId))
            ->count();
    }

    public function extend(): array
    {
        $personaId = $this->personaId();
        if ($personaId <= 0) {
            return ['ungrouped_count' => 0];
        }
        return [
            'ungrouped_count' => MaterialGroupLogic::countBase($this->userId, $personaId, $this->params)
                ->where('group_id', 0)
                ->count(),
        ];
    }

    private function applyKind($query)
    {
        if (!array_key_exists('keep_original', $this->params)
            || $this->params['keep_original'] === ''
            || $this->params['keep_original'] === null
        ) {
            return $query;
        }
        return $query->where(
            'keep_original',
            (int)$this->params['keep_original'] === 1 ? 1 : 0
        );
    }

    private function personaId(): int
    {
        $personaId = (int)($this->params['persona_id'] ?? 0);
        if ($personaId <= 0) {
            return 0;
        }
        $owned = AiPersona::where('id', $personaId)
            ->where('user_id', $this->userId)
            ->findOrEmpty();
        return $owned->isEmpty() ? 0 : $personaId;
    }
}
