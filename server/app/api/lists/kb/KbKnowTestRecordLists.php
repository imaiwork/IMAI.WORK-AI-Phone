<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\model\kb\KbKnowTestRecord;


/**
 * 知识库列表
 */
class KbKnowTestRecordLists extends BaseApiDataLists
{
    /**
     * @notes 筛选条件
     * @return array
     * @author kb
     */
    public function where(): array
    {
        $where[] = ['user_id', '=', $this->userId];
        $where[] = ['kb_id', '=', $this->request->get('kb_id')];
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