<?php

namespace app\api\lists\aiPersona;

use app\api\lists\BaseApiDataLists;
use app\api\logic\aiPersona\MaterialGroupLogic;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\aiPersona\AiPersona;
use app\common\model\aiPersona\Material;
use app\common\model\aiPersona\MaterialUseLog;

class MaterialLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    public function setSearch(): array
    {
        return [
            // material_type 支持单值或多值：1 / 1,2 / [1,2]
            '=' => ['persona_id', 'use_status', 'publish_mode', 'is_wechat', 'slice_status', 'source_type', 'keep_original'],
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
                $item->use_num = MaterialUseLog::alias('log')
                    ->leftJoin('shanjian_video_task task', 'task.id = log.task_id')
                    ->where('log.material_id', $item->id)
                    ->where('log.use_status', 1)
                    // 与素材详情的生成记录同口径：热点追踪等手动发起的任务(auto_type=0)不计次。
                    // 用左连接是因为成品直发库的使用记录没有 task 行，内连接会把它们一起抹掉
                    ->where(function ($query) {
                        $query->whereNull('task.id')
                            ->whereOr('task.auto_type', '=', MaterialUseLogLists::AUTO_TASK);
                    })
                    ->count();
                $item->slice_status_text = Material::getSliceStatusText((int)($item->slice_status ?? 0));
                $item->source_type_text = Material::getSourceTypeText(
                    (string)($item->source_type ?? ''),
                    (int)($item->keep_original ?? 0)
                );
            })
            ->toArray();
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    public function extend(): array
    {
        $personaId = (int)($this->params['persona_id'] ?? 0);
        if ($personaId <= 0) {
            return [
                'material_use_mode' => ShanjianVideoSettingLogic::MATERIAL_USE_MODE_SLICE,
            ];
        }
        $owned = AiPersona::where('id', $personaId)
            ->where('user_id', $this->userId)
            ->findOrEmpty();
        if ($owned->isEmpty()) {
            return [
                'material_use_mode' => ShanjianVideoSettingLogic::MATERIAL_USE_MODE_SLICE,
            ];
        }
        return [
            'material_use_mode' => ShanjianVideoSettingLogic::resolvePersonaMaterialUseMode($personaId),
        ];
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
        $this->applyGroupFilter($query);

        if ($hasUseStatusFilter || $hasSliceStatusFilter) {
            return $query;
        }

        // 默认可见范围收在 Material::applyDefaultVisible，配置清单的素材计数复用同一份条件
        return Material::applyDefaultVisible($query);
    }

    /**
     * 不传 group_id = 全部。0 = 未分组。大于 0 必须是当前用户这个人设的分组。
     */
    private function applyGroupFilter($query): void
    {
        if (!array_key_exists('group_id', $this->params) || $this->params['group_id'] === '' || $this->params['group_id'] === null) {
            return;
        }
        $groupId = (int)$this->params['group_id'];
        $personaId = (int)($this->params['persona_id'] ?? 0);
        $keepOriginal = null;
        if (array_key_exists('keep_original', $this->params)
            && $this->params['keep_original'] !== ''
            && $this->params['keep_original'] !== null
        ) {
            $keepOriginal = (int)$this->params['keep_original'] === 1 ? 1 : 0;
        }
        if ($groupId > 0 && !MaterialGroupLogic::owns($this->userId, $personaId, $groupId, $keepOriginal)) {
            $query->where('id', 0);
            return;
        }
        $query->where('group_id', max(0, $groupId));
    }
}
