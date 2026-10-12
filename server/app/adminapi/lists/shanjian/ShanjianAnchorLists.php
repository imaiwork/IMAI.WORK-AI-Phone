<?php

namespace app\adminapi\lists\shanjian;

use app\adminapi\lists\BaseAdminDataLists;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianAnchor;
use app\common\model\user\User;
use app\common\service\digitalHuman\PendingAnchorTaskService;

class ShanjianAnchorLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['sj.name','u.nickname'],
            'in' => ['sj.status'],
        ];
    }

    public function lists(): array
    {
        PendingAnchorTaskService::materialize('shanjian');
        $list = ShanjianAnchorLogic::applyCloneModeFilter(
            ShanjianAnchor::alias('sj')
                ->join('user u', 'u.id = sj.user_id')
                ->where($this->searchWhere)
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                ->field('sj.*,u.nickname'),
            'sj'
        )
            ->order(['sj.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
        // 系统默认形象存的是本地静态相对路径(static/videos/...)，展示时补全域名
        foreach ($list as &$item) {
            $item['anchor_url'] = \app\common\service\FileService::getFileUrl((string)($item['anchor_url'] ?? ''));
            $item['authorized_url'] = \app\common\service\FileService::getFileUrl((string)($item['authorized_url'] ?? ''));
        }
        unset($item);

        return $list;
    }

    public function count(): int
    {
        PendingAnchorTaskService::materialize('shanjian');
        return ShanjianAnchorLogic::applyCloneModeFilter(
            ShanjianAnchor::alias('sj')
                ->join('user u', 'u.id = sj.user_id')
                ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                    $query->whereBetween('sj.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
                })
                ->where($this->searchWhere),
            'sj'
        )->count();
    }
}
