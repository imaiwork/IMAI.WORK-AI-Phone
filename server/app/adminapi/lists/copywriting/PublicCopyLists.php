<?php

namespace app\adminapi\lists\copywriting;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\copywriting\PublicCopywriting;
use app\common\service\copywriting\PublicCopywritingService;

class PublicCopyLists extends BaseAdminDataLists implements ListsExtendInterface
{
    private function where(): array
    {
        $where = [];
        $uiType = (int)($this->params['ui_type'] ?? 0);
        $libraryType = (int)($this->params['library_type'] ?? 0);
        $driverType = array_key_exists('driver_type', $this->params) ? (int)$this->params['driver_type'] : -1;
        if ($uiType > 0) {
            $where[] = ['ui_type', '=', $uiType];
        }
        if ($libraryType > 0) {
            $where[] = ['library_type', '=', $libraryType];
            if ($driverType >= 0) {
                $where[] = ['driver_type', '=', $driverType];
            }
        }
        return $where;
    }

    public function lists(): array
    {
        $query = PublicCopywriting::order(['sort' => 'asc', 'id' => 'desc']);
        if ($this->where()) {
            $query->where($this->where());
        }
        $rows = $query->limit($this->limitOffset, $this->limitLength)->select()->toArray();
        return array_map([PublicCopywritingService::class, 'formatTemplate'], $rows);
    }

    public function count(): int
    {
        $query = new PublicCopywriting();
        if ($this->where()) {
            $query = $query->where($this->where());
        }
        return $query->count();
    }

    public function extend(): array
    {
        return PublicCopywritingService::countByUiType();
    }
}
