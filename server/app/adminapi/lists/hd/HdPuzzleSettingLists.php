<?php

namespace app\adminapi\lists\hd;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdPuzzleSetting;
use app\common\model\hd\HdPuzzle;
use app\common\model\user\User;
use app\common\service\FileService;

class HdPuzzleSettingLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '%like%' => ['hps.name', 'u.nickname'],
            'in' => ['hps.status'],
            '=' => ['hps.user_id'],
        ];
    }

    public function lists(): array
    {
        $list = HdPuzzleSetting::alias('hps')
            ->join('user u', 'u.id = hps.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('hps.*, u.nickname,u.avatar as user_avatar')
            ->order(['hps.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                // 处理JSON字段
                $jsonFields = ['copywriting', 'material', 'extra'];
                foreach ($jsonFields as $field) {
                    $item[$field] = !empty($item[$field]) ? json_decode($item[$field], true) : [];
                }
                $item['user_avatar']    = $item['user_avatar'] ? FileService::getFileUrl($item['user_avatar']) : '';

                $item['copywriting_num'] = count($item['copywriting']);
                $item['material_num'] = count($item['material']);
                $item['puzzle_token'] = HdPuzzle::where('puzzle_setting_id', $item['id'])->sum('img_token') ?? 0;
                // 处理状态文本
                $item['status_text'] = HdPuzzleSetting::getStatusText($item['status']);
            })
            ->toArray();
        
        return $list;
    }

    public function count(): int
    {
        return HdPuzzleSetting::alias('hps')
            ->join('user u', 'u.id = hps.user_id')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}