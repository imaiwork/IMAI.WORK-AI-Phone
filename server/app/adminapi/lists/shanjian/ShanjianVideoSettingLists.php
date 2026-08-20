<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoSetting;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\user\User;

class ShanjianVideoSettingLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['sj.name','u.nickname'],
            'in' => ['sj.status'],
            'in' => ['sj.shanjian_type'],
        ];
    }

    public function lists(): array
    {
        $list = ShanjianVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('sj.*,u.nickname')
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                $item['video_token'] = ShanjianVideoTask::where('video_setting_id', $item->id)->where('status',3)->sum('video_token');
                if ($item['video_token'] > 0) {
                    $item['video_token'] = sprintf('%.2f',   $item['video_token']);
                }
                switch ($item['shanjian_type']) {
                    case 1:
                        $item['shanjian_type_name'] = '数字人口播混剪';
                        break;
                    case 2:
                        $item['shanjian_type_name'] = '真人口播混剪';

                        $oral_video = $this->jsonToArray($item['anchor'] ?? null);
                        $item['oral_video_num'] = count($oral_video);
                        $material = $this->jsonToArray($item['material'] ?? null);
                        $item['material_num'] = count($material);
                        break;
                    case 3:
                    case 4:
                        $item['shanjian_type_name'] = $item['shanjian_type'] ==3 ? '素材混剪':'新闻体混剪';
                        $material_array = $this->jsonToArray($item['material'] ?? null);
                        $item['material_array_num'] = count($material_array);
                        $material_count = 0;

                        // 遍历数组
                        foreach ($material_array as $group) {
                            $material_count += is_array($group) ? count($group) : 1;
                        }
                        $item['material_num'] = $material_count;
                        $item['copywriting_num'] = 0;
                        if (($item['copywriting'] ?? '') !== '') {
                            $copywriting = json_decode((string)$item['copywriting'], true);
                            if (is_array($copywriting) && json_last_error() === JSON_ERROR_NONE) {
                                $item['copywriting_num'] = count($copywriting);
                            } else {
                                $item['copywriting_num'] = 1;
                            }
                        }
                        break;
                    default:
                        $item['shanjian_type_name'] = '数字人口播混剪';
                        break;
                }

                
            })
            ->toArray();
        return $list;
    }

    private function jsonToArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $data = json_decode((string)$value, true);
        return is_array($data) ? $data : [];
    }

    public function count(): int
    {
        return ShanjianVideoSetting::alias('sj')
            ->join('user u', 'u.id = sj.user_id')  ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })->where($this->searchWhere)->count();
    }
}
