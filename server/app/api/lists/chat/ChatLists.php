<?php

namespace app\api\lists\chat;

use app\api\lists\BaseApiDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\Assistants;
use app\common\model\chat\ChatLog;
use app\common\model\chat\Scene;
use app\common\model\kb\KbRobot;
use app\common\model\user\User;

class ChatLists extends BaseApiDataLists implements ListsSearchInterface
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
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $sceneId = $this->request->get('scene_id');
        if ($sceneId || $sceneId == 0) {
            // 获取所有子集场景ID
            $sceneIds = Scene::where('pid', $sceneId)->column('id');
            if($sceneId == 0){
                $assistantIds = [0];
            }else{
                $assistantIds = Assistants::whereIn('scene_id', $sceneIds)->column('id');
            }
            $this->searchWhere[] = ['assistant_id', 'in', $assistantIds];
        }

        $chatType = (int)$this->request->get('chat_type');
        if ($chatType == AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT) {
            $this->searchWhere[] = ['robot_id', '>', 0];
        }

        $robotId = (int)$this->request->get('robot_id');
        if ($robotId) {
            $this->searchWhere[] = ['robot_id', '=', $robotId];
        }

        // 首先获取所有不同的 task_id（按最新记录排序）
        $taskIds = ChatLog::where($this->searchWhere)
            ->whereIn('chat_type', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_SCENE_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT, AccountLogEnum::TOKENS_DEC_OPENAI_CHAT])
            ->group('task_id')
            ->order('update_time', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->column('task_id');

        if (empty($taskIds)) {
            return [];
        }

        $logList = [];

        // 对每个 task_id 处理
        foreach ($taskIds as $taskId) {
            $logInfo = ChatLog::where('task_id', $taskId)
                ->order('id', 'asc')
                ->field('id,chat_type,user_id,task_id,robot_id,message,reply,assistant_id,create_time,update_time,extra')
                ->find()
                ->toArray();

            //过滤知识库回复内容
            if (mb_strpos($logInfo['message'], '请根据以下知识库内容回答问题：', 0, 'UTF-8') !== false) {
                $lastSepPos         = mb_strrpos($logInfo['message'], '问题：', 0, 'UTF-8');
                $startPos           = $lastSepPos + mb_strlen('问题：', 'UTF-8');
                $logInfo['message'] = mb_substr($logInfo['message'], $startPos, null, 'UTF-8');;
            }
            $user                = User::where('id', $logInfo['user_id'])->field('nickname,avatar')->find();
            $logInfo['nickname'] = $user['nickname'];
            $logInfo['avatar']   = $user['avatar'];
            $logInfo['robot_name'] = !empty($logInfo['robot_id']) ? KbRobot::where('id', $logInfo['robot_id'])->value('name') : '';
            // 场景名
            if ($logInfo['assistant_id'] == 0) {
                $logInfo['scene_name'] = Assistants::where('id', 1)->value('name');
            } else {
                $logInfo['scene_name'] = Assistants::where('id', $logInfo['assistant_id'])->value('name');
            }
            $extra = json_decode($logInfo['extra'], true) ?? [];
            $logInfo['file_info'] = $extra['file'] ?? [];
            unset($logInfo['extra']);
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

        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $sceneId = $this->request->get('scene_id');
        if ($sceneId || $sceneId == 0) {
            // 获取所有子集场景ID
            $sceneIds = Scene::where('pid', $sceneId)->column('id');
            if($sceneId == 0){
                $assistantIds = [0];
            }else{
                $assistantIds = Assistants::whereIn('scene_id', $sceneIds)->column('id');
            }
            $this->searchWhere[] = ['assistant_id', 'in', $assistantIds];
        }

        // 首先获取所有不同的 task_id（按最新记录排序）
        return ChatLog::where($this->searchWhere)
            ->whereIn('chat_type', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_SCENE_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT])
            ->group('task_id')
            ->count();
    }
}
