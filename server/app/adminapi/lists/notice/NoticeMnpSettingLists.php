<?php


namespace app\adminapi\lists\notice;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\notice\NoticeSetting;

/**
 * 小程序通知设置
 * Class NoticeMnpSettingLists
 * @package app\adminapi\lists\notice
 */
class NoticeMnpSettingLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @author ljj
     * @date 2022/2/17 2:21 下午
     */
    public function setSearch(): array
    {
        return [
            '=' => ['recipient', 'type']
        ];
    }

    /**
     * @notes 通知设置列表
     * @return array
     * @author ljj
     * @date 2022/2/16 3:18 下午
     */
    public function lists(): array
    {
        return (new NoticeSetting())->field('id,scene_id,scene_name,scene_desc,mnp_notice,type,support,recipient')
                                    ->append(['sms_status_desc', 'type_desc'])
                                    ->where($this->searchWhere)
                                    ->select()
                                    ->toArray();
    }

    /**
     * @notes 通知设置数量
     * @return int
     * @author ljj
     * @date 2022/2/16 3:18 下午
     */
    public function count(): int
    {
        return (new NoticeSetting())->where($this->searchWhere)->count();
    }
}
