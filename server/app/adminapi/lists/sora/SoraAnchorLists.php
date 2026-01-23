<?php

namespace app\adminapi\lists\sora;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sora\SoraAnchor;
use app\common\model\user\User;
use app\common\service\FileService;

class SoraAnchorLists extends BaseAdminDataLists implements ListsSearchInterface
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
        $list = SoraAnchor::where($this->searchWhere)
                          ->order(['id' => 'desc'])
                          ->limit($this->limitOffset, $this->limitLength)
                          ->select()
                          ->each(function ($item) {
                              $item['nickname']       = User::where(['id' => $item['user_id']])->value('nickname');
                              $item['anchor_url']     = FileService::getFileUrl($item['anchor_url']);
                              $item['draw_image_url'] = FileService::getFileUrl($item['draw_image_url']);
                              $item['image_url']      = FileService::getFileUrl($item['image_url']);
                              $item['pic']            = FileService::getFileUrl($item['pic']);
                          })
                          ->toArray();
        return $list;
    }

    public function count(): int
    {
        $status = $this->request->get('status');
        if (isset($status) && $status != '') {
            $status              = explode(',', $status);
            $this->searchWhere[] = ['status', 'in', $status];
        }
        return SoraAnchor::where($this->searchWhere)->count();
    }
}


