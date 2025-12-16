<?php

namespace app\api\lists\hd;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdPuzzle;
use app\common\model\hd\HdPuzzleSetting;
use app\common\service\FileService;

/**
 * 拼图设置列表
 */
class HdPuzzleSettingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
            '%like%' => ['name']
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = HdPuzzleSetting::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $puzzle_urlArr = [];    
        foreach ($list as &$item) {

            $jsonFields = ['copywriting', 'material', 'extra','puzzle_url'];
            foreach ($jsonFields as $field) {
                $item[$field] = !empty($item[$field]) ? json_decode($item[$field], true) : [];
            }
        
            $puzzle_url = HdPuzzle::where('puzzle_setting_id', $item['id'])->where('status', 1)->column('puzzle_url') ?? [];
            $item['puzzle_url'] = [];

            foreach ($puzzle_url as &$url) {
                // $item[$field] = !empty($item[$field]) ? json_decode($item[$field], true) : [];
                $url = !empty($url) ? json_decode($url, true) : [];
                $num = count($url);
                if ($num > 0) {
                    foreach ($url as &$u) {
                        if (in_array($u, $puzzle_urlArr)) {
                            continue;
                        }
                           $puzzle_urlArr[] = $u;
                        $item['puzzle_url'][]  = FileService::getFileUrl($u);
                    }
                }
            };
        }

        return $list;
    }

    public function count(): int
    {
        return HdPuzzleSetting::where($this->searchWhere)->count();
    }
}

