<?php

namespace app\api\logic\sv;


use app\api\logic\service\TokenLogService;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use think\facade\Db;
use think\facade\Log;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanVoice;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\model\user\User;
/**
 * SvVideoTaskLogic
 * @desc 视频设置逻辑处理
 */
class SvVideoTaskLogic extends SvBaseLogic
{


     /**
     * 更新形象
     * @param array $data
     * @param string $modelVersion
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function updateAnchor(array $data, string $modelVersion): bool
    {
        $model = SvVideoTask::where('model_version', $modelVersion)->where('status', 8);
        if (in_array($modelVersion,[1,7])) {
            $model = $model->where('anchor_id', $data['id']);
        }elseif ($modelVersion == 2) {
            return true;
        } else {

            return false;
        }

        $model->select()
            ->each(function ($item) use ($data) {
                if ($item->model_version === 7) {

                    if (in_array($data['status'], [2, 4, 5])) {
                        $item->status = ($data['status'] == 2) ? 13 : 9;
                        // TODO 失败退费
                        if ($item->status == 9) {
                            self::refundTokens($item->user_id, $item->anchor_id, $item->task_id, 'human_anchor_chanjing');
                            $anchor = ['status' => 2];
                        }else{
                           
                            if (isset($data['audio_man_id']) && $data['audio_man_id'] != '' && $item->voice_id == '') {
                                $addData = [
                                    'user_id'       => $item->user_id,
                                    'status'        => 1,
                                    'type'          => $item->type,
                                    'voice_id'      => $data['audio_man_id'],
                                    'name'          => $data['name'],
                                    'gender'        => $item->gender,
                                    'model_version' => 7,
                                    'task_id'       => $item->task_id,
                                    'voice_urls'    => $item->upload_video_url
                                ];
                                HumanVoice::create($addData);
                              
                                $item->voice_id = $data['audio_man_id'];
                                $item->status = 13;
                            }
                            
                            $item->width = $data['width'] ?? '';
                            $item->height = $data['height'] ?? '';
                            $anchor = [
                                'width' => $data['width'] ?? '',
                                'height' => $data['height'] ?? '',
                                'status' => 1,
                            ];

                        }

                        HumanAnchor::where('task_id', $item->task_id)->where('type',$item->type)->update($anchor);
                    } 
                }
                $item->save();
            });

        return true;
    }

    /**
     * 更新音色
     * @param array $data
     * @param string $modelVersion
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function updateVoice(array $data, string $modelVersion): bool
    {

        //查询形象
        $model = SvVideoTask::where('model_version', $modelVersion)->where('status', 11);
        if (in_array($modelVersion,[1,7])) {
            $model = $model->where('voice_id', $data['id']);
        }elseif ($modelVersion == 2) {
            return true;
        } else {

            return false;
        }

        $model->select()
            ->each(function ($item) use ($data) {
                if ($item->model_version === 1) { //标准版
                    if (in_array($data['current_status'], ['completed', 'failed'])) {
                        $item->status = ($data['current_status'] == 'completed') ? 1 : 2;

                        // TODO 失败退费
                        if ($item->status == 2) {
                            self::refundTokens($item->user_id, $item->anchor_id, $item->task_id, 'human_anchor');
                        }

                    } else {
                        $item->status = 0;
                    }
                }
                if ($item->model_version === 7) {

                    if (in_array($data['status'], [2, 3, 4])) {
                        $item->status = ($data['status'] == 2) ? 13 : 12;
                        // TODO 失败退费
                        if ($item->status == 12) {
                            HumanVoice::where('voice_id', $item->voice_id)->where('task_id', $item->task_id)->where('model_version', 7)->where('status', 0)->update(['status' => 2, 'remark' => $data['err_msg']]);
                            self::refundTokens($item->user_id, $item->voice_id, $item->task_id, 'human_voice_chanjing');
                        }else{
                            HumanVoice::where('voice_id', $item->voice_id)->where('task_id', $item->task_id)->where('model_version', 7)->where('status', 0)->update(['status' => 1]);
                        }
                    } else {
                        $item->status = 0;
                    }
                }
                $item->save();
            });

        return true;
    }


        /**
     * 更新视频
     * @param array $data
     * @param string $modelVersion
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function updateVideo(array $data, string $modelVersion): bool
    {
        //查询形象
        $model = SvVideoTask::where('model_version', $modelVersion)->where('status', 4);
        if (in_array($modelVersion,[1,7])) {
            $model = $model->where('result_id', $data['id']);
        }elseif (in_array($modelVersion,[4,6])) {
            $model = $model->where('result_id', $data['job_id']);
        }elseif ($modelVersion == 2) {
            return true;
        } else {

            return false;
        }
        $model->select()
            ->each(function ($item) use ($data) {

                if (in_array($item->model_version,[4,6])) { //高级版
                   //这里对应 status=3 或 status=4） 3成功 4失败
                    if (in_array($data['status'], [3,4])) {
                        if ($data['status'] == 3){
                            $duration = $data['duration'] ?? 0;
                            if ($duration == 0){
                                $item->duration = 0;
                            }else{
                                $item->duration = $duration / 1000;
                            }

                        }
                        $item->status = ($data['status'] == 3) ? 6 : 5;
                        $scene = $item->model_version == 4 ? "human_video_ym" : "human_video_ymt";
                    } else {
                        $item->status = 4;
                    }
                    $item->video_result_url   = FileService::downloadFileBySource($data['video_Url'], 'video');
                    $item->remark       = $data['message'] ?? '';
                }

                if ($item->model_version === 7) { //标准版
                    $status = (int)$data['status'];
                    if ($status != 10) {
                        $item->status = ($data['status'] == 30) ? 6 : 5;
                        $scene ="human_video_chanjing";
                       
                    } 
                    if ($status == 30){
                        $item->video_result_url   = FileService::downloadFileBySource($data['video_url'], 'video');
                        $item->audio_url   = FileService::downloadFileBySource($data['audio_urls'][0], 'audio');
                        $item->duration   = $data['duration'] ?? 0;
                    }
                    $item->remark       = $data['msg'] ?? '';
                }

                if(in_array($item->status,[5,6])){
                    $videoSetting = SvVideoSetting::where('id', $item->video_setting_id)->find();
                        if($item->status == 5){
                            self::refundTokens($item->user_id, $item->result_id, $item->task_id, $scene);
                            $videoSetting->error_num += 1;
                            $item->video_token = 0;
                        }else{
                            $videoSetting->success_num += 1;
                        }
                        $num = $videoSetting->video_count -  $videoSetting->success_num;
                        if ( $videoSetting->error_num == $num){
                            $videoSetting->status = 5;
                        }

                        if ($videoSetting->success_num == $videoSetting->video_count){
                            $videoSetting->status = 3;
                        }
                        if ($videoSetting->error_num == $videoSetting->video_count){
                            $videoSetting->status = 4;
                        }
                        $videoSetting->save();
                }

                if($item->status == 6 && $item->automatic_clip == 1&& $item->clip_status == 1){


                    try {
                        $unit = TokenLogService::checkToken($item->user_id, 'video_clip');
                        $result_url = FileService::getFileUrl($item->video_result_url);
                        $params = [
                            'video_id' => $item->id,
                            'task_id' => $item->task_id,
                            'clip_type' => $item->clip_type,
                            'music_url' => $item->music_url,
                            'music_type' => $item->music_type,
                            'msg' => $item->msg,
                            'result_url' => $result_url,
                            'type' => 2,
                        ];
                        Log::channel('clip')->write('短视频视频剪辑参数' . json_encode($params));
                        $response = \app\common\service\ToolsService::Sv()->clip($params);
                        if (isset($response['code']) && $response['code'] == 10000) {

                            $points = $unit;
                            $item->clip_token = $points;
                            if ($points > 0) {
                                $extra = [];
                                //token扣除
                                User::userTokensChange($item->user_id, $points);
                                //记录日志
                                AccountLogLogic::recordUserTokensLog(true, $item->user_id, AccountLogEnum::TOKENS_DEC_VIDEO_CLIP, $points, $item->task_id, $extra);
                            }
                            $item->clip_status = 2;
                        }
                    } catch (\Exception $e) {
                        $item->clip_status = 4;
                        $item->remark       = mb_substr($e->getMessage(), 0, 100);
                    }

                }

                $item->save();
            });

        return true;
    }


     /**
     * @desc 退费
     * @param int $userId
     * @param int $id
     * @param string $taskId
     * @param string $type
     * @return bool
     */
    public static function refundTokens(int $userId, string $id, string $taskId, string $type): bool
    {

        try {

            [$typeIndex, $typeID] = match ($type) {
                'human_anchor' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR],
                'human_voice' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE],
                'human_audio' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO],
                'human_video' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO],
                'human_anchor_pro' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_PRO],
                'human_voice_pro' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_PRO],
                'human_audio_pro' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_PRO],
                'human_video_pro' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO],

                'human_voice_ym' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM],
                'human_audio_ym' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM],
                'human_video_ym' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM],

                'human_voice_ymt' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT],
                'human_audio_ymt' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT],
                'human_video_ymt' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT],

                'human_anchor_chanjing' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING],
                'human_voice_chanjing' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CHANJING],
                'human_audio_chanjing' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_CHANJING],
                'human_video_chanjing' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING],
            };
            // 请求查询接口
            $requestParams = [
                'id' => $id,
                'task_id' => $taskId,
                'type' => $typeIndex
            ];

            if (strpos($type, '_ymt') !== false) {
                $response = \app\common\service\ToolsService::Human()->detailYmt($requestParams);
            }elseif (strpos($type, '_ym') !== false) {
                $response = \app\common\service\ToolsService::Human()->detailYm($requestParams);
            }elseif (strpos($type, '_pro') !== false) {
                $response = \app\common\service\ToolsService::Human()->detailPro($requestParams);
            }elseif (strpos($type, '_chanjing') !== false) {
                $response = \app\common\service\ToolsService::Human()->detailChanjing($requestParams);
            } else {
                $response = \app\common\service\ToolsService::Human()->detail($requestParams);
            }
           
            if(isset($response['data']['task_status']) && $response['data']['task_status'] == 1) {
                return true;
            }
            $count = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 2)->where('task_id', $taskId)->count();

            //查询是否已返还
            if (UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('action', 1)->where('task_id', $taskId)->count() < $count) {

                $points = UserTokensLog::where('user_id', $userId)->where('change_type', $typeID)->where('task_id', $taskId)->value('change_amount') ?? 0;

                AccountLogLogic::recordUserTokensLog(false, $userId, $typeID, $points, $taskId);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }


    public static function updateSvVideoTask(array $params)
    {
        try {
           
            $task = SvVideoTask::where('id',$params['id'])->where('user_id', self::$uid)
                ->find();
            if (!$task) {
                self::setError('视频不存在');
                return false;
            }
            $data['id'] = $params['id'];
            $data['name'] = $params['name'];
            $task->update($data);
            self::$returnData = $task->refresh()->toArray();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    public static function deleteSvVideoTask(int $id)
    {
        try {
            $task = SvVideoTask::where('id',$id)->whereIn('status',[3,5,6])->where('user_id', self::$uid)
                ->find();
            if (!$task) {
                self::setError('视频不存在');
                return false;
            };
            $task->delete();
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }


    public static function retrySvVideoTask(array $params){

        Db::startTrans();

        try {
            $task = SvVideoTask::where('id', $params['id'])->whereIn('status',[2,5,9])->where('user_id', self::$uid)
                ->find();
            if (!$task) {
                self::setError('视频不存在');
                return false;
            };
            $setting = SvVideoSetting::where('id', $task->video_setting_id)->field('id,error_num,status')->find();
            if (!$setting) {
                self::setError('任务不存在');
                return false;
            };
            if ($task['status'] == 2){
                $update['status'] = 10;
            }elseif ($task['status'] == 5){
                $update['status'] = 3;
            }else{
                $update['status'] = 0;
            }
            $update['tries'] = 0;
            $update['id'] =  $params['id'];
            $task->update($update);


            $set['error_num'] = $setting['error_num'] -1;
            $set['status'] = 2;
            $set['id'] = $setting['id'];
            $setting->update($set);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }


    }

    public static function updateClipVideo(array $data): bool
    {
        $model = SvVideoTask::where('id', $data['id'])->where('task_id', $data['task_id'])->where('clip_status', 2)->find();
        if(empty($model)){
            self::setError('参数错误');
            return false;
        }
        if ($data['status'] == 4){
            $count = UserTokensLog::where('user_id', $model['user_id'])->where('change_type', '5101')->where('action', 2)->where('task_id', $data['task_id'])->count();
            //查询是否已返还
            if (UserTokensLog::where('user_id',  $model['user_id'])->where('change_type', '5101')->where('action', 1)->where('task_id', $data['task_id'])->count() < $count) {
                $points = UserTokensLog::where('user_id', $model['user_id'])->where('change_type', '5101')->where('task_id', $data['task_id'])->value('change_amount') ?? 0;
                AccountLogLogic::recordUserTokensLog(false, $model['user_id'], '5101', $points, $data['task_id']);
            }
            $update['clip_token'] = 0;
        }

        $url = '';
        if($data['url'] != ''){
            $url = FileService::setFileUrl($data['url']);
        }
        $update['id'] = $data['id'];
        $update['clip_status'] = $data['status'];
        $update['clip_result_url'] = $url;
        SvVideoTask::update($update);
        if ($data['status'] == 3 && $model['auto_type'] == 1 ){
            $param = [
                'device_code' => $model['device_code'],
                'sv_video_id' => $model['id']
            ];
            //\app\api\logic\auto\PublishLogic::setShanjianPublish($param);
            Log::channel('auto')->write('自动化闪剪视频合成：' . json_encode($param), 'clip');
        }
        return true;
    }

}
