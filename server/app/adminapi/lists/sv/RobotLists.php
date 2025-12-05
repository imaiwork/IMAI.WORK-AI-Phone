<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvRobot;
use app\common\model\sv\SvSetting;
class RobotLists extends BaseAdminDataLists implements ListsSearchInterface
{
     /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2025-05-15 16:00:09
     */
     public function lists(): array
     {
        return SvRobot::alias('r')
            ->field('r.id,r.name,r.logo,u.nickname,k.name as kname,r.create_time')
            ->leftjoin('user u','u.id = r.user_id')
            ->leftjoin('knowledge_bind kd','kd.data_id = r.id')
            ->leftjoin('knowledge k','kd.kid = k.id')
            ->where($this->searchWhere)
            ->order('r.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function($item){
                 //调用次数
                $item['use_count'] = SvSetting::where('robot_id', $item['id'])
                    ->count() ?? 0;
            })
            ->toArray();
     }

      /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvRobot::alias('r')
            ->field('r.id,r.name,r.logo,u.nickname,k.name as kname,r.create_time')
            ->leftjoin('user u','u.id = r.user_id')
            ->leftjoin('knowledge_bind kd','kd.data_id = r.id')
            ->leftjoin('knowledge k','kd.kid = k.id')
            ->where($this->searchWhere)
            ->count();
    }

     /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [
            "%like%" =>  ['r.name','u.nickname']
        ];
    }
}