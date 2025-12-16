<?php

namespace app\api\lists\hd;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\model\hd\HdPuzzle;
use app\common\service\FileService;

/**
 * 拼图任务列表
 */
class HdPuzzleLists extends BaseApiDataLists implements ListsSearchInterface, ListsSortInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'type', 'puzzle_setting_id'],
            '%like%' => ['name'],
            'between' => ['create_time'],
        ];
    }

    public function setSortFields(): array
    {
        return ['create_time' => 'create_time'];
    }

    public function setDefaultOrder(): array
    {
        return ['create_time' => 'desc'];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = HdPuzzle::where($this->searchWhere)
            ->order($this->sortOrder)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $jsonFields = ['title', 'material', 'extra', 'puzzle_url'];
            foreach ($jsonFields as $field) {
                $item[$field] = !empty($item[$field]) ? json_decode($item[$field], true) : [];
            }

            // 若素材里有文件路径，追加访问地址
            if (!empty($item['material']) && is_array($item['material'])) {
                $item['material'] = array_map(function ($url) {
                    return FileService::getFileUrl($url);
                }, $item['material']);
            }

            if ($item['status'] == 1 && !empty($item['puzzle_url']) && is_array($item['puzzle_url'])){
                $item['puzzle_url'] = array_map(function ($url) {
                    return FileService::getFileUrl($url);
                }, $item['puzzle_url']);
            }
        }

        return $list;
    }

    public function count(): int
    {
        return HdPuzzle::where($this->searchWhere)->count();
    }
}

