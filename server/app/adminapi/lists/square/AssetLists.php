<?php

namespace app\adminapi\lists\square;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\model\square\SquareAsset;
use app\common\model\square\SquareAssetGroup;
use app\common\service\square\SquareAssetService;

class AssetLists extends BaseAdminDataLists implements ListsExtendInterface
{
    private function where(): array
    {
        $where = [];
        $groupId = (int)($this->params['group_id'] ?? 0);
        $kind = (int)($this->params['kind'] ?? 0);
        if ($groupId > 0) {
            $where[] = ['group_id', '=', $groupId];
        }
        if ($kind > 0) {
            $where[] = ['kind', '=', $kind];
        }
        return $where;
    }

    public function lists(): array
    {
        $query = SquareAsset::order(['sort' => 'asc', 'id' => 'desc']);
        if ($this->where()) {
            $query->where($this->where());
        }
        $assets = $query->limit($this->limitOffset, $this->limitLength)->select()->toArray();
        if (!$assets) {
            return [];
        }

        $groupNames = SquareAssetGroup::whereIn('id', array_unique(array_column($assets, 'group_id')))
            ->column('name', 'id');
        return array_map(
            fn ($asset) => SquareAssetService::formatAsset($asset, $groupNames[$asset['group_id']] ?? ''),
            $assets
        );
    }

    public function count(): int
    {
        $query = new SquareAsset();
        if ($this->where()) {
            $query = $query->where($this->where());
        }
        return $query->count();
    }

    public function extend(): array
    {
        return [
            'asset_total' => SquareAssetService::countAllAssets(),
        ];
    }
}
