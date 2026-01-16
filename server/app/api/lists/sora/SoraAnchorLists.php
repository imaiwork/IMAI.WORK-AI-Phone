<?php

namespace app\api\lists\sora;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraAnchor;
use app\common\service\FileService;

class SoraAnchorLists extends BaseApiDataLists implements ListsSearchInterface
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
        if (isset($status) && $status != '') {
            $status              = explode(',', $status);
            $this->searchWhere[] = ['status', 'in', $status];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list                = SoraAnchor::where($this->searchWhere)
                                         ->order(['id' => 'desc'])
                                         ->limit($this->limitOffset, $this->limitLength)
                                         ->select()
                                         ->toArray();

        foreach ($list as &$item) {
            $item['anchor_url']     = FileService::getFileUrl($item['anchor_url']);
            $item['image_url']      = FileService::getFileUrl($item['image_url']);
            $item['pic']            = FileService::getFileUrl($item['pic']);
            $item['draw_image_url'] = FileService::getFileUrl($item['draw_image_url']);
        }
        return $list;
    }

    public function count(): int
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != '') {
            $status              = explode(',', $status);
            $this->searchWhere[] = ['status', 'in', $status];
        }
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SoraAnchor::where($this->searchWhere)->count();
    }
}


