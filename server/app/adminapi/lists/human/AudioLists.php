<?php


namespace app\adminapi\lists\human;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanAudio;
use app\common\model\human\HumanVoice;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;


/**
 * 音频
 */
class AudioLists extends BaseAdminDataLists implements ListsSearchInterface
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
    {$type = $this->request->get('type','0');
        if (trim($type) == ''){
            $type = 0;
        }
        //模型版本
        $modelList = ConfigService::get('model', 'list', []);
        $modelVersion = $modelList['channel'];
        return HumanAudio::alias('ha')
            ->join('user u', 'u.id = ha.user_id')
            ->field('ha.id,ha.name,ha.user_id,ha.model_version,ha.type,
            ha.task_id,ha.create_time,ha.url,ha.status,u.nickname,u.avatar')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ha.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($type, function ($query)use ($type){
                $query->where('ha.type', $type );
            })
            ->where($this->searchWhere)
            ->order(['ha.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelVersion) {
                $item['url']        = FileService::getFileUrl($item['url']);
                $item['avatar']     = FileService::getFileUrl($item['avatar']);
                $item['voice_name'] = HumanVoice::where('voice_id', $item['voice_id'])->value('name') ?? '';

                //模型版本
                foreach ($modelVersion as $value) {
                    if ($value['id'] == $item['model_version']) {

                        $item['model_name'] = $value['name'];
                    }
                }

                $points = 0;
                $duration = 0;
                switch ($item['model_version']) {
                    case 1:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO;
                        break;
                    case 2:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_PRO;
                        break;
                    case 4:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM;
                        break;
                    case 6:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT;
                        break;
                    case 7:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_CHANJING;
                        break;
                }   
                //扣费记录
                UserTokensLog::where('user_id', $item['user_id'])
                    ->where('change_type',   $change_type)
                    ->where('task_id', $item['task_id'])
                    ->field('extra, change_type,change_amount, action')
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
        $type = $this->request->get('type','0');
        if (trim($type) == ''){
            $type = 0;
        }
        return HumanAudio::alias('ha')
            ->join('user u', 'u.id = ha.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($type, function ($query)use ($type){
                $query->where('ha.type', $type );
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ha.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
