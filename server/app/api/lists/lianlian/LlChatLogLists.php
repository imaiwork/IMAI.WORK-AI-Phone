<?php

namespace app\api\lists\lianlian;

use app\common\model\lianlian\LlChat;
use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\service\ConfigService;


/**
 * 列表
 * Class LlChatLogLists
 * @package app\Adminapi\lists\lianlian
 */
class LlChatLogLists extends BaseApiDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function lists(): array
    {

        //随机从配置列表中提取
        $info =  ConfigService::get('lianlian', 'config', []);

        return LlChat::alias('lc')
            ->join('user u', 'u.id = lc.user_id')
            ->join('ll_scene ls', 'ls.id = lc.scene_id')
            ->where('lc.user_id', $this->userId)
            ->where($this->searchWhere)
            ->field('ls.coach_voice, lc.id,lc.analysis_id, lc.scene_id, lc.preliminary_ask, lc.preliminary_ask_audio, lc.preliminary_ask_audio_duration, lc.ask, lc.ask_audio, lc.ask_audio_duration, lc.reply, lc.reply_audio, lc.reply_audio_duration, lc.performance, lc.speechcraft, ls.logo as scene_logo, ls.user_id as scene_user_id, lc.user_id as ask_user_id, lc.create_time, u.nickname as ask_user_name, u.avatar as ask_user_avatar')
            ->order('lc.id', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item, $key) use ($info) {

                // 过滤开场白的数据
                if ($key == 0) {
                    $item['speechcraft'] = "";
                }

                //把第一条数据中的speechcraft赋值给第二条数据
                if ($key == 1) {
                    $item['speechcraft'] = LlChat::where('analysis_id', $item['analysis_id'])->order('id', 'asc')->value('speechcraft') ?? '';
                }

                $item['ask_user_avatar'] = $item['ask_user_avatar'] ? FileService::getFileUrl($item['ask_user_avatar']) : '';
                $item['scene_logo']      = $item['scene_logo'] ? FileService::getFileUrl($item['scene_logo']) : '';
                $item['coach_avatar']    = '';

                // 获取陪练头像
                foreach ($info['voice'] ?? [] as $key => $value) {

                    if ($item['coach_voice'] == $value['code']) {

                        $item['coach_avatar']    = $value['logo'] ? FileService::getFileUrl($value['logo']) : '';
                    }
                }
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function count(): int
    {
        return LlChat::alias('lc')
            ->join('user u', 'u.id = lc.user_id')
            ->join('ll_scene ls', 'ls.id = lc.scene_id')
            ->where('lc.user_id', $this->userId)
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-05 11:05:46
     */
    public function setSearch(): array
    {
        return [
            '=' => ['lc.analysis_id'],
        ];
    }
}
