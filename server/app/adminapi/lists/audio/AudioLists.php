<?php

namespace app\adminapi\lists\audio;

use app\common\model\audio\AudioInfo;
use app\common\model\audio\Audio;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;
use app\common\service\ConfigService;

/**
 * 列表
 * Class RechargeLists
 * @package app\Adminapi\lists\audio
 */
class AudioLists extends BaseAdminDataLists implements ListsSearchInterface
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
        return AudioInfo::alias('ai')
            ->join('user u', 'ai.user_id = u.id')
            ->field('ai.id,ai.name,ai.task_id,ai.task_type,ai.user_id,ai.url,ai.response,ai.translation,ai.status,ai.remark,ai.text,ai.create_time,ai.language,u.nickname as user_name,u.avatar as user_avatar')
            ->where($this->searchWhere)
            ->when($this->request->get('user'), function ($query) {
                $query->where('ai.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ai.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('ai.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['url']            = FileService::getFileUrl($item['url']);
                $item['user_avatar']    = FileService::getFileUrl($item['user_avatar']);
                $item['ws_url']         = $item['ws_url'] ?: '';
                $item['text']           = $item['text'] ?: '';

                //算力扣费情况
                $points = 0;
                $duration = 0;

                //扣费记录
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('change_type', AccountLogEnum::TOKENS_DEC_MEETING)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type')
                    ->select()
                    ->each(function ($item) use (&$points, &$duration) {
                        $info = json_decode($item['extra'], true);

                        $duration   += $info['音视频时长'] ?? 0;
                        $points     += $info['实际消耗算力'] ?? 0;
                    });

                $item['points']          = $points;
                $item['duration']        = $duration;

                $meetingConfig = ConfigService::get('meeting', 'config', []);

                $item['language_name'] = '';
                $item['translation_name'] = '';

                foreach ($meetingConfig['language'] as $key => $value) {

                    if($value['code'] == $item['language']){

                        $item['language_name'] = $value['name'];
                    }
                }

                foreach ($meetingConfig['translation'] as $key => $value) {

                    if($value['code'] == $item['translation']){

                        $item['translation_name'] = $value['name'];
                    }
                }
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
        return AudioInfo::alias('ai')
            ->join('user u', 'ai.user_id = u.id')
            ->field('ai.id,ai.name,ai.task_id,ai.task_type,ai.user_id,ai.url,ai.response,ai.translation,ai.status,ai.remark,ai.text,ai.create_time,ai.language,u.nickname as user_name,u.avatar as user_avatar')
            ->where($this->searchWhere)
            ->when($this->request->get('user'), function ($query) {
                $query->where('ai.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ai.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
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
        return [
            "%like%" =>  ['name'],
            "=" =>  ['status'],
        ];
    }
}
