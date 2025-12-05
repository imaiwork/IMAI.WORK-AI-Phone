<?php


namespace app\adminapi\lists\human;

use  app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\human\HumanVoice;
use app\common\service\ConfigService;
use app\common\service\FileService;
use app\common\model\user\UserTokensLog;
use app\common\enum\user\AccountLogEnum;

/**
 * 音色
 */
class VoiceLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            "%like%" => ['name'],
            "=" => ['model_version',]
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

        $type = $this->request->get('type','0');
        if (trim($type) == ''){
            $type = 0;
        }
        //模型版本
        $modelList = ConfigService::get('model', 'list', []);
        $modelVersion = $modelList['channel'];
        return HumanVoice::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->field('hv.id,hv.name,hv.user_id,hv.model_version,hv.gender,hv.type,
            hv.task_id,hv.create_time,hv.voice_urls,hv.status,u.nickname,u.avatar')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($type, function ($query)use ($type){
                $query->where('hv.type', $type );
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->order(['hv.create_time' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) use ($modelVersion) {
                $item['url']        = FileService::getFileUrl($item['voice_urls']);
                $item['avatar']     =  trim($item['avatar']) ?  FileService::getFileUrl($item['avatar']) : "";

                //模型版本
                foreach ($modelVersion as $value) {
                    if ($value['id'] == $item['model_version']) {

                        $item['model_name'] = $value['name'];
                    }
                }
                switch ($item['model_version']) {
                    case 1:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE;
                        break;
                    case 2:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_PRO;
                        break;
                    case 4:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM;
                        break;
                    case 6:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT;
                        break;
                    case 7:
                        $change_type = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CHANJING;
                        break;
                }

                // 消耗情况
                $points1 = UserTokensLog::where('user_id', $item['user_id'])->where('action',1)
                    ->where('task_id', $item['task_id'])->where('change_type', $change_type)->value('change_amount') ?? 0;

                $points2 = UserTokensLog::where('user_id', $item['user_id'])->where('action',2)
                    ->where('task_id', $item['task_id'])->where('change_type', $change_type)->value('change_amount') ?? 0;
                $item['points'] = $points1 + $points2 ;
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
        return HumanVoice::alias('hv')
            ->join('user u', 'u.id = hv.user_id')
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($type, function ($query)use ($type){
                $query->where('hv.type', $type );
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('hv.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
