<?php


namespace app\adminapi\lists\human;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAnchor;
use app\common\model\ModelConfig;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\enum\user\AccountLogEnum;
use app\common\model\human\HumanVideoTask;
use app\common\model\user\UserTokensLog;
use TencentCloud\Tcss\V20201101\Models\VulAffectedRegistryImageInfo;

/**
 * 视频列表
 */
class VideoLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
            "=" => ['model_version']
        ];
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
        //模型版本
        $modelList = ConfigService::get('model', 'list', []);
        $modelVersion = $modelList['channel'];

        return HumanVideoTask::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->field('hv.voice_name,hv.id,hv.name,hv.user_id,hv.model_version,hv.anchor_id,hv.create_time,hv.pic,hv.clip_result_url,hv.clip_status,hv.automatic_clip,
            hv.clip_type,hv.result_url,hv.gender,hv.status,hv.audio_type,hv.task_id,u.nickname,u.avatar')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->order(['hv.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelVersion) {
                $item['pic']            = FileService::getFileUrl($item['pic']);
                $item['clip_result_url']     = FileService::getFileUrl($item['clip_result_url']);
                $item['result_url']     = FileService::getFileUrl($item['result_url']);
                $item['avatar']         = FileService::getFileUrl($item['avatar']);
                $item['anchor_name']    = HumanAnchor::where('anchor_id', $item['anchor_id'])->value('name') ?? '';

                //模型版本
                foreach ($modelVersion as $value) {
                    if ($value['id'] == $item['model_version']) {

                        $item['model_name'] = $value['name'];
                    }
                }
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

                $item['video_points']          = $points;
                $item['duration']        = $duration;
                $item['clip_points']     = 0;
                if ($item['clip_status'] == 3) {
                    $points = 0;
                    UserTokensLog::where('user_id', $item['user_id'])
                        ->where('change_type', '5101')
                        ->where('task_id', $item['task_id'])
                        ->field('extra, change_type,action,change_amount')
                        ->select()
                        ->each(function ($item) use (&$points, &$duration) {

                            if ($item['action'] == 1){
                                $points    -=$item['change_amount'];
                            }else{
                                $points     += $item['change_amount'] ?? 0;
                            }
                        });

                    $item['clip_points'] = $points;;
                }
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     * @author 段誉
     * @date 2023/2/23 18:43
     */
    public function count(): int
    {
        return HumanVideoTask::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
