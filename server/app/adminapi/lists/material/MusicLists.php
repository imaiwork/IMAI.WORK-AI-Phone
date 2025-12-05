<?php

namespace app\adminapi\lists\material;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\material\Music;
use app\common\service\FileService;

class MusicLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['style'],
            'like' => ['name'],
        ];
    }

    public function lists(): array
    {
        $list = Music::where($this->searchWhere)
            ->where('source',0)
            ->order(['create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['uri'] = $item['url'];
            $item['url'] =  $item['uri'] ? FileService::getFileUrl($item['uri']) : '';
            $item['source_text'] = Music::getSourceText($item['source']);
            $item['style_text'] = Music::getStyleText($item['style']);
        }

        return $list;
    }

    public function count(): int
    {
        return Music::where($this->searchWhere)->where('source',0)->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }
}
