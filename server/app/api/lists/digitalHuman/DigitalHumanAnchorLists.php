<?php

namespace app\api\lists\digitalHuman;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\digitalHuman\DigitalHumanAnchor;
use app\common\model\human\HumanAnchor;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\service\FileService;

class DigitalHumanAnchorLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $this->searchWhere[] = ['status', 'in', explode(',',$status)];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list                = DigitalHumanAnchor::where($this->searchWhere)
                                                 ->order(['id' => 'desc'])
                                                 ->limit($this->limitOffset, $this->limitLength)
                                                 ->select()
                                                 ->toArray();

        foreach ($list as &$item) {
            $item['pic']         = !empty($item['image']) ? FileService::getFileUrl($item['image']) : '';
            $item['result_url']  = !empty($item['result_url']) ? FileService::getFileUrl($item['result_url']) : '';
            $item['create_time'] = !empty($item['create_time']) ? $item['create_time'] : '';
            $item['update_time'] = !empty($item['update_time']) ? $item['update_time'] : '';
            $weiju              = HumanAnchor::where('model_version', '=', 1)->where('dh_id', '=', $item['id'])->find();
            $chanjing           = HumanAnchor::where('model_version', '=', 7)->where('dh_id', '=', $item['id'])->find();
            $shanjian           = ShanjianAnchor::where('dh_id', '=', $item['id'])->find();
            $item['anchor_ids'] = [
                'weiju_anchor_id'    => $weiju->anchor_id ?? '',
                'chanjing_anchor_id' => $chanjing->anchor_id ?? '',
                'shanjian_anchor_id' => $shanjian->anchor_id ?? '',
            ];
            $item['extra_info'] = [
                'width'  => $weiju->width ?? ($chanjing->width ?? ''),
                'height' => $weiju->height ?? ($chanjing->width ?? ''),
                'shanjian_voice_id' => $shanjian->voice_id ?? '',
            ];
        }
        return $list;
    }

    public function count(): int
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != ''){
            $this->searchWhere[] = ['status', 'in', explode(',',$status)];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return DigitalHumanAnchor::where($this->searchWhere)->count();
    }
}


