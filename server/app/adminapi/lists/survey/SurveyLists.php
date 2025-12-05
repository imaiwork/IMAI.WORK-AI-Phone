<?php


namespace app\adminapi\lists\survey;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\survey\Surveys;
use app\common\service\FileService;

/**
 * 调查问卷列表
 * Class SurveyLists
 * @package app\adminapi\lists\survey
 */
class SurveyLists extends BaseAdminDataLists
{

    public function setSearch(): array
    {
        return [
            "%like%" => ['s.company_name'],
            "=" => ['s.company_size_type']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function lists(): array
    {

        return Surveys::alias('s')
            ->field('s.id,s.company_name,s.company_size,s.create_time,s.user_id,u.nickname,u.avatar')
            ->leftJoin('user u', 'u.id = s.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', $this->request->get('user'));
            })
            ->where($this->searchWhere)
            ->order('s.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($data) {
                $data['avatar'] = FileService::getFileUrl($data['avatar']);
            })->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {

        return Surveys::alias('s')
            ->field('s.id,s.company_name,s.company_size,s.create_time,s.user_id,u.nickname,u.avatar')
            ->leftJoin('user u', 'u.id = s.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', $this->request->get('user'));
            })
            ->where($this->searchWhere)
            ->count();
    }
}
