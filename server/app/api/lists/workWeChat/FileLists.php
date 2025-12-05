<?php


namespace app\api\lists\workWeChat;

use app\api\lists\BaseapiDataLists;
use app\common\enum\FileEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\file\File;
use app\common\service\FileService;

/**
 * 文件列表
 * Class FileLists
 * @package app\adminapi\lists\file
 */
class FileLists extends BaseapiDataLists implements ListsSearchInterface
{

    /**
     * @notes 文件搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2021/12/29 14:27
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'source'],
            '%like%' => ['name']
        ];
    }



    /**
     * @notes 获取文件列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2021/12/29 14:27
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['source_id', '=', $this->userId];
        $lists = (new File())->field(['id,cid,type,name,uri,create_time'])
            ->order('id', 'desc')
            ->where($this->searchWhere)
            ->where('source', FileEnum::SOURCE_USER)
            ->where('type', FileEnum::CSV_TYPE)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['uri'] = FileService::getFileUrl($item['uri']);
        }

        return $lists;
    }


    /**
     * @notes 获取文件数量
     * @return int
     * @author 段誉
     * @date 2021/12/29 14:29
     */
    public function count(): int
    {
        $this->searchWhere[] = ['source_id', '=', $this->userId];
        return (new File())->where($this->searchWhere)
            ->where('source', FileEnum::SOURCE_USER)
            ->where('type', FileEnum::CSV_TYPE)
            ->count();
    }
}
