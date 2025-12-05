<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatMediaGroup;
use app\common\model\wechat\AiWechatMediaFile;

/**
 * 文件分组列表
 * Class FileGroupLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class FileGroupLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $userId = $this->userId;
        $this->searchWhere[] = ['user_id', '=', $userId];
        $fileType = $this->request->get('file_type', 0);
        $groupList =  AiWechatMediaGroup::field('id,group_name')
            ->where($this->searchWhere)
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($userId, $fileType)
            {
                $item['file_count'] = AiWechatMediaFile::where('user_id', $userId)->where('file_type', $fileType)->whereRaw('JSON_CONTAINS(group_ids, ?)', [$item->id])->count();
            })
            ->toArray();

        if ($this->request->get('page_no', 1) == 1)
        {
            // 数组上方添加未打标签的好友数量
            array_unshift($groupList, [
                'id' => 0,
                'group_name' => '全部分组',
                'file_count' => AiWechatMediaFile::where('user_id', $userId)->where('file_type', $fileType)->count(),
            ]);
        }

        return $groupList;
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatMediaGroup::field('id,group_name')
            ->where($this->searchWhere)
            ->count();
    }
}
