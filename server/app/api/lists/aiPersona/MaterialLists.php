<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;
use think\facade\Cache as FacadeCache;

class MaterialLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            // material_type 支持单值或多值：1 / 1,2 / [1,2]
            '=' => ['persona_id', 'use_status', 'publish_mode', 'is_wechat', 'slice_status'],
            'in' => ['material_type'],
            'like' => ['material_name'],
        ];
    }

    public function lists(): array
    {
        return $this->baseQuery()
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                $item->use_num =    MaterialUseLog::where('material_id', $item->id)->where('use_status', 1)->count();
                $item->slice_status_text = Material::getSliceStatusText((int)($item->slice_status ?? 0));
            })
            ->toArray();
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    private function baseQuery()
    {
        $where = $this->searchWhere;
        $where[] = ['user_id', '=', $this->userId];

        // 默认：可用素材（非切割 或 切割成功）+ 切割失败待清理（use_status=2 & slice_status=4）
        $hasUseStatusFilter = false;
        $hasSliceStatusFilter = false;
        foreach ($where as $item) {
            if (!is_array($item) || count($item) < 1) {
                continue;
            }
            $field = (string)$item[0];
            if ($field === 'use_status') {
                $hasUseStatusFilter = true;
            }
            if ($field === 'slice_status') {
                $hasSliceStatusFilter = true;
            }
        }

        // material_type 的 in 条件统一为整型数组，兼容 "1,2" / [1,2] / 1
        foreach ($where as $idx => $item) {
            if (!is_array($item) || ($item[0] ?? '') !== 'material_type' || ($item[1] ?? '') !== 'in') {
                continue;
            }
            $raw = $item[2] ?? [];
            if (is_string($raw)) {
                $raw = explode(',', $raw);
            }
            if (!is_array($raw)) {
                $raw = [$raw];
            }
            $types = array_values(array_unique(array_filter(array_map('intval', $raw))));
            if ($types) {
                $where[$idx][2] = $types;
            } else {
                unset($where[$idx]);
            }
        }
        $where = array_values($where);

        $query = Material::where($where);

        if ($hasUseStatusFilter || $hasSliceStatusFilter) {
            return $query;
        }

        return $query->where(function ($q) {
            $q->where(function ($ok) {
                $ok->where('use_status', Material::USE_STATUS_ENABLED)
                    ->where(function ($src) {
                        $src->where('source_type', '<>', 'slice')
                            ->whereOr('slice_status', Material::SLICE_STATUS_SUCCESS);
                    });
            })->whereOr(function ($fail) {
                $fail->where('use_status', Material::USE_STATUS_DISABLED)
                    ->where('slice_status', Material::SLICE_STATUS_FAILED)
                    ->where('source_type', 'original');
            });
        });
    }
}
