<?php


namespace app\api\lists\hd;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\hd\HdImage;
use app\common\model\hd\HdLog;


/**
 * 充值记录列表
 * Class RechargeLists
 * @package app\api\lists\recharge
 */
class HdLists extends BaseApiDataLists implements ListsSearchInterface, ListsExtendInterface
{
    public function setSearch(): array
    {
        return [];
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
        $where = [];
        $request = $this->request->get();
        if (!empty($request['type'])) {
            $where['hl.type'] = $request['type'];
        }
        if (!empty($request['model_type'])) {
            $where['hl.model_type'] = $request['model_type'];
        }
        $where['hl.user_id'] = $this->userId;
        $where['hi.task_status'] = 1;
        $result = HdImage::alias('hi')
            ->join('hd_log hl', 'hl.id = hi.log_id')
            ->where($where)
            ->field('hi.*,hl.type')
            ->order('hi.id desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        return $result;
    }

    /**
     * @desc 返回执行中的任务列表
     * @return array|mixed
     * @date 2024/7/18 18:12
     * @author dagouzi
     */
    public function extend()
    {
        $taskLists = HdLog::where(['task_status' => 0])->column('task_id');
        return $taskLists;
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        $where = [];
        $request = $this->request->get();
        if (!empty($request['type'])) {
            $where['type'] = $request['type'];
        }
        if (!empty($request['model_type'])) {
            $where['model_type'] = $request['model_type'];
        }
        $where['hl.user_id'] = $this->userId;
        $where['hi.task_status'] = 1;
        return HdImage::alias('hi')->join('hd_log hl', 'hl.id = hi.log_id')->where($where)->count();
    }
}
