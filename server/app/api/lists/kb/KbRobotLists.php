<?php


namespace app\api\lists\kb;

use app\api\lists\BaseApiDataLists;
use app\common\model\coze\AgentCate;
use app\common\model\kb\KbKnow;
use app\common\model\kb\KbRobot;
use app\common\service\FileService;

/**
 * 机器人列表
 */
class KbRobotLists extends BaseApiDataLists
{
    public function where(): array
    {
        $where = [];
        if (isset($this->params['type']) && is_numeric($this->params['type'])) {
            $where[] = ['kr.is_public', '=', intval($this->params['type'])];
        }
        if (isset($this->params['cate_id']) && is_numeric($this->params['cate_id'])) {
            $where[] = ['kr.cate_id', '=', intval($this->params['cate_id'])];
        }
        if (!empty($this->params['keyword']) && $this->params['keyword']) {
            $where[] = ['kr.name', 'like', '%' . $this->params['keyword'] . '%'];
        }
        return $where;
    }

    /**
     * @notes 归属范围(资源跟人):企业空间→本企业全体有效成员创建的智能体(不限创建时空间,
     *        成员加入即共享、退团/移除/到期自动退出) ∪ 本人创建的全部;
     *        个人空间→本人创建的全部;系统预置(user_id=0)始终可见。
     *        source: 0=仅系统, 1=仅我的/团队, 其它=系统+我的/团队。
     */
    private function scope(): \Closure
    {
        $userId = (int)$this->userId;
        $teamId = \app\common\service\TeamContextService::currentTeamId($userId);
        $source = $this->params['source'] ?? null;

        $mine = function ($q) use ($userId, $teamId) {
            if ($teamId > 0) {
                $memberIds = \app\common\service\TeamBillingService::activeMemberUserIds($teamId);
                $q->where(function ($q2) use ($memberIds, $userId) {
                    $q2->whereIn('kr.user_id', $memberIds ?: [-1])
                        ->whereOr('kr.user_id', '=', $userId);
                });
            } else {
                $q->where('kr.user_id', '=', $userId);
            }
        };
        $system = function ($q) {
            $q->where('kr.user_id', '=', 0)->where('kr.team_id', '=', 0);
        };

        return function ($q) use ($mine, $system, $source) {
            if ($source === 0 || $source === '0') {
                $system($q);
            } elseif ($source === 1 || $source === '1') {
                $mine($q);
            } else {
                $q->where(function ($q2) use ($mine) {
                    $mine($q2);
                })->whereOr(function ($q2) use ($system) {
                    $system($q2);
                });
            }
        };
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
        $model = new KbRobot();
        $lists = $model
            ->alias('kr')
            ->field([
                        'kr.id,kr.kb_ids,kr.cate_id,kr.intro,kr.image,kr.bg_image,kr.name,kr.sort,kr.is_enable,kr.is_public',
                        'kr.permissions,kr.member_level_ids,kr.create_time,kr.user_id,u.nickname,u.avatar'

                    ])
            ->leftJoin('user u', 'u.id = kr.user_id')
            ->where($this->where())
            ->where($this->scope())
            ->where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->order('kr.id desc')
            ->select()
            ->toArray();

        $modelKbKnow = new KbKnow();
        foreach ($lists as &$item) {
            $item['knows'] = [];
            if ($item['kb_ids']) {
                $kbIds         = explode(',', $item['kb_ids']);
                $item['knows'] = $modelKbKnow->field(['id,name'])->whereIn('id', $kbIds)->select()->toArray();
            }
            $item['cate_id']     = $item['cate_id'] ?? '';
            $item['cate_name']   = $item['cate_id'] ? AgentCate::where('id', $item['cate_id'])->value('name') : '';
            $item['source']      = $item['user_id'] ? 1 : 0;
            $item['source_text'] = $item['source'] ? '用户' : '后台';
            // 团队共享可见,但仅创建者可编辑/删除
            $item['is_owner']    = (int)$item['user_id'] === (int)$this->userId ? 1 : 0;
            $item['avatar']      = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
            unset($item['kb_ids']);
        }

//        $shareRobotIds = KbRobotShareLog::where(['user_id'=>$this->userId])
//            ->distinct(true)
//            ->column('robot_id');
//        foreach ($lists as $key =>$list){
//            $lists[$key]['is_share'] = 0;
//            if(in_array($list['id'],$shareRobotIds)){
//                $lists[$key]['is_share'] = 1;
//            }
//        }
        return $lists;
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author kb
     */
    public function count(): int
    {
        $model = new KbRobot();
        return $model
            ->alias('kr')
            ->field([
                        'kr.id,kr.kb_ids,kr.cate_id,kr.image,kr.name,kr.sort,kr.is_enable,kr.is_public',
                        'kr.create_time,kr.user_id,u.sn,u.nickname,u.avatar,u.mobile'
                    ])
            ->leftJoin('user u', 'u.id = kr.user_id')
            ->where($this->where())
            ->where($this->scope())
            ->where($this->searchWhere)
            ->count();
    }
}