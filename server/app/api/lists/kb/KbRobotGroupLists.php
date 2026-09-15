<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\model\kb\KbRobotGroup;

/**
 * 智能体分组列表
 */
class KbRobotGroupLists extends BaseApiDataLists
{
    /**
     * @notes 筛选条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where = [];
        if (!empty($this->request->get('name'))) {
            $where[] = ['name', 'like', '%' . $this->request->get('name') . '%'];
        }
        $where[] = ['user_id', '=', $this->userId];
        return $where;
    }

    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DataNotFoundException
     * @throws @\think\db\exception\DbException
     * @throws @\think\db\exception\ModelNotFoundException
     * @author kb
     */
    public function lists(): array
    {
        $model = new KbRobotGroup();
        $model = $model
            ->field(['id,user_id,name,sort,create_time'])
            ->where($this->where());
        if (!empty($this->request->get('sort'))) {
            $model = $model->order('sort', $this->request->get('sort'));
        } else {
            $model = $model->order('sort', 'desc');
        }
        return $model
            ->order('update_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['is_top'] = $item['sort'] == 999 ? 1 : 0;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbRobotGroup();
        return $model
            ->where($this->where())
            ->count();
    }
}