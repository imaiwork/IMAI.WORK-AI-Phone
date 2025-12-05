<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCopywritingLibrary;

/**
 * 文案库列表
 * Class SvCopywritingLibraryLists
 */
class SvCopywritingLibraryLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['user_id', 'type', 'copywriting_type'],
            '%like%' => ['name'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvCopywritingLibrary::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理JSON字段
        foreach ($list as &$item) {
            $jsonFields = ['title', 'described', 'oral_copy', 'extra'];
            foreach ($jsonFields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = json_decode($item[$field], true);
                } else {
                    $item[$field] = [];
                }
            }
        }
        return $list;
    }

    public function count(): int
    {
        return SvCopywritingLibrary::where($this->searchWhere)->count();
    }
} 