<?php

namespace app\api\lists\material;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\material\Music;
use app\common\service\FileService;

class MusicLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['style'],
        ];
    }

    public function lists(): array
    {
        $style = $this->request->get('style', 0);
        $type = $this->request->get('type', 1);
        if ( $type != 0){
            if ($style == 0){
                $where = [
                    ['source_id', '=', $this->userId],
                    ['style', '=', 0],
                    ['source','=',1]
                ];
            }else{
                $where = [
                    ['style', '=', $style],
                    ['source','=',0]
                ];
            }
        }else{
            $where = [
                ['style', 'in', [1,2,3,4,5,6]],
                ['source','=',0]
            ];
        }



        $list = Music::where($where)
            ->order(['create_time' => 'desc'])
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['uri'] = $item['url'];
            $item['url'] = $item['url'] ? FileService::getFileUrl($item['url']) : '';
        }

        return $list;
    }

    public function count(): int
    {
        $style = $this->request->get('style', 0);
        $type = $this->request->get('type', 1);
        if ( $type != 0){
            if ($style == 0){
                $where = [
                    ['source_id', '=', $this->userId],
                    ['style', '=', 0],
                    ['source','=',1]
                ];
            }else{
                $where = [
                    ['style', '=', $style],
                    ['source','=',0]
                ];
            }
        }else{
            $where = [
                ['style', 'in', [1,2,3,4,5,6]],
                ['source','=',0]
            ];
        }
        return Music::where($where) ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->count();
    }
}
