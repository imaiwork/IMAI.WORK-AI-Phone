<?php

namespace app\adminapi\lists\assistants;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\ChatLog;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;

/**
 * 列表
 * Class ChatLogLists
 * @package app\Adminapi\lists\assistants
 */
class ChatLogLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists(): array
    {
        $chat_type = $this->request->get('chat_type', 0);

        if ($chat_type == 0) {
            $this->searchWhere[] = ['l.chat_type', 'in', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT]];
        } else {
            $this->searchWhere[] = ['l.chat_type', 'in', [AccountLogEnum::TOKENS_DEC_SCENE_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT]];
        }

        return ChatLog::alias('l')
            ->leftJoin('user u', 'u.id = l.user_id')
            ->leftJoin('assistants a', 'a.id = l.assistant_id')
            ->leftJoin('scene s', 's.id = a.scene_id and a.scene_id <> 0')
            ->where($this->searchWhere)
            ->order('l.id', 'desc')
            ->when($this->request->get('start_date') && $this->request->get('end_date'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_date')), strtotime($this->request->get('end_date'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('scene_name'), function ($query) {
                $query->where('s.name', 'like', '%' . $this->request->get('scene_name') . '%');
            })
            ->field('l.id,l.user_id,l.assistant_id,a.scene_id,l.task_id,l.message,l.create_time,l.reply,s.name as scene_name,a.name as assistant_name,u.nickname,u.avatar')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar'] = $item['avatar'] ? FileService::getFileUrl($item['avatar']) : '';
                $item['scene_name']         = $item['scene_name'] ?? '通用聊天';
                $item['assistant_name']     = $item['assistant_name'] ?? '通用聊天';
                $item['scene_id']           = $item['scene_id'] ?? 0;

                //获取消耗tokens情况
                $points = 0;
                $tokens = 0;
                //扣费记录
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->select()
                    ->each(function ($item) use (&$points, &$tokens) {
                        $info = json_decode($item['extra'], true);

                        $points = $info['实际消耗算力'] ?? 0;
                        $tokens = $info['总消耗tokens数'] ?? 0;
                    });

                $item['points']          = $points;
                $item['tokens']          = $tokens;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function count(): int
    {
        $chat_type = $this->request->get('chat_type', 0);

        if ($chat_type == 0) {
            $this->searchWhere[] = ['l.chat_type', 'in', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT]];
        } else {
            $this->searchWhere[] = ['l.chat_type', 'in', [AccountLogEnum::TOKENS_DEC_COMMON_CHAT, AccountLogEnum::TOKENS_DEC_KNOWLEDGE_CHAT]];
        }

        return ChatLog::alias('l')
            ->leftJoin('user u', 'u.id = l.user_id')
            ->leftJoin('assistants a', 'a.id = l.assistant_id')
            ->leftJoin('scene s', 's.id = a.scene_id and a.scene_id <> 0')
            ->where($this->searchWhere)
            ->order('l.id', 'desc')
            ->when($this->request->get('start_date') && $this->request->get('end_date'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_date')), strtotime($this->request->get('end_date'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('scene_name'), function ($query) {
                $query->where('s.name', 'like', '%' . $this->request->get('scene_name') . '%');
            })
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function setSearch(): array
    {
        return [
            "%like%" => ['l.message']
        ];
    }
}
