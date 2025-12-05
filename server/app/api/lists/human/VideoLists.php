<?php


namespace app\api\lists\human;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanVideoTask;


/**
 * VideoLists
 * @desc 视频任务
 * @author dagouzi
 */
class VideoLists extends BaseApiDataLists implements ListsSearchInterface
{

    /**
     * @notes 搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2022/9/16 18:54
     */
    public function setSearch(): array
    {
        return [
            '%like%' => ['name'],
            '=' => ['status']
        ];
    }


    /**
     * @desc 获取视频列表
     * @return array
     * @date 2024/9/30 19:11
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public function lists(): array
    {

        $field = '*';
        $result = HumanVideoTask::field($field)
            ->where($this->searchWhere)
            ->where('user_id', '=', $this->userId)
            ->order('id DESC')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->toArray();

        return $result;
    }


    /**
     * @desc 获取视频数量
     * @return int
     * @date 2024/9/30 19:11
     * @throws \think\db\exception\DbException
     * @author dagouzi
     */
    public function count(): int
    {
        return HumanVideoTask::where($this->searchWhere)
            ->where('user_id', '=', $this->userId)
            ->count();
    }
}
