<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatMediaFile;

/**
 * 文件列表
 * Class FileLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class FileLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "=" => ['file_type'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatMediaFile::field('id,file_name,file_url, group_ids, file_type, ext_info, create_time')
            ->where($this->searchWhere)
            ->when($this->request->get('group_id'), function ($query)
            {
                $query->whereRaw('JSON_CONTAINS(group_ids,?)', [$this->request->get('group_id')]);
            })
            ->when($this->request->get('file_name'), function ($query)
            {
                $query->where('file_name', 'like', '%' . $this->request->get('file_name') . '%');
            })
            ->order('create_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatMediaFile::field('id,file_name,file_url, ext_info')
            ->where($this->searchWhere)
            ->when($this->request->get('group_id'), function ($query)
            {
                $query->whereRaw('JSON_CONTAINS(group_ids,?)', [$this->request->get('group_id')]);
            })
            ->when($this->request->get('file_name'), function ($query)
            {
                $query->where('file_name', 'like', '%' . $this->request->get('file_name') . '%');
            })
            ->count();
    }
}
