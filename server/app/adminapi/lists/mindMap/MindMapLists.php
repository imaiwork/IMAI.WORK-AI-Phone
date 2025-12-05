<?php

namespace app\adminapi\lists\mindMap;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\model\mindMap\MindMap;

/**
 * 列表
 * Class MindMapLists
 * @package app\Adminapi\lists\mindMap
 */
class MindMapLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function lists(): array
    {

        return MindMap::alias('m')
            ->leftJoin('user u', 'u.id = m.user_id and m.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('name'), function ($query) {
                $query->where('m.ask', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('m.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->field('m.id,m.user_id,m.ask,m.reply,m.create_time,u.nickname,u.avatar,m.task_id')
            ->order('m.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar']     = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';

                $points = 0;
                $tokens = 0;
                //思维导图扣费
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('change_type', AccountLogEnum::TOKENS_DEC_MIND_MAP)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->select()
                    ->each(function ($item) use (&$points, &$tokens) {
                        $info = json_decode($item['extra'], true);

                        $tokens += $info['总消耗tokens数'] ?? 0;
                        $points += $info['实际消耗算力'] ?? 0;
                    });

                $item['points']     = $points;
                $item['tokens']     = $tokens;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function count(): int
    {
        return MindMap::alias('m')
            ->leftJoin('user u', 'u.id = m.user_id and m.user_id <> 0')
            ->where($this->searchWhere)
            ->when($this->request->get('name'), function ($query) {
                $query->where('m.ask', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('m.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
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
        return [];
    }
}
