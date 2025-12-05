<?php

namespace app\adminapi\lists\meeting;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\audio\AudioInfo;
use app\common\model\audio\Audio;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;

/**
 * 列表
 * Class MeetingLists
 * @package app\Adminapi\lists\meeting
 */
class MeetingLists extends BaseAdminDataLists implements ListsSearchInterface
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

        return Audio::alias('a')
            ->leftJoin('user u', 'u.id = a.user_id and a.user_id <> 0')
            ->where($this->searchWhere)
            ->where('a.json_info', '<>', '')
            ->when($this->request->get('name'), function ($query) {
                $query->where('a.file_name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->field('a.id,a.user_id,a.file_name,a.file_path,a.create_time,a.json_info,u.nickname,u.avatar,a.order_id as task_id')
            ->order('a.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $info = json_decode($item['json_info'], true);
                unset($item['json_info']);
                $item['language']   = $info['language'] ?? '';
                $item['avatar']     = FileService::getFileUrl($item['avatar']);
                $item['file_path']  = FileService::getFileUrl($item['file_path']);

                //会议内容 转 markdown
                $item['content'] = AudioInfo::alias('ai')
                    ->leftJoin('audio_key_words ak', 'ai.key_word_id = ak.id')
                    ->where('ai.audio_id', $item['id'])
                    ->field('ai.markdown,ak.name')
                    ->select()
                    ->toArray();

                $points = 0;
                $duration = 0;
                $tokens = 0;
                //会议扣费
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('change_type', 'in', [AccountLogEnum::TOKENS_DEC_MEETING])
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->select()
                    ->each(function ($item) use (&$points, &$duration) {
                        $info = json_decode($item['extra'], true);

                        $duration += $info['音视频时长'] ?? 0;
                        $points += $info['实际消耗算力'] ?? 0;
                    });

                $item['points']     = $points;
                $item['duration']   = $duration;
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
        return Audio::alias('a')
            ->leftJoin('user u', 'u.id = a.user_id and a.user_id <> 0')
            ->where($this->searchWhere)
            ->where('a.json_info', '<>', '')
            ->when($this->request->get('name'), function ($query) {
                $query->where('a.file_name', 'like', '%' . $this->request->get('name') . '%');
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
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [];
    }
}
