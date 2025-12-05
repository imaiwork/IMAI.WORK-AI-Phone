<?php


namespace app\adminapi\lists\wechat;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\chat\ChatLog;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;


/**
 * 聊天记录列表
 */
class WechatChatLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function lists(): array
    {

        return ChatLog::alias('l')
            ->join('user u', 'u.id = l.user_id')
            ->where('chat_type', AccountLogEnum::TOKENS_DEC_AI_WECHAT)
            ->where($this->searchWhere)
            ->order('l.id', 'desc')
            ->when($this->request->get('start_date') && $this->request->get('end_date'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_date')), strtotime($this->request->get('end_date'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->field('l.id,l.user_id,l.assistant_id,l.task_id,l.message,l.create_time,l.reply,u.nickname,u.avatar')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['avatar']             = FileService::getFileUrl($item['avatar']);

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

                        $points += $info['实际消耗算力'] ?? 0;
                        $tokens += $info['总消耗tokens数'] ?? 0;
                    });

                $item['points']          = $points;
                $item['tokens']          = $tokens;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @author L
     * @date 2024-07-02 16:25:03
     */
    public function count(): int
    {
        return ChatLog::alias('l')
            ->join('user u', 'u.id = l.user_id')
            ->where('chat_type', AccountLogEnum::TOKENS_DEC_AI_WECHAT)
            ->where($this->searchWhere)
            ->order('l.id', 'desc')
            ->when($this->request->get('start_date') && $this->request->get('end_date'), function ($query) {
                $query->whereBetween('l.create_time', [strtotime($this->request->get('start_date')), strtotime($this->request->get('end_date'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
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
