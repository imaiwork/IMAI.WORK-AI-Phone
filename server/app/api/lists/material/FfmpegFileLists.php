<?php

namespace app\api\lists\material;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\material\FfmpegFile;

class FfmpegFileLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'status'],
            'like' => ['name'],
        ];
    }

    public function lists(): array
    {
        $type = $this->request->get('type', 0);
        $status = $this->request->get('status', -1);
        
        $where = [];
        
        // 基础条件：当前用户的数据
        $where[] = ['user_id', '=', $this->userId];
        
        // 按类型筛选
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }
        
        // 按状态筛选
        if ($status >= 0) {
            $where[] = ['status', '=', $status];
        }

        $list = FfmpegFile::where($where)
            ->order(['create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['type_text'] = FfmpegFile::getTypeText($item['type']);
            $item['status_text'] = FfmpegFile::getStatusText($item['status']);
        }

        return $list;
    }

    public function count(): int
    {
        $type = $this->request->get('type', 0);
        $status = $this->request->get('status', -1);
        
        $where = [];
        
        // 基础条件：当前用户的数据
        $where[] = ['user_id', '=', $this->userId];
        
        // 按类型筛选
        if ($type > 0) {
            $where[] = ['type', '=', $type];
        }
        
        // 按状态筛选
        if ($status >= 0) {
            $where[] = ['status', '=', $status];
        }

        return FfmpegFile::where($where)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}