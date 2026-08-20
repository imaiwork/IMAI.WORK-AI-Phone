<?php


namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\kb\KbKnowTestRecord;


/**
 * 搜索测试记录列表
 */
class KbKnowTestRecordLists extends BaseAdminDataLists
{
    /**
     * @notes 筛选条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where[] = ['user_id', '=', 0];
        if (isset($this->params['kb_id']) && $this->params['kb_id']) {
            $where[] = ['kb_id', '=', intval($this->params['kb_id'])];
        }
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
        $model = new KbKnowTestRecord();
        return $model
            ->field(['id,user_id,kb_id,ask,create_time'])
            ->where($this->where())
            ->order('id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
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
        $model = new KbKnowTestRecord();
        return $model
            ->where($this->where())
            ->count();
    }
}
