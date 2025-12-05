<?php

namespace app\adminapi\lists\sv;

use app\common\enum\user\AccountLogEnum;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAnchor;
use app\common\model\sv\SvVideoTask;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;

/**
 * 视频任务列表
 * Class SvVideoTaskLists
 * @package app\api\lists\sv
 */
class SvVideoTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => [ 'type','video_setting_id', 'audio_type', 'ai_type', 'model_version'],
            '%like%' => ['name'],
            'between' => ['create_time'],
            'in' => ['status']
            // 其他搜索条件
        ];
    }

    public function lists(): array
    {
        $list = SvVideoTask::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        // 处理特定字段，将JSON字符串转为数组
        foreach ($list as &$item) {
            if (!empty($item['extra'])) {
                $item['extra'] = json_decode($item['extra'], true);
            } else {
                $item['extra'] = [];
            }

            $item['audio_url']            = trim($item['audio_url']) ?  FileService::getFileUrl($item['audio_url']) : "";
            $item['upload_audio_url']     = trim($item['upload_audio_url']) ? FileService::getFileUrl($item['upload_audio_url']): "";
            $item['upload_video_url']         = trim($item['upload_video_url']) ? FileService::getFileUrl($item['upload_video_url']): "";
            $item['video_result_url']         = trim($item['video_result_url']) ? FileService::getFileUrl($item['video_result_url']): "";
            $item['clip_result_url']         = trim($item['clip_result_url']) ? FileService::getFileUrl($item['clip_result_url']): "";
            if(in_array($item['status'],[4,5,6])){
                switch ($item['model_version']) {
                    case 1:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO;
                        break;
                    case 2:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO;
                        break;
                    case 4:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM;
                        break;
                    case 6:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT;
                        break;
                    case 7:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING;
                        break;
                }

                $points = 0;
                $duration = 0;

                //扣费记录
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('change_type', $change_type)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type,action,change_amount')
                    ->select()
                    ->each(function ($item) use (&$points, &$duration) {

                        $info = json_decode($item['extra'], true);

                        if ($item['action'] == 1){
                            $points    -=$item['change_amount'];
                        }else{
                            $duration   = $info['音视频时长'] ?? 0;
                            $points     += $item['change_amount'] ?? 0;
                        }

                    });

                $item['points']          = $points;
                $item['duration']        = $duration;
            }

        }
        
        return $list;
    }

    public function count(): int
    {
        return SvVideoTask::where($this->searchWhere)->count();
    }
}