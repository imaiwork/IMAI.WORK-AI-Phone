<?php

namespace app\adminapi\lists\kb;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Assistants;
use app\common\model\chat\ChatLog;
use app\common\model\chat\Scene;
use app\common\model\kb\KbRobot;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;

class ChatLists extends BaseAdminDataLists implements ListsSearchInterface
{
    // 会话列表涉及的聊天类型
    const CHAT_TYPES = [
        AccountLogEnum::TOKENS_DEC_COMMON_CHAT,
        AccountLogEnum::TOKENS_DEC_SCENE_CHAT,
        AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT,
    ];

    // 查询条件缓存，避免 lists() 与 count() 重复拼接
    private ?array $where = null;

    public function setSearch(): array
    {
        return [];
    }

    /**
     * @notes 拼装查询条件（lists()与count()共用，保证分页总数与列表一致）
     * @return array
     */
    private function buildWhere(): array
    {
        if ($this->where !== null) {
            return $this->where;
        }

        $where = $this->searchWhere;

        $user = $this->request->get('user');
        if ($user) {
            $userIds  = User::where('nickname', 'like', '%' . $user . '%')->column('id');
            $where[] = ['user_id', 'in', $userIds];
        }
        $message = $this->request->get('message');
        if ($message) {
            $where[] = ['message', 'like', '%' . $message . '%'];
        }
        $startTime = strtotime($this->request->get('start_date'));
        $endTime   = strtotime($this->request->get('end_date'));
        if ($startTime && $endTime) {
            $where[] = ['update_time', 'between', [$startTime, $endTime]];
        }
        $sceneId = $this->request->get('scene_id');
        if ($sceneId || $sceneId == 0) {
            // 获取所有子集场景ID
            $sceneIds = Scene::where('pid', $sceneId)->column('id');
            if ($sceneId == 0) {
                $assistantIds = [0];
            } else {
                $assistantIds = Assistants::whereIn('scene_id', $sceneIds)->column('id');
            }
            $where[] = ['assistant_id', 'in', $assistantIds];
        }

        $chatType = (int)$this->request->get('chat_type');
        if ($chatType == AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT) {
            $where[] = ['chat_type', '=', AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT];
        }

        $robotId = (int)$this->request->get('robot_id');
        if ($robotId) {
            $where[] = ['robot_id', '=', $robotId];
        }

        return $this->where = $where;
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
        // 按 task_id 分组取出本页会话，last_time=会话最后活跃时间
        $groups = ChatLog::where($this->buildWhere())
                         ->whereIn('chat_type', self::CHAT_TYPES)
                         ->field('task_id, MAX(update_time) AS last_time')
                         ->group('task_id')
                         ->order('last_time', 'desc')
                         ->limit($this->limitOffset, $this->limitLength)
                         ->select()
                         ->toArray();

        if (empty($groups)) {
            return [];
        }

        $taskIds = array_column($groups, 'task_id');

        // 每个会话的首条记录id：不带筛选条件，取会话真正的第一条（与原逐条回表查询一致）
        $firstIds = ChatLog::whereIn('task_id', $taskIds)
                           ->group('task_id')
                           ->column('MIN(id)');

        // 会话首条记录
        $logs = ChatLog::whereIn('id', $firstIds)
                       ->field('id,chat_type,user_id,task_id,robot_id,message,reply,assistant_id,create_time,update_time')
                       ->select()
                       ->toArray();
        $logs = array_column($logs, null, 'task_id');

        // 消耗算力：不加order（避免无索引时走主键反向全表扫描），在PHP里按最小id去重
        $pointsMap  = [];
        $pointsRows = UserTokensLog::whereIn('task_id', $taskIds)
                                   ->field('id,task_id,change_amount')
                                   ->select()
                                   ->toArray();
        $pointsMinId = [];
        foreach ($pointsRows as $row) {
            $tid = $row['task_id'];
            if (!isset($pointsMinId[$tid]) || $row['id'] < $pointsMinId[$tid]) {
                $pointsMinId[$tid] = $row['id'];
                $pointsMap[$tid]   = $row['change_amount'];
            }
        }

        // 用户、机器人、场景（助手）名称
        // 用select()而非column()，保留User模型的avatar访问器（补全图片域名）
        $userIds = array_filter(array_column($logs, 'user_id'));
        $userMap = $userIds ? array_column(User::whereIn('id', $userIds)->field('id,nickname,avatar')->select()->toArray(), null, 'id') : [];

        $robotIds  = array_filter(array_column($logs, 'robot_id'));
        $robotMap  = $robotIds ? KbRobot::whereIn('id', $robotIds)->column('name', 'id') : [];

        // assistant_id为0时统一取id为1的助手
        $assistantIds = array_map(function ($item) {
            return empty($item['assistant_id']) ? 1 : $item['assistant_id'];
        }, $logs);
        $assistantMap = Assistants::whereIn('id', array_unique($assistantIds))->column('name', 'id');

        $logList = [];
        foreach ($taskIds as $taskId) {
            if (!isset($logs[$taskId])) {
                continue;
            }
            $logInfo = $logs[$taskId];

            $logInfo['points'] = $pointsMap[$taskId] ?? 0;

            //过滤知识库回复内容
            if (mb_strpos($logInfo['message'], '请根据以下知识库内容回答问题：', 0, 'UTF-8') !== false) {
                $lastSepPos         = mb_strrpos($logInfo['message'], '问题：', 0, 'UTF-8');
                $startPos           = $lastSepPos + mb_strlen('问题：', 'UTF-8');
                $logInfo['message'] = mb_substr($logInfo['message'], $startPos, null, 'UTF-8');
            }

            $logInfo['nickname']   = $userMap[$logInfo['user_id']]['nickname'] ?? '';
            $logInfo['avatar']     = $userMap[$logInfo['user_id']]['avatar'] ?? '';
            $logInfo['robot_name'] = !empty($logInfo['robot_id']) ? ($robotMap[$logInfo['robot_id']] ?? '') : '';
            // 场景名
            $assistantId           = empty($logInfo['assistant_id']) ? 1 : $logInfo['assistant_id'];
            $logInfo['scene_name'] = $assistantMap[$assistantId] ?? '';

            $logList[] = $logInfo;
        }

        return $logList;
    }

    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return ChatLog::where($this->buildWhere())
                      ->whereIn('chat_type', self::CHAT_TYPES)
                      ->group('task_id')
                      ->count();
    }
}
