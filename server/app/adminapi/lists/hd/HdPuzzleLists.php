<?php

namespace app\adminapi\lists\hd;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;
use app\common\model\user\User;
use app\common\service\FileService;

class HdPuzzleLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['hp.puzzle_setting_id', 'hp.status', 'hp.type', 'hp.user_id'],
            '%like%' => ['hp.name', 'hp.task_id', 'hp.remark', 'u.nickname'],
        ];
    }

    public function lists(): array
    {
        $list = HdPuzzle::alias('hp')
            ->field('hp.*, u.nickname,u.avatar as user_avatar')
            ->join('user u', 'u.id = hp.user_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hp.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order(['hp.id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                // 处理JSON字段
                $jsonFields = ['title', 'material', 'puzzle_url', 'extra'];
                foreach ($jsonFields as $field) {
                    $item[$field] = !empty($item[$field]) ? json_decode($item[$field], true) : [];
                }
                $item['user_avatar']    = $item['user_avatar'] ? FileService::getFileUrl($item['user_avatar']) : '';

                if ($item['status'] == 1 && !empty($item['puzzle_url']) && is_array($item['puzzle_url'])){
                    $item['puzzle_url'] = array_map(function ($url) {
                        return FileService::getFileUrl($url);
                    }, $item['puzzle_url']);
                }
                // 处理状态文本
                $item['status_text'] = $this->getStatusText($item['status']);

                // 处理类型文本
                $item['type_text'] = $this->getTypeText($item['type']);
            })
            ->toArray();
        
        return $list;
    }

    public function count(): int
    {
        return HdPuzzle::alias('hp')
            ->join('user u', 'u.id = hp.user_id')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hp.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
    
    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    private function getStatusText(int $status): string
    {
        $statusMap = [
            0 => '待处理',
            1 => '成功',
            2 => '失败',
        ];
        
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取类型文本
     * @param int $type
     * @return string
     */
    private function getTypeText(int $type): string
    {
        $typeMap = [
            1 => '三张图',
            2 => '四张图',
            3 => '五张图',
            4 => '六张图',
            5 => '九张图',
        ];
        
        return $typeMap[$type] ?? '未知';
    }
}