<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\human\HumanAnchor;
use app\common\model\human\HumanAudio;
use app\common\model\human\HumanTask;
use app\common\model\human\HumanVideoTask;
use app\common\model\human\HumanVoice;
use app\common\model\ModelConfig;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

class HumanLogic extends ApiLogic
{

    const AVATAR_TRAINING = 'avatarTraining'; //形象训练
    const VOICE_TRAINING = 'voiceTraining'; //音色训练
    const AUDIO_TRAINING = 'audioTraining'; //文字转音频
    const VIDEO_TRAINING = 'videoTraining'; //视频训练
    const COPYWRITING_CREATE = 'copywritingCreate'; //文案创作
    const HUMAN_COPYWRITING = 'humanCopywriting';
    const AVATAR_TRAINING_PRO = 'avatarTrainingPro'; //形象训练
    const VOICE_TRAINING_PRO = 'voiceTrainingPro'; //音色训练
    const AUDIO_TRAINING_PRO = 'audioTrainingPro'; //文字转音频
    const VIDEO_TRAINING_PRO = 'videoTrainingPro'; //视频训练

    const AVATAR_TRAINING_YM = 'avatarTrainingYm'; //形象训练
    const VOICE_TRAINING_YM = 'voiceTrainingYm'; //音色训练
    const AUDIO_TRAINING_YM = 'audioTrainingYm'; //文字转音频
    const VIDEO_TRAINING_YM = 'videoTrainingYm'; //视频训练


    const AVATAR_TRAINING_YMT = 'avatarTrainingYmt'; //形象训练
    const VOICE_TRAINING_YMT = 'voiceTrainingYmt'; //音色训练
    const AUDIO_TRAINING_YMT = 'audioTrainingYmt'; //文字转音频
    const VIDEO_TRAINING_YMT = 'videoTrainingYmt'; //视频训练

    const AVATAR_TRAINING_CHANJING = 'avatarTrainingChanjing'; //形象训练
    const VOICE_TRAINING_CHANJING = 'voiceTrainingChanjing'; //音色训练
    const AUDIO_TRAINING_CHANJING = 'audioTrainingChanjing'; //文字转音频
    const VIDEO_TRAINING_CHANJING = 'videoTrainingChanjing'; //视频训练

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
        $model = HumanAnchor::where('model_version', $modelVersion)->where('status', 0);

        if (in_array($modelVersion,[1,7])) {

            $model = $model->where('anchor_id', $data['id']);
        }elseif ($modelVersion == 2) {
            // 目前是同步的  没有回调

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
                } elseif ($item->model_version === 2) {

                }elseif ($item->model_version === 7) {

                    if (in_array($data['status'], [2, 4, 5])) {
                        $item->status = ($data['status'] == 2) ? 1 : 2;
                        // TODO 失败退费
                        if ($item->status == 2) {
                            self::refundTokens($item->user_id, $item->anchor_id, $item->task_id, 'human_anchor_chanjing');
                        }else{
                            $voice_id = HumanVideoTask::where('task_id', $item->task_id)->value('voice_id') ?? '';
                            if (isset($data['audio_man_id']) && $data['audio_man_id'] != '' && $voice_id == '') {
                                $addData = [
                                    'user_id'       => $item->user_id,
                                    'status'        => 1,
                                    'voice_id'      => $data['audio_man_id'],
                                    'name'          => $data['name'],
                                    'gender'        => $item->gender,
                                    'model_version' => 7,
                                    'task_id'       => $item->task_id,
                                    'voice_urls'    => $item->url
                                ];
                                HumanVoice::create($addData);
                                HumanVideoTask::where('task_id', $item->task_id)->update([
                                    'voice_id' => $data['audio_man_id']
                                ]);
                            }
                            $item->width = $data['width'] ?? '';
                            $item->height = $data['height'] ?? '';
                        }
                    } else {
                        $item->status = 0;
                    }
                    $item->remark = $data['err_reason'] ?? '';
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
        $model = HumanVoice::where('model_version', $modelVersion)->where('status', 0);
        if (in_array($modelVersion,[1,7])) {
            $model = $model->where('voice_id', $data['id']);
        } elseif ($modelVersion == 2) {
            // 目前是同步的  没有回调
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
                        $item->status = ($data['status'] == 2) ? 1 : 2;
                        // TODO 失败退费
                        if ($item->status == 2) {
                            self::refundTokens($item->user_id, $item->voice_id, $item->task_id, 'human_voice_chanjing');
                        }
                    } else {
                        $item->status = 0;
                    }
                    $item->remark       = $data['err_msg'] ?? '';
                }
                $item->save();
            });

        return true;
    }

    /**
     * 更新音频
     * @param array $data
     * @param string $modelVersion
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function updateAudio(array $data, string $modelVersion): bool
    {

        //查询形象
        $model = HumanAudio::where('model_version', $modelVersion)->where('status', 0);
        if (in_array($modelVersion,[1,7])) {
            $model = $model->where('audio_id', $data['id']);
        } elseif ($modelVersion == 2) {
            // 目前是同步的  没有回调
            return true;
        } else {

            return false;
        }

        $model->select()
            ->each(function ($item) use ($data) {

                if ($item->model_version === 1) { //标准版

                    $item->status = ($data['url'] != "") ? 1 : 2;
                    $item->url = FileService::downloadFileBySource($data['url'], 'audio');

                    // TODO 失败退费
                    if ($item->status == 2) {
                        self::refundTokens($item->user_id, $item->audio_id, $item->task_id, 'human_audio');
                    }

                    // 更新视频
                    HumanVideoTask::where('task_id', $item->task_id)->update([
                        'audio_url' => FileService::setFileUrl($item->url)
                    ]);
                }

                if ($item->model_version === 7) { //标准版
                    $audio_url = $data['full']['url'] ?? '';
                    if ($audio_url != '' && $data['status'] == 9) {
                        $item->url = FileService::downloadFileBySource($audio_url, 'audio');
                        $item->status = 1;
                    }
                    if ($audio_url == '' && $data['status'] == 9) {
                        $item->status = 2;
                    }

                    // TODO 失败退费
                    if ($item->status == 2) {
                        $item->remark = $data['err_reason'] ?? '';
                        self::refundTokens($item->user_id, $item->audio_id, $item->task_id, 'human_audio_chanjing');
                    }

                    if ($audio_url != ''){
                        // 更新视频
                        HumanVideoTask::where('task_id', $item->task_id)->update([
                            'audio_url' => FileService::setFileUrl($item->url)
                        ]);
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
        $model = HumanVideoTask::where('model_version', $modelVersion)->where('status', 0);
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
                if ($item->model_version === 1) { //标准版

                    if (in_array($data['current_status'], ['success', 'fail'])) {
                        $item->status = ($data['current_status'] == 'success') ? 1 : 2;

                        if ($item->status == 2) {
                            self::refundTokens($item->user_id, $item->result_id, $item->task_id, 'human_video');
                        }

                    } else {
                        $item->status = 0;
                    }
                    $item->result_url   = FileService::downloadFileBySource($data['result'], 'video');
                    $item->remark       = $data['msg'] ?? '';
                }
                if (in_array($item->model_version,[4,6])) { //高级版
                    //这里对应 status=3 或 status=4） 3成功 4失败
                    if (in_array($data['status'], [3,4])) {
                        $item->status = ($data['status'] == 3) ? 1 : 2;
                        $scene = $item->model_version == 4 ? "human_video_ym" : "human_video_ymt";

                        if($item->status == 2){
                            self::refundTokens($item->user_id, $item->result_id, $item->task_id, $scene);
                        }

                    } else {
                        $item->status = 0;
                    }
                    $item->result_url   =  FileService::downloadFileBySource($data['video_Url'], 'video');
                    $item->remark       = $data['msg'] ?? '';
                }
                if ($item->model_version === 7) { //标准版
                    $status = (int)$data['status'];
                    if ($status != 10) {
                        $item->status = ($data['status'] == 30) ? 1 : 2;

                        if ($item->status == 2) {
                            self::refundTokens($item->user_id, $item->result_id, $item->task_id, 'human_video_chanjing');
                        }

                    } else {
                        $item->status = 0;
                    }

                    if ($status == 30){
                        $item->result_url   = FileService::downloadFileBySource($data['video_url'], 'video');
                        $item->audio_url   = FileService::downloadFileBySource($data['audio_urls'][0], 'audio');

                        $user = User::find( $item->user_id);
                        $unit = ModelConfig::where('scene', 'human_video_chanjing')->value('score', 0);
                        $duration = $data['duration'] ?? 1;
                        $points = round($duration * $unit,2);
                        $newpoints = UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING)->where('task_id',  $item->task_id )->value('change_amount') ?? 0;

                        $sl = $newpoints - $points ;
                        if ($sl > 0) {
                            //调整可用余额
                            $kf = '克隆数字人视频预扣费超额扣费退费：'.$sl.'算力';
                            $user->tokens += $sl;
                            $user->save();
                            //记录日志
                            AccountLogLogic::add(
                                $user->id,
                                AccountLogEnum::TOKENS_INC_HUMAN,
                                AccountLogEnum::INC,
                                $sl,
                                1,
                                '',
                                $kf

                            );
                        } else {
                            $sl =  $points - $newpoints ;
                            $kf = '克隆数字人视频预扣费补足费用补扣：'.$sl.'算力';
                            $user->tokens -= $sl;
                            $user->save();
                            //记录日志
                            AccountLogLogic::add(
                                $user->id,
                                AccountLogEnum::TOKENS_DEC_HUMAN_EXT,
                                AccountLogEnum::DEC,
                                $sl,
                                1,
                                '',
                                $kf
                            );
                        }


                    }
                    $item->remark = $data['msg'] ?? '';
                }
                if($item->status == 1 && $item->automatic_clip == 1&& $item->clip_status == 1){
                    $unit = TokenLogService::checkToken($item->user_id, 'video_clip');
                    $result_url = FileService::getFileUrl($item->result_url);
                    $params = [
                        'video_id' => $item->id,
                        'task_id' => $item->task_id,
                        'clip_type' => $item->clip_type,
                        'music_url' => $item->music_url,
                        'music_type' => $item->music_type,
                        'result_url' => $result_url,
                        'msg' => $item->msg,
                        'type' => 1,
                    ];

                    $response = \app\common\service\ToolsService::Sv()->clip($params);
                    if (isset($response['code']) && $response['code'] == 10000) {

                        $points = $unit;

                        if ($points > 0) {
                            $extra = [];
                            //token扣除
                            User::userTokensChange($item->user_id, $points);

                            //记录日志
                            AccountLogLogic::recordUserTokensLog(true, $item->user_id, AccountLogEnum::TOKENS_DEC_VIDEO_CLIP, $points,  $item->task_id, $extra);
                        }
                        $item->clip_status = 2;
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
                'human_anchor_ym' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YM],
                'human_voice_ym' => [2, AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM],
                'human_audio_ym' => [3, AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM],
                'human_video_ym' => [4, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM],
                'human_anchor_ymt' => [1, AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YMT],
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


    /**
     * @desc 视频生成任务
     * @param $request
     * @return bool
     * @date 2024/9/30 17:55
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function videoTask($request)
    {
        try {
            $anchor_id = $request['anchor_id'] ?? '';
            $anchor_name = $request['anchor_name'] ?? '';
            $name = $request['name'] ?? '';
            $gender = $request['gender'] ?? 'male';
            $pic = $request['pic'] ?? '';
            $video_url = $request['video_url'] ?? '';
            $msg = $request['msg'] ?? '';
            $audio_url = $request['audio_url'] ?? '';
            $model_version = $request['model_version'] ?? '';
            $voice_id = $request['voice_id'] ?? '';
            $voice_name = $request['voice_name'] ?? '';
            $voice_type = $request['voice_type'] ?? 1;
            $width = $request['width'] ?? '';
            $height = $request['height'] ?? '';
            $clip_type = $request['clip_type'] ?? 1;
            $automatic_clip = $request['automatic_clip'] ?? 0;
            $music_url = $request['music_url'] ?? '';
            $music_type = $request['music_type'] ?? 1;

            if (empty($anchor_name) || empty($name) || !in_array($model_version, [1, 2, 4, 6, 7]) || !in_array($gender, ['male', 'female'])) {

                throw new \Exception('参数错误');
            }



            //生成一个唯一任务ID
            $taskId = generate_unique_task_id();

            // 驱动类型 1：文字驱动 2：音频驱动
            $audio_type = $request['audio_type'] ?? 0;

            switch ($audio_type) {
                case 1: // 文字驱动
                    //模型1 字数超过150字节
                    if ($model_version == 1 && mb_strlen($msg) > 150) {

                        throw new \Exception('字数不能超过150字节');
                    }

                    //模型2 字数超过30000字节
                    if ($model_version == 2 && mb_strlen($msg) > 30000) {

                        throw new \Exception('字数不能超过30000字节');
                    }
                    //模型2 字数超过2000字
                    if (in_array($model_version, [4, 6]) &&  mb_strlen($msg, 'UTF-8') > 6200) {

                        throw new \Exception('字数不能超过2000');
                    }

                    if ($model_version == 7) {
                        $string = $msg;
                        $result = '';
                        $length = mb_strlen($string);

                        for ($i = 0; $i < $length; $i++) {
                            $char = mb_substr($string, $i, 1);
                            $codePoint = mb_ord($char);

                            // 定义各个范围
                            if ($codePoint >= 0x1F600 && $codePoint <= 0x1F64F) { // 表情符号
                                continue;
                            }
                            if ($codePoint >= 0x1F300 && $codePoint <= 0x1F5FF) { // 杂项符号和象形文字
                                continue;
                            }
                            if ($codePoint >= 0x1F680 && $codePoint <= 0x1F6FF) { // 交通工具和地图符号
                                continue;
                            }
                            if ($codePoint >= 0x1F700 && $codePoint <= 0x1F77F) { // Alchemical Symbols (炼金术符号)
                                continue;
                            }
                            if ($codePoint >= 0x1F900 && $codePoint <= 0x1F9FF) { // 补充象形文字
                                continue;
                            }
                            if ($codePoint >= 0x2600 && $codePoint <= 0x26FF) { // 杂项符号
                                continue;
                            }
                            if ($codePoint >= 0x2700 && $codePoint <= 0x27BF) { // Dingbats
                                continue;
                            }
                            if ($codePoint >= 0x1F1E6 && $codePoint <= 0x1F1FF) { // 区域标志符号
                                continue;
                            }
                            if ($codePoint >= 0x1F3FB && $codePoint <= 0x1F3FF) { // Emoji 修饰符
                                continue;
                            }
                            $result .= $char;
                        }
                        $msg = $result;
                    }
                    break;
                case 2: // 音频驱动
                    $msg = '';
                    if (in_array($model_version, [1])) {

                        throw new \Exception('目前不支持标准版');
                    }

                    // 音频驱动 音频链接不能为空
                    if (empty($audio_url)) {
                        throw new \Exception('音频文件不能为空');
                    }
                    break;
                default:
                    throw new \Exception('参数错误');
            }

            //标题 只能有数字与字母、中文组成
            $pattern = '/^[a-zA-Z0-9\x{4e00}-\x{9fa5}]*$/u';

            if (!preg_match($pattern, $name)) {

                throw new \Exception('标题只能有数字与字母、中文组成, 且10个字符以内');
            }
            // 验证余额
            if ($model_version == 1) {
                TokenLogService::checkToken(self::$uid, self::VIDEO_TRAINING);
            } elseif ($model_version == 4) {
                TokenLogService::checkToken(self::$uid, self::VIDEO_TRAINING_YM);
            } elseif ($model_version == 6) {
                TokenLogService::checkToken(self::$uid, self::VIDEO_TRAINING_YMT);
            }elseif ($model_version == 7) {
                TokenLogService::checkToken(self::$uid, self::VIDEO_TRAINING_CHANJING);
            }else {
                TokenLogService::checkToken(self::$uid, self::VIDEO_TRAINING_PRO);
            }

            //如果用户有任务在处理中，不允许创建任务
            $taskCount = HumanVideoTask::where('user_id', self::$uid)
                ->where('status', 0)
                ->where('model_version', $model_version)
                ->count();

            if ($taskCount >= 5) {

                throw new \Exception('当前您已有5个任务正在排队处理中，请等待任务完成后再创建');
            }


            // 形象ID 已有形象
            if ($anchor_id) {
                $anchor = HumanAnchor::where('anchor_id', $anchor_id)->findOrEmpty();
                if ($anchor->isEmpty()) {
                    throw new \Exception('形象不存在');
                }
                $video_url =  $anchor['url'];
            }
            if ( $pic == ''){
                $pic = self::getVideoThumbnailFromUrl($video_url);
                if ( $pic == false){
                    Log::write('获取图片任务失败' . $pic);
                    throw new \Exception('封面获取失败');
                }
            }

            // 音色ID 已有音色
            if ($voice_id && $voice_type == 1) {
                $voice = HumanVoice::where('voice_id', $voice_id)->findOrEmpty();
                if ($voice->isEmpty()) {
                    throw new \Exception('音色不存在');
                }
                $voice_name =  $voice['name'];
            }elseif ($voice_id && $voice_type == 0){
                $voice_id = HumanVoice::getBuiltInVoice($voice_id,$model_version);
                if ($voice_name == '') {
                    throw new \Exception('音色名称不能为空');
                }
                if ($voice_id === '00000') {
                    throw new \Exception('音色错误');
                }
            }

            $videoTaskData = [
                'user_id' => self::$uid,
                'name' => $name,
                'pic' => $pic,
                'gender' => $gender,
                'width' => $width,
                'height' => $height,
                'audio_type' => $audio_type,
                'anchor_id' => $anchor_id,
                'anchor_name' => $anchor_name,
                'voice_id' => $voice_id,
                'voice_name' => $voice_name,
                'msg' => $msg,
                'task_id' => $taskId,
                'model_version' => $model_version,
                'upload_video_url' => $video_url,
                'upload_audio_url' => $audio_url,
                'music_url' => $music_url,
                'music_type' => $music_type,
                'automatic_clip' => $automatic_clip,
                'clip_type' => $clip_type,
            ];

            if ($audio_type == 2) {
                $videoTaskData['audio_url'] = $audio_url;
            }
            $videoTask = HumanVideoTask::create($videoTaskData);

            self::$returnData = $videoTask->toArray();

            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @desc 创建形象
     * @param $data
     * @return bool
     * @throws \Exception
     * @date 2024/9/28 17:46
     * @author dagouzi
     */
    public static function createAnchor($data)
    {
        // 检查余额
        if ($data['model_version'] == 1) {
            TokenLogService::checkToken(self::$uid, 'human_anchor');
        } elseif ($data['model_version'] == 4){
            TokenLogService::checkToken(self::$uid, 'human_anchor_ym');
        }elseif ($data['model_version'] == 6){
            TokenLogService::checkToken(self::$uid, 'human_anchor_ymt');
        }elseif ($data['model_version'] == 7){
            TokenLogService::checkToken(self::$uid, 'human_anchor_chanjing');
        }else {
            TokenLogService::checkToken(self::$uid, 'human_anchor_pro');
        }
        $name = $data['name'] ?? '';
        $width = $data['width'] ?? '';
        $height = $data['height'] ?? '';
        $gender = $data['gender'] ?? 'male';
        $anchor_url = $data['url'] ?? '';
        $model_version = $data['model_version'] ?? '';
        if (empty($name) || !in_array($model_version, [1, 2, 4, 6, 7]) || !in_array($gender, ['male', 'female']) || empty($anchor_url)) {
            message('参数错误');
        }

        //标题 只能有数字与字母、中文组成
        $pattern = '/^[a-zA-Z0-9\x{4e00}-\x{9fa5}]*$/u';

        if (!preg_match($pattern, $name)) {

            message('标题只能有数字与字母、中文组成, 且10个字符以内');
        }
        $pic =  $data['pic'] ?? '';
        if ($pic == ''){
            $pic = self::getVideoThumbnailFromUrl($anchor_url);
            if ( $pic == false){
                self::setError('封面获取失败');
                return false;
            }
        }
        $taskId = generate_unique_task_id();

        if (in_array($data['model_version'] ,[2, 4, 6])) {

            $addData = [
                'user_id' => self::$uid,
                'status' => 1,
                'anchor_id' => uniqid(),
                'name' => $name,
                'gender' => $gender,
                'url' => $anchor_url,
                'task_id' => $taskId,
                'model_version' => $model_version,
                'pic' => $pic,
            ];
            $anchor = HumanAnchor::create($addData);

            self::$returnData = [
                'id' => $anchor->anchor_id,
                'pic' => $pic,
                'picurl' => FileService::getFileUrl($pic),
            ];
            return true;
        }

        $request = [
            'video_url' => $anchor_url,
            'name' => $name,
            'gender' => $gender,
        ];
        switch ($model_version)
        {
            case  1:
                $scene = self::AVATAR_TRAINING;
                break;
            case  2:
                $scene = self::AVATAR_TRAINING_PRO;
                break;
            case 4:
                $scene = self::AVATAR_TRAINING_YM;
                break;
            case 6:
                $scene = self::AVATAR_TRAINING_YMT;
                break;
            case 7:
                $scene = self::AVATAR_TRAINING_CHANJING;
                break;
            default:
                $scene = self::AVATAR_TRAINING;
                break;
        }

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);

        if (!empty($result) && isset($result['id'])) {

            $result['pic'] = $pic;
            $result['picurl'] = FileService::getFileUrl($pic);
            self::$returnData = $result;
            $addData = [
                'user_id' => self::$uid,
                'status' => $result['status'] == 'completed' ? 1 : 0,
                'anchor_id' => $result['id'],
                'name' => $name,
                'gender' => $gender,
                'width' => $width,
                'height' => $height,
                'url' => $anchor_url,
                'task_id' => $taskId,
                'model_version' => $model_version,
                'pic' => $pic
            ];
            HumanAnchor::create($addData);
        } else {
            self::setError('合成失败');
            return false;
        }
        return true;
    }


    /**
     * @desc 形象重试
     * @param $data
     * @return bool
     * @throws \Exception
     * @date 2024/9/28 17:46
     * @author dagouzi
     */
    public static function anchorRetry(int $id)
    {
        $anchor = HumanAnchor::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
        if ($anchor->isEmpty()) {

            message('形象不存在');
        }

        if ($anchor->model_version == 2) {
            message('当前模型无需重试');
        }

        //状态不对
        if ($anchor->status != 2) {
            message('当前任务不是失败状态，请勿提交');
        }

        //提交时间间隔1分钟
        if (strtotime($anchor->update_time) > strtotime('-1 minute')) {
            message('任务训练间隔1分钟，请勿频繁提交');
        }

        //保存更新时间
        $anchor->update_time = time();
        $anchor->save();

        $request = [
            'video_url' => $anchor->url,
            'name'      => $anchor->name,
            'gender'    => $anchor->gender,
        ];

        switch ( $anchor->model_version)
        {
            case  1:
                $scene = self::AVATAR_TRAINING;
                break;
            case  2:
                $scene = self::AVATAR_TRAINING_PRO;
                break;
            case 4:
                $scene = self::AVATAR_TRAINING_YM;
                break;
            case 6:
                $scene = self::AVATAR_TRAINING_YMT;
                break;
            case 7:
                $scene = self::AVATAR_TRAINING_CHANJING;
                break;
            default:
                $scene = self::AVATAR_TRAINING;
                break;
        }

        $result = self::requestUrl($request, $scene, $anchor->user_id, $anchor->task_id);

        if (!empty($result) && isset($result['id'])) {

            $anchor->anchor_id  = $result['id'];
            $anchor->status    = 0;
            $anchor->save();
            self::$returnData = $anchor->toArray();
        } else {

            self::setError('重试失败');
            return false;
        }
        return true;
    }

    /**
     * @desc 形象列表
     * @param $data
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:25
     * @author dagouzi
     */
    public static function anchorLists($data): array
    {
        //TODO 新增分页
        $pageNo = ($data['page_no'] - 1) * $data['page_size'];
        $pageSize = $data['page_size'];
        $modelVersion = $data['model_version'] ?? '';
        $status = $data['status'] ?? '';
        $name = $data['name'] ?? '';
        $type = $data['type'] ?? '';
        $result = HumanAnchor::where(['user_id' => self::$uid])
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($modelVersion, function ($query) use ($modelVersion) {
                $query->where('model_version', 'in', explode(',', $modelVersion));
            })
            ->when($type != "", function ($query) use ($type) {
                $query->where('type', $type);

            })
            ->when($status != "", function ($query) use ($status) {
                $query->whereIn('status', $status);
            })
            ->limit($pageNo, $pageSize)
            ->order('create_time', 'desc')
            ->select()->toArray();

        $data = [
            'lists' => $result,
            'count' => HumanAnchor::where(['user_id' => self::$uid])
                ->when($modelVersion, function ($query) use ($modelVersion) {
                    $query->where('model_version', 'in', explode(',', $modelVersion));
                })
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($type != "", function ($query) use ($type) {
                    $query->where('type', $type);

                })
                ->when($status != "", function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->count(),
            'page_no' => $data['page_no'],
            'page_size' => $data['page_size'],
        ];
        return $data;
    }

    /**
     * @desc 删除形象
     * @param array $data
     * @return bool
     * @date 2024/9/28 18:31
     * @author dagouzi
     */
    public static function anchorDelete(array $data)
    {

        try {

            if (is_string($data['id'])) {
                HumanAnchor::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                HumanAnchor::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 语音克隆
     * @param $data
     * @return bool
     * @date 2024/9/28 18:36
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function createVoice($data)
    {
        // 检查余额
        if ($data['model_version'] == 1) {
            TokenLogService::checkToken(self::$uid, 'human_voice');
        }elseif ($data['model_version'] == 4) {
            TokenLogService::checkToken(self::$uid, 'human_voice_ym');
        }elseif ($data['model_version'] == 6) {
            TokenLogService::checkToken(self::$uid, 'human_voice_ymt');
        }elseif ($data['model_version'] == 7) {
            TokenLogService::checkToken(self::$uid, 'human_voice_chanjing');
        }else {
            TokenLogService::checkToken(self::$uid, 'human_voice_pro');
        }
        $name = $data['name'] ?? '';
        $gender = $data['gender'] ?? '';
        $voice_url = $data['url'] ?? '';
        $model_version = $data['model_version'] ?? '';
        if (empty($name) || !in_array($model_version, [1, 2, 4, 6, 7]) || !in_array($gender, ['male', 'female']) || empty($voice_url)) {
            message('参数错误');
        }

        //标题
        $pattern = '/^[a-zA-Z0-9\p{Han}]{1,10}$/u';

        if (!preg_match($pattern, $name)) {

            message('标题只能有数字与字母、中文组成, 且10个字符以内');
        }

        $taskId = generate_unique_task_id();

        $request = [
            'name' => $name,
            'gender' => $gender,
            'audio_url' => $voice_url
        ];

        switch ($model_version) {
            case 1:
                $scene = self::VOICE_TRAINING;
                break;
            case 2:
                $scene = self::VOICE_TRAINING_PRO;
                break;
            case 4:
                $scene = self::VOICE_TRAINING_YM;
                break;
            case 6:
                $scene = self::VOICE_TRAINING_YMT;
                break;
            case 7:
                $scene = self::VOICE_TRAINING_CHANJING;
                break;
            default:
                $scene = self::VOICE_TRAINING_PRO;
                break;
        }


        $result = self::requestUrl($request, $scene, self::$uid, $taskId);

        if (!empty($result) && isset($result['id'])) {
            self::$returnData = $result;
            $addData = [
                'user_id'       => self::$uid,
                'status'        => $model_version == 2 ? 1 : 0,
                'voice_id'      => $result['id'],
                'name'          => $name,
                'gender'        => $gender,
                'model_version' => $model_version,
                'task_id'       => $taskId,
                'voice_urls'    => $voice_url
            ];
            HumanVoice::create($addData);

            if(in_array( $model_version,[4,6])){
                HumanTask::create([
                    'user_id' => self::$uid,
                    'video_task_id' => 0,
                    'task_id' => $taskId,
                    'model_version' => $model_version,
                    'data_id'=> $result['id'],
                    'type' => 2,
                ]);
            }

        } else {
            self::setError('合成失败');
            return false;
        }
        return true;
    }

    /**
     * @desc 音色重试
     * @param $data
     * @return bool
     * @throws \Exception
     * @date 2024/9/28 17:46
     * @author dagouzi
     */
    public static function voiceRetry(int $id)
    {
        $voice = HumanVoice::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
        if ($voice->isEmpty()) {

            message('音色不存在');
        }

        //状态不对
        if ($voice->status != 2) {
            message('当前任务不是失败状态，请勿提交');
        }

        //提交时间间隔1分钟
        if (strtotime($voice->update_time) > strtotime('-1 minute')) {
            message('任务训练间隔1分钟，请勿频繁提交');
        }

        //保存更新时间
        $voice->update_time = time();
        $voice->save();


        if(!empty($voice->voice_urls)){
            $request = [
                'name'      => $voice->name,
                'gender'    => $voice->gender,
                'audio_url' => $voice->voice_urls
            ];

            switch ($voice->model_version) {
                case 1:
                    $scene = self::VOICE_TRAINING;
                    break;
                case 2:
                    $scene = self::VOICE_TRAINING_PRO;
                    break;
                case 4:
                    $scene = self::VOICE_TRAINING_YM;
                    break;
                case 6:
                    $scene = self::VOICE_TRAINING_YMT;
                    break;
                case 7:
                    $scene = self::VOICE_TRAINING_CHANJING;
                    break;
                default:
                    $scene = self::VOICE_TRAINING_PRO;
                    break;
            }
            $result = self::requestUrl($request, $scene, $voice->user_id, $voice->task_id);
        }else{
            $result = [];
        }


        if (!empty($result) && isset($result['id'])) {

            $voice->voice_id  = $result['id'];
            $voice->status    = $voice->model_version == 2 ? 1 : 0;
            $voice->save();

            // 更新音频
            HumanAudio::where('task_id', $voice->task_id)->update([
                'voice_id' => $result['id']
            ]);

            self::$returnData = $voice->toArray();
        } else {
            self::setError('重试失败');
            return false;
        }
        return true;
    }

    /**
     * @desc 音色列表
     * @param $data
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:25
     * @author dagouzi
     */
    public static function voiceLists($data): array
    {
        //TODO 新增分页
        $pageNo = ($data['page_no'] - 1) * $data['page_size'];
        $pageSize = $data['page_size'];
        $name = $data['name'] ?? '';

        // if (empty($data['model_version'])){
        //     $modelVersion = '';
        // }else{
        //     $modelVersion = json_decode($data['model_version'],true);
        //     $modelVersion = is_array($modelVersion) ? $modelVersion : [(int)$modelVersion];
        // }

        $modelVersion = $data['model_version'] ?? '';

        $status = $data['status'] ?? '';



        $builtin = $data['builtin'] ?? 3;
        $type = $data['type'] ?? '';
        $result = [];
        $count = 0;
        if (in_array($builtin,[1,3])) {
            $result = HumanVoice::where(['user_id' => self::$uid])
                ->limit($pageNo, $pageSize)
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($modelVersion, function ($query) use ($modelVersion) {
                    $query->where('model_version', 'in' , explode(',', $modelVersion));
                })
                ->when($type!= "", function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($status != "", function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->order('create_time', 'desc')
                ->select()->each(function ($item) {
                    $item->builtin = 1;
                })
                ->toArray();

            $count = HumanVoice::where(['user_id' => self::$uid])
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($type != "", function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($modelVersion, function ($query) use ($modelVersion) {
                    $query->where('model_version', 'in' ,  explode(',', $modelVersion));
                })
                ->when($status != "", function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->count();
        }

        if (in_array($builtin,[0,3])) {
            $voice = HumanVoice::getModelList();
            if ($voice) {
                foreach ($voice['voice'] as $key => &$v) {
                    $v['builtin'] = 0;
                    if ($name != '' && strpos($v['name'], $name) === false) {
                        unset($voice['voice'][$key]); // 移除匹配的元素
                    }
                };
                $result = array_merge($voice['voice'], $result);
            }
            $count = count( $result);
        }

        $data = [
            'lists' => $result,
            'count' =>  $count,
            'page_no' => $data['page_no'],
            'page_size' => $data['page_size'],
        ];
        return $data;
    }

    /**
     * @desc 删除音色
     * @param $data
     * @return bool
     * @date 2024/9/28 18:31
     * @author dagouzi
     */
    public static function voiceDelete($data)
    {
        try {

            if (is_string($data['id'])) {
                HumanVoice::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                HumanVoice::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 创建音频
     * @param $data
     * @return bool
     * @date 2024/9/28 18:36
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author dagouzi
     */
    public static function createAudio($data)
    {
        // 检查余额
        if ($data['model_version'] == 1) {
            TokenLogService::checkToken(self::$uid, 'human_audio');
        }elseif ($data['model_version'] == 4){
            TokenLogService::checkToken(self::$uid, 'human_audio_ym');
        }elseif ($data['model_version'] == 6){
            TokenLogService::checkToken(self::$uid, 'human_audio_ymt');
        }elseif ($data['model_version'] == 7){
            TokenLogService::checkToken(self::$uid, 'human_audio_chanjing');
        }else {
            TokenLogService::checkToken(self::$uid, 'human_audio_pro');
        }
        $msg = $data['msg'] ?? '';
        $name = $data['name'] ?? '';
        $voice_id = $data['voice_id'] ?? '';
        $model_version = $data['model_version'] ?? '';

        if (empty($msg) || !in_array($model_version, [1, 2, 4,5,6,7]) || empty($voice_id)) {
            message('参数错误');
        }

        //字数超过150字节
        if ($model_version == 1 && mb_strlen($msg) > 150) {
            message('字数不能超过150字节');
        }

        if ($model_version == 2 && mb_strlen($msg) > 30000) {
            message('字数不能超过30000字节');
        }

        $request = [
            'msg' => $msg,
            'voice_id' => $voice_id
        ];

        // 检查音色ID
        $voice = HumanVoice::where('voice_id', $voice_id)->findOrEmpty();
        if ($voice->status != 1) {
            self::setError('请等待音色创建完成');
            return false;
        }

        $taskId = generate_unique_task_id();

        switch ($model_version) {
            case 1:
                $scene = self::AUDIO_TRAINING;
                break;
            case 2:
                $scene = self::AUDIO_TRAINING_PRO;
                break;
            case 4:
                $scene = self::AUDIO_TRAINING_YM;
                break;
            case 6:
                $scene = self::AUDIO_TRAINING_YMT;
                break;
            case 7:
                $scene = self::AUDIO_TRAINING_CHANJING;
                break;
            default:
                $scene = self::AUDIO_TRAINING_PRO;
                break;
        }

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);

        if (!empty($result) && isset($result['url'])) {
            self::$returnData = $result;
            $addData = [
                'user_id' => self::$uid,
                'status' => 0,
                'name' => $name,
                'msg' => $msg,
                'voice_id' => $voice_id,
                'model_version' => $model_version,
                'task_id' => $taskId,
                'audio_id' => $result['id']
            ];

            if ($model_version == 2) {
                $addData['url'] = FileService::downloadFileBySource($result['url'], 'audio');
            }

            HumanAudio::create($addData);
        } else {
            self::setError('合成失败');
            return false;
        }
        return true;
    }

    /**
     * @desc 音频重试
     * @param $data
     * @return bool
     * @throws \Exception
     * @date 2024/9/28 17:46
     * @author dagouzi
     */
    public static function audioRetry(int $id)
    {
        $audio = HumanAudio::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
        if ($audio->isEmpty()) {

            message('音频不存在');
        }

        if (HumanVoice::where('voice_id', $audio->voice_id)->where('status', 1)->count() == 0) {
            self::setError('请等待音色创建完成');
            return false;
        }

        //状态不对
        if ($audio->status != 2) {
            message('当前任务不是失败状态，请勿提交');
        }

        //提交时间间隔1分钟
        if (strtotime($audio->update_time) > strtotime('-1 minute')) {
            message('任务训练间隔1分钟，请勿频繁提交');
        }

        //保存更新时间
        $audio->update_time = time();
        $audio->save();

        $request = [
            'msg'       => $audio->msg,
            'voice_id'  => $audio->voice_id
        ];

        switch ($audio->model_version)
        {
            case 1:
                $scene = self::AUDIO_TRAINING;
                break;
            case 2:
                $scene = self::AUDIO_TRAINING_PRO;
                break;
            case 4:
                $scene = self::AUDIO_TRAINING_YM;
                break;
            case 6:
                $scene = self::AUDIO_TRAINING_YMT;
                break;
            case 7:
                $scene = self::AUDIO_TRAINING_CHANJING;
                break;
            default:
                $scene = self::AUDIO_TRAINING_PRO;
                break;
        }

        $result = self::requestUrl($request, $scene, $audio->user_id, $audio->task_id);

        if ($audio->model_version == 2 && isset($result['url']) && $result['url']) {
            $audio->url = FileService::downloadFileBySource($result['url'], 'audio');
            $audio->status = 1;
            $audio->save();

            // 更新视频
            HumanVideoTask::where('task_id', $audio->task_id)->update([
                'audio_url' => FileService::setFileUrl($audio->url)
            ]);
        } else if (isset($result['id']) && $result['id']) {
            $audio->audio_id = $result['id'];
            $audio->status = 0;
            $audio->save();
        } else {
            self::setError('重试失败');
            return false;
        }

        return true;
    }


    /**
     * @desc 视频重试
     * @param $data
     * @return bool
     * @throws \Exception
     * @date 2024/9/28 17:46
     * @author dagouzi
     */
    public static function videoRetry(int $id)
    {
        $video = HumanVideoTask::where('id', $id)->where('user_id', self::$uid)->findOrEmpty();
        if ($video->isEmpty()) {

            message('视频不存在');
        }

        if ($video->anchor_id == "") {
            self::setError('请等待形象创建完成');
            return false;
        }

        if ($video->audio_url == "") {
            self::setError('请等待音频创建完成');
            return false;
        }

        //状态不对
        if ($video->status != 2) {
            message('当前任务不是失败状态，请勿提交');
        }

        //提交时间间隔1分钟
        if (strtotime($video->update_time) > strtotime('-1 minute')) {
            message('任务训练间隔1分钟，请勿频繁提交');
        }

        //保存更新时间
        $video->update_time = time();
        $video->save();

        $request = [
            'name'       => $video->name,
            'avatar_id'  => $video->anchor_id,
            'video_url'  => $video->upload_video_url,
            'audio_url'  => $video->audio_url
        ];

        switch ($video->model_version)
        {
            case 1:
                $scene = self::VIDEO_TRAINING;
                break;
            case 2:
                $scene = self::VIDEO_TRAINING_PRO;
                break;
            case 4:
                $scene = self::VIDEO_TRAINING_YM;
                break;
            case 6:
                $scene = self::VIDEO_TRAINING_YMT;
                break;
            case 7:
                $scene = self::VIDEO_TRAINING_CHANJING;
                break;
            default:
                $scene = self::VIDEO_TRAINING_PRO;
                break;
        }

        $result = self::requestUrl($request, $scene, $video->user_id, $video->task_id);

        if (!empty($result) && isset($result['id'])) {

            $video->result_id = $result['id'];
            $video->remark    = "";
            $video->status    = 0;
            $video->save();
            self::$returnData = $video->toArray();
        } else {
            self::setError('重试失败');
            return false;
        }
        return true;
    }


    /**
     * @desc 音频列表
     * @param $data
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:25
     * @author dagouzi
     */
    public static function audioLists($data): array
    {
        //TODO 新增分页
        $pageNo = ($data['page_no'] - 1) * $data['page_size'];
        $pageSize = $data['page_size'];
        $name = $data['name'] ?? '';
        $modelVersion = $data['model_version'] ?? '';
        $status = $data['status'] ?? '';
        $type = $data['type'] ?? '';
        $result = HumanAudio::where(['user_id' => self::$uid])
            ->limit($pageNo, $pageSize)
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($type != "", function ($query) use ($type) {
                $query->where('type',  $type );
            })
            ->when($modelVersion, function ($query) use ($modelVersion) {
                $query->where('model_version', $modelVersion);
            })
            ->when($status != "", function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->order('create_time', 'desc')
            ->select()
            ->toArray();

        $data = [
            'lists' => $result,
            'count' => HumanAudio::where(['user_id' => self::$uid])
                ->when($modelVersion, function ($query) use ($modelVersion) {
                    $query->where('model_version', $modelVersion);
                })
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->when($type != "", function ($query) use ($type) {
                    $query->where('type',  $type );
                })
                ->when($status != "", function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->count(),
            'page_no' => $data['page_no'],
            'page_size' => $data['page_size'],
        ];
        return $data;
    }

    /**
     * @desc 删除音频
     * @param $data
     * @return bool
     * @date 2024/9/28 18:31
     * @author dagouzi
     */
    public static function audioDelete($data)
    {
        try {

            if (is_string($data['id'])) {
                HumanAudio::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                HumanAudio::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * @desc 视频列表
     * @return array
     * @date 2024/9/28 18:25
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function videoLists(array $data)
    {
        $modelVersion = $data['model_version'] ?? '';
        $name = $data['name'] ?? '';
        $status = $data['status'] ?? '';
        $pageNo = ($data['page_no'] - 1) * $data['page_size'];
        $pageSize = $data['page_size'];
        $result = HumanVideoTask::where(['user_id' => self::$uid])
            ->when($modelVersion, function ($query) use ($modelVersion) {
                $query->where('model_version', $modelVersion);
            })
            ->when($name, function ($query) use ($name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->when($status != "", function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->order('create_time', 'desc')
            ->limit($pageNo, $pageSize)
            ->select()
            ->toArray();

        $data = [
            'lists' => $result,
            'count' => HumanVideoTask::where(['user_id' => self::$uid])
                ->when($modelVersion, function ($query) use ($modelVersion) {
                    $query->where('model_version', $modelVersion);
                })
                ->when($name, function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%');
                })
                ->count(),
            'page_no' => $data['page_no'],
            'page_size' => $data['page_size'],
        ];

        return $data;
    }

    /**
     * @desc 删除视频
     * @param $data
     * @return bool
     * @date 2024/9/28 18:31
     * @author dagouzi
     */
    public static function videoDelete($data)
    {
        try {

            if (is_string($data['id'])) {
                HumanVideoTask::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                HumanVideoTask::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * @desc 视频信息定时任务
     * @return bool
     */
    public static function videoInfoCron(): bool
    {

        try {
            HumanVideoTask::where('status', 0)
                ->where('result_id', '<>', '')
                ->where('model_version', '=', 2)
                ->order('tries', 'asc')
                ->limit(3)
                ->select()
                ->each(function ($item) {
                    try {
                        $response = \app\common\service\ToolsService::Human()->detailPro([
                            'id' => $item['result_id']
                        ]);
                        //阿里极速版  2
                        //  Log::write('阿里获取视频结果' . json_encode($response));
                        if (!empty($response['data']['url'])) {
                            $item->status = 1;
                            $item->tries = $item['tries'] + 1;
                            $item->result_url = FileService::downloadFileBySource($response['data']['url'], 'video');
                            $item->save();
                            return true;
                        }else{
                            $item->tries = $item['tries'] + 1;
                            $item->save();
                            return true;
                        }
                    } catch (\think\exception\HttpResponseException $e) {
                        Log::write('数字人视频保存失败' .$item['tries'].'----' . $e->getResponse()->getData()['msg']);
                        $item->remark = $e->getResponse()->getData()['msg'] ?? '';
                        $item->tries = $item['tries'] + 1;
                        $item->status = 2;
                        $item->save();
                        //退费
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO)->where('action', 1)->where('task_id', $item->task_id)->count() == 0) {

                            $points = UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO)->where('task_id', $item->task_id)->value('change_amount') ?? 0;

                            AccountLogLogic::recordUserTokensLog(false, $item->user_id, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO, $points, $item->task_id);
                        }

                        return true;
                    }
                    return true;
                });
            return true;
        } catch (\Exception $e) {
            Log::write('视频信息定时任务失败' . $e->getMessage());
            return true;
        }
    }

    public static function videoWjInfoCron(): bool
    {
        try {
            print_r("\n查询标准版数字人视频任务结果\n");
            HumanVideoTask::where('status', 0)
                ->where('result_id', '<>', '')
                ->where('model_version', '=', 1)
                ->order('tries', 'asc')
                ->limit(3)
                ->select()
                ->each(function ($item) {
                    try {
                        $response = \app\common\service\ToolsService::Human()->getWjDetail([
                            'id' => $item['result_id'],
                            'model_version' => 1,
                            'type' => 4,
                        ]);
                        $task_info = $response['data']['task_info'] ?? [];

                        if(!isset($task_info['current_status'])){
                            return true;
                        }

                        if($task_info['current_status'] === 'init'){
                            return true;

                        }
                        if($task_info['current_status'] === 'fail'){
                            $item->status = 2;
                            $item->tries = $item['tries'] + 1;
                            $item->save();
                            //退费
                            self::refundTokens($item->user_id, $item->result_id, $item->task_id, 'human_video');
                            return true;
                        }
                        if($task_info['current_status'] === 'success'){
                            $item->status = 1;
                            $item->result_url = FileService::downloadFileBySource($task_info['result'], 'video');
                            $item->save();
                        }

                        if($item->status == 1 && $item->automatic_clip == 1&& $item->clip_status == 1){
                            $unit = TokenLogService::checkToken($item->user_id, 'video_clip');
                            $result_url = FileService::getFileUrl($item->result_url);
                            $params = [
                                'video_id' => $item->id,
                                'task_id' => $item->task_id,
                                'clip_type' => $item->clip_type,
                                'music_url' => $item->music_url,
                                'music_type' => $item->music_type,
                                'result_url' => $result_url,
                                'msg' => $item->msg,
                                'type' => 1,
                            ];

                            $response = \app\common\service\ToolsService::Sv()->clip($params);
                            if (isset($response['code']) && $response['code'] == 10000) {

                                $points = $unit;

                                if ($points > 0) {
                                    $extra = [
                                        '场景' => '数字人 - 标准版'
                                    ];
                                    //token扣除
                                    User::userTokensChange($item->user_id, $points);

                                    //记录日志
                                    AccountLogLogic::recordUserTokensLog(true, $item->user_id, AccountLogEnum::TOKENS_DEC_VIDEO_CLIP, $points,  $item->task_id, $extra);
                                }
                                $item->clip_status = 2;
                            }
                            $item->save();
                        }
                        return true;
                    } catch (\think\exception\HttpResponseException $e) {
                        Log::write('wj数字人视频保存失败' .$item['tries'].'----' . $e->getResponse()->getData()['msg']);
                        $item->remark = $e->getResponse()->getData()['msg'] ?? '';
                        $item->tries = $item['tries'] + 1;
                        $item->status = 2;
                        $item->save();
                        //退费
                        //查询是否已返还
                        if (UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO)->where('action', 1)->where('task_id', $item->task_id)->count() == 0) {

                            $points = UserTokensLog::where('user_id', $item->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO)->where('task_id', $item->task_id)->value('change_amount') ?? 0;

                            AccountLogLogic::recordUserTokensLog(false, $item->user_id, AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO, $points, $item->task_id);
                        }

                        return true;
                    }
                    return true;
                });
            return true;
        } catch (\Exception $e) {
            Log::write('wj视频信息定时任务失败' . $e->getMessage());
            return true;
        }
    }
    

    /**
     * @desc 视频定时任务
     * @return bool|void
     * @date 2024/10/2 10:36
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author dagouzi
     */
    public static function videoTaskCron(string $taskId = '')
    {
        try {
            if ($taskId == '') {
                // 请求30分钟内，还处于0状态的任务
                HumanVideoTask::where('status', 0)
                    ->where('result_id', '')
                    ->where('create_time', '<=', strtotime('-30 minutes'))
                    ->select()
                    ->each(function ($item) {
                        self::setTimeoutTask($item->task_id);
                    });
            }
            // 第一步：获取任务列表
            $taskModel = HumanVideoTask::where('status', 0)
                ->where('result_id', '')
                ->where('tries', '<', 3)
                ->limit(3);

            if ($taskId) {

                $taskModel = $taskModel->where('task_id', $taskId);
            }
            $modellist = HumanVoice::getModelList();

            //第二步遍历任务
            $taskModel->select()->each(function ($item) {
                // //如果存在失败，且还没到执行时间（1分钟后执行）
                if ($item->tries >= 1 && strtotime($item->update_time) > strtotime('-1 minute')) {

                    return true;
                }
                try {
                    $tries = $item->tries;
                    //step1 形象
                    $anchor = HumanAnchor::where('user_id', $item['user_id'])->where('anchor_id', $item->anchor_id)->findOrEmpty();
                    // 如果形象不存在，则创建形象
                    if ($anchor->isEmpty()) {

                        $anchor = HumanAnchor::create([
                            'task_id' => $item->task_id,
                            'model_version' => $item->model_version,
                            'height' => $item->height,
                            'width' => $item->width,
                            'name' => $item->anchor_name,
                            'gender' => $item->gender,
                            'status' => 0,
                            'pic' => $item->pic,
                            'url' => $item->upload_video_url,
                            'user_id' => $item['user_id']
                        ]);
                    }
                    
                    if ($anchor->anchor_id == ""){
                        switch ($item->model_version) {
                            case 1:
                                $scene = self::AVATAR_TRAINING;
                                break;
                            case 2:
                                $scene = self::AVATAR_TRAINING_PRO;
                                break;
                            case 4:
                                $scene = self::AVATAR_TRAINING_YM;
                                break;
                            case 6:
                                $scene = self::AVATAR_TRAINING_YMT;
                                break;
                            case 7:
                                $scene = self::AVATAR_TRAINING_CHANJING;
                                break;
                            default:
                                $scene = self::AVATAR_TRAINING_PRO;
                                break;
                        }
                        //请求形象接口
                        $response = self::requestUrl([
                            'name' => $anchor->name,
                            'gender' => $anchor->gender,
                            'video_url' => $anchor->url,
                        ], $scene, $item->user_id, $item->task_id);
                        if (empty($response['id'])) {
                            $item->remark = $response['message'] ?? '形象创建失败';
                            $item->save();
                            message('形象创建失败');
                        }

                        if($item->model_version == 1 && $response['status'] == 'completed'){
                            $anchor->status = 1;
                        }

                        if (in_array($item->model_version,[2,4,6])) {

                            $anchor->status = 1;
                        }

                        $anchor->anchor_id = $response['id'];
                        $anchor->save();

                        // 保存形象id
                        $item->anchor_id = $anchor->anchor_id;
                        $item->save();
                    }
                    // 形象还没有训练完成
                    if ($anchor->status != 1) {

                        return true;
                    }
                    // 文案驱动
                    if ($item->audio_type == 1) {
                        //step2 音色
                        $voice = HumanVoice::where('user_id', $item['user_id'])->where('voice_id', $item->voice_id)->findOrEmpty();
                        $voiceres = false;
                        // 如果音色不存在，则创建音色
                        if ($voice->isEmpty()) {
                            $BuiltInVoice = HumanVoice::getBuiltInVoiceList($item->model_version);
                            if (!in_array($item->voice_id, $BuiltInVoice)) {
                                $voice = HumanVoice::create([
                                    'task_id' => $item->task_id,
                                    'model_version' => $item->model_version,
                                    'name' => $item->voice_name ? $item->voice_name : $item->name,
                                    'gender' => $item->gender,
                                    'voice_urls' => $item->upload_video_url,
                                    'user_id' => $item['user_id']
                                ]);
                                $voiceres = false;

                            }else{
                                $voice->voice_id = $item->voice_id;
                                $voiceres = true;
                            }
                        }


                        // 如果没有训练，请求训练
                        if ($voice->voice_id == "" && !$voiceres) {
                            switch ($item->model_version) {
                                case 1:
                                    $scene = self::VOICE_TRAINING;
                                    break;
                                case 2:
                                    $scene = self::VOICE_TRAINING_PRO;
                                    break;
                                case 4:
                                    $scene = self::VOICE_TRAINING_YM;
                                    $item->upload_video_url = FileService::getFileUrl($item->upload_video_url);
                                    break;
                                case 6:
                                    $scene = self::VOICE_TRAINING_YMT;
                                    $item->upload_video_url = FileService::getFileUrl($item->upload_video_url);
                                    break;
                                case 7:
                                    $scene = self::VOICE_TRAINING_CHANJING;
                                    break;
                                default:
                                    $scene = self::VOICE_TRAINING_PRO;
                                    break;
                            }
                            $response = self::requestUrl([
                                'name' => $voice->name,
                                'gender' => $voice->gender,
                                'is_video' => true,
                                'audio_url' => $item->upload_video_url
                            ], $scene, $item->user_id, $item->task_id);
                            if (empty($response['id'])) {
                                $item->remark = $response['message'] ?? '音色创建失败';
                                $item->save();
                                message('音色创建失败');
                            }

                            if ($item->model_version == 2) {

                                $voice->status = 1;
                            }

                            $voice->voice_id = $response['id'];
                            $voice->save();

                            $item->voice_id = $response['id'];
                            $item->save();
                            if(in_array($item->model_version,[4,6])){
                                $humantask = HumanTask::where([
                                    'user_id'=> $item['user_id'],
                                    'video_task_id' => $item->id,
                                    'task_id' => $item->task_id,
                                    'type' => 2,
                                    'model_version' => $item->model_version,
                                ])->findOrEmpty();

                                // 如果音色不存在，则创建音色
                                if ($humantask->isEmpty()) {

                                    HumanTask::create([
                                        'user_id' => $item['user_id'],
                                        'video_task_id' => $item->id,
                                        'task_id' => $item->task_id,
                                        'model_version' => $item->model_version,
                                        'data_id'=> $response['id'],
                                        'type' => 2,
                                    ]);

                                    $item->tries = 6;
                                    $item->save();
                                }
                            }
                        }
                        // 音色还没有训练完成
                        if ($voice->status != 1 && !$voiceres) {

                            return true;
                        }
                        if ($item->model_version == 7) {

                            $scene = self::VIDEO_TRAINING_CHANJING;
                            $response = self::requestUrl([
                                'name'      => $item->name,
                                'msg'       => $item->msg,
                                'width'     => $item->width,
                                'height'    => $item->height,
                                'avatar_id' => $item->anchor_id,
                                'voice_id'  => $item->voice_id,
                                'video_url' => $item->upload_video_url,
                                'audio_url' => $item->audio_url
                            ], $scene, $item->user_id, $item->task_id);
                            // Log::write( $item->task_id .'高级版视频合成' . json_encode($response));
                            if (empty($response['id'])) {
                                $item->remark = $response['message'] ?? '视频创建失败';
                                $item->save();
                                message('视频创建失败');
                            }
                            $item->voice_name = $item->voice_name ? $item->voice_name : $item->name;
                            $item->result_id = $response['id'];
                            $item->save();
                            return ;
                        }
                        //step3 音频
                        $audio = HumanAudio::where('user_id', $item['user_id'])->where('task_id', $item->task_id)->findOrEmpty();

                        // 如果音频不存在，则创建音频
                        if ($audio->isEmpty()) {

                            $audio = HumanAudio::create([
                                'user_id' => $item['user_id'],
                                'task_id' => $item->task_id,
                                'model_version' => $item->model_version,
                                'name' => $item->name,
                                'msg' => $item->msg,
                                'voice_id' => $voice->voice_id
                            ]);
                        }

                        // 如果没有训练，请求训练
                        if ($audio->audio_id == "") {
                            switch ($item->model_version) {
                                case 1:
                                    $scene = self::AUDIO_TRAINING;
                                    break;
                                case 2:
                                    $scene = self::AUDIO_TRAINING_PRO;
                                    break;
                                case 4:
                                    $scene = self::AUDIO_TRAINING_YM;
                                    break;
                                case 6:
                                    $scene = self::AUDIO_TRAINING_YMT;
                                    break;
                                default:
                                    $scene = self::AUDIO_TRAINING_PRO;
                                    break;
                            }
                            // 请求合成音频
                            $response = self::requestUrl([
                                'msg' => $item->msg,
                                'voice_id' => $voice->voice_id
                            ], $scene, $item->user_id, $item->task_id);
                            //      Log::write( $item->task_id .'高级版音频合成' . json_encode($response));
                            if ($item->model_version == 2) {

                                if (empty($response['url'])) {

                                    message('音频创建失败');
                                }

                                $audio->audio_id = uniqid();
                                $audio->url = FileService::downloadFileBySource($response['url'], 'audio');
                                $audio->status = 1;
                                $audio->save();
                            }  elseif(in_array($item->model_version,[4,6])){
                                if (empty($response['id'])) {

                                    message('音频创建失败');
                                }

                                $audio->audio_id = $response['id'];
                                $audio->status = 0;
                                $audio->save();
                                $humantask = HumanTask::where([
                                    'task_id' => $item->task_id,
                                    'video_task_id' => $item->id,
                                    'model_version' => $item->model_version,
                                    'type' => 3,
                                    'user_id'=> $item['user_id'],
                                ])->findOrEmpty();

                                // 如果声音不存在，则创建
                                if ($humantask->isEmpty()) {

                                    HumanTask::create([
                                        'task_id' => $item->task_id,
                                        'video_task_id' => $item->id,
                                        'model_version' => $item->model_version,
                                        'data_id'=> $response['id'],
                                        'type' => 3,
                                        'user_id' => $item['user_id']
                                    ]);

                                    $item->tries = 6;
                                    $item->save();
                                }
                            }else {

                                if (empty($response['id'])) {

                                    message('音频创建失败');
                                }

                                $audio->audio_id = $response['id'];
                                $audio->save();
                            }
                        }

                        // 音频还没有训练完成
                        if ($audio->status != 1) {

                            return true;
                        }

                        // 保存音频url
                        $item->audio_url = $audio->url;
                        $item->save();

                    }

                    //最终合成视频 有形象了，有音频了
                    if ($item->anchor_id != "" && $item->audio_url != "") {

                        switch ($item->model_version) {
                            case 1:
                                $scene = self::VIDEO_TRAINING;
                                break;
                            case 2:
                                $scene = self::VIDEO_TRAINING_PRO;
                                break;
                            case 4:
                                $scene = self::VIDEO_TRAINING_YM;
                                break;
                            case 6:
                                $scene = self::VIDEO_TRAINING_YMT;
                                break;
                            case 7:
                                $scene = self::VIDEO_TRAINING_CHANJING;
                                break;
                            default:
                                $scene = self::VIDEO_TRAINING_PRO;
                                break;
                        }

                        $response = self::requestUrl([
                            'name'      => $item->name,
                            'avatar_id' => $item->anchor_id,
                            'video_url' => $item->upload_video_url,
                            'audio_url' => $item->audio_url,
                            'msg'       => $item->msg,
                            'width'     => $item->width,
                            'height'    => $item->height,
                        ], $scene, $item->user_id, $item->task_id);
                        // Log::write( $item->task_id .'高级版视频合成' . json_encode($response));
                        if (empty($response['id'])) {

                            message('视频创建失败');
                        }

                        $item->result_id = $response['id'];
                        $item->save();
                    }

                    return true;
                } catch (\think\exception\HttpResponseException $e) {
                    $item->tries = $tries + 1;
                    $item->save();
                    //失败3次 更新任务失败
                    if ($item->tries >= 3) {
                        $remark = $e->getResponse()->getData()['msg'] ?? '';
                        self::updateFailTask($item->task_id, $remark);
                    }
                    return true;
                }
            });
        } catch (\Exception $e) {
            Log::channel('human')->write('数字人错误:'.$e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @desc 数字人定时任务
     * @return bool
     */
    public static function humanInfoCron(): bool
    {
        try {
            HumanTask::where('status', 0)
                ->whereIn('model_version',[4,6])
                ->where('tries', '<', 8)
                ->order('tries', 'asc')
                ->limit(3)
                ->select()
                ->each(function ($item) {
                    try {
                        $methodMap = [
                            4 => 'detailYm',
                            6 => 'detailYmt',
                        ];

                        $method = $methodMap[$item['model_version']] ?? 'detailYmt';
                        if($item['type'] == 2){

                            $response = \app\common\service\ToolsService::Human()->$method([
                                'type' => $item['type'],
                                'id' => $item['data_id']
                            ]);
                            // Log::write('高级版获取音色结果' . json_encode($response));
                            if (isset($response['data']['status']) && $response['data']['status'] == 3) {
                                $item->result_id = $response['data']['train_id'];
                                $item->status = 1;
                                $item->tries = $item['tries'] + 1;
                                $item->result_url = $response['data']['demo_audio'];
                                $item->upload_url = FileService::downloadFileBySource($response['data']['demo_audio'], 'audio');
                                $item->save();

                                $task = HumanVoice::where([
                                    'user_id'=> $item['user_id'],
                                    'task_id'=> $item['task_id'],
                                    'status' =>0
                                ])->update([
                                    'status' =>1,
                                    'voice_urls'=> $item->upload_url
                                ]);
                                $tt = HumanVideoTask::where([
                                    'user_id'=> $item['user_id'],
                                    'task_id'=> $item['task_id'],
                                    'status' =>0
                                ])->update([
                                    'tries'=> 1
                                ]);
                                return true;
                            }else{
                                $item->tries = $item['tries'] + 1;
                                $item->save();
                                return true;
                            }
                        }


                        if($item['type'] == 3){
                            $response = \app\common\service\ToolsService::Human()->$method([
                                'type' => $item['type'],
                                'id' => $item['data_id']
                            ]);
                            //阿里极速版  2
                            if (isset($response['data']['status']) && $response['data']['status'] == 3) {
                                $upload_url = FileService::downloadFileBySource($response['data']['speech_url'], 'audio');
                                $item->result_id = $response['data']['id'];
                                $item->status = 1;
                                $item->tries = $item['tries'] + 1;
                                $item->result_url = $response['data']['speech_url'];
                                $item->upload_url =  $upload_url;
                                $item->save();

                                $task = HumanAudio::where([
                                    'user_id'=> $item['user_id'],
                                    'task_id'=> $item['task_id'],
                                    'status' =>0,
                                    'audio_id'=> $response['data']['task_id'],
                                ])->update([
                                    'status' =>1,
                                    'url' => $upload_url
                                ]);
                                $tt = HumanVideoTask::where([
                                    'user_id'=> $item['user_id'],
                                    'task_id'=> $item['task_id'],
                                    'status' =>0
                                ])->update([
                                    'tries'=> 1,
                                    'audio_url' => $upload_url

                                ]);
                                return true;
                            }else{
                                $item->tries = $item['tries'] + 1;
                                $item->save();
                                return true;
                            }
                        }

                    } catch (\think\exception\HttpResponseException $e) {
                        Log::write('数字人保存失败' .$item['tries'].'----' . $e->getResponse()->getData()['msg']);
                        $item->remark = $e->getResponse()->getData()['msg'] ?? '';
                        $item->tries = $item['tries'] + 1;
                        $item->status = 2;
                        $item->save();
                        if($item->type == 2){
                            if($item->model_version == 4){
                                $scene = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM;
                            }else{
                                $scene = AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT;
                            }
                            //查询是否已返还
                            if (UserTokensLog::where('user_id', $item->user_id)->where('change_type', $scene)->where('action', 1)->where('task_id', $item->task_id)->count() == 0) {

                                $points = UserTokensLog::where('user_id', $item->user_id)->where('change_type', $scene)->where('task_id', $item->task_id)->value('change_amount') ?? 0;

                                AccountLogLogic::recordUserTokensLog(false, $item->user_id, $scene, $points, $item->task_id);
                            }
                        }else{
                            if($item->model_version == 4){
                                $scene = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM;
                            }else{
                                $scene = AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT;
                            }
                            if (UserTokensLog::where('user_id', $item->user_id)->where('change_type', $scene)->where('action', 1)->where('task_id', $item->task_id)->count() == 0) {

                                $points = UserTokensLog::where('user_id', $item->user_id)->where('change_type',  $scene)->where('task_id', $item->task_id)->value('change_amount') ?? 0;

                                AccountLogLogic::recordUserTokensLog(false, $item->user_id,  $scene, $points, $item->task_id);
                            }
                        }
                        //退费


                        return true;
                    }
                    return true;
                });
            return true;
        } catch (\Exception $e) {
            Log::write('视频信息定时任务失败' . $e->getMessage());
            return true;
        }
    }


    /**
     * @desc 删除失败任务
     * @param $taskId
     * @return void
     * @date 2024/10/2 10:43
     * @author dagouzi
     */
    private static function deleteFailTask(string $taskId): void
    {
        //删除音频失败任务, status=0,2  
        // TODO 不删除  让用户手动重试
        // HumanAudio::where('task_id', $taskId)->whereIn('status', [0, 2])->findOrEmpty()->delete();

        // //删除音色失败任务, status=0,2
        // HumanVoice::where('task_id', $taskId)->whereIn('status', [0, 2])->findOrEmpty()->delete();

        // //删除形象失败任务, status=0,2
        // HumanAnchor::where('task_id', $taskId)->whereIn('status', [0, 2])->findOrEmpty()->delete();

        // //删除视频失败任务, status=0,2
        // HumanVideoTask::where('task_id', $taskId)->whereIn('status', [0, 2])->findOrEmpty()->delete();
    }


    /**
     * @desc 删除失败任务
     * @param string $taskId
     * @param string $remark
     * @return void
     * @date 2024/10/2 10:43
     * @author dagouzi
     */
    private static function updateFailTask(string $taskId, string $remark): void
    {
        //更新音频失败任务, status=0,2
        HumanAudio::where('task_id', $taskId)->where('status', 0)->update([
            'status' => 2,
        ]);

        //更新音色失败任务, status=0,2
        HumanVoice::where('task_id', $taskId)->where('status', 0)->update([
            'status' => 2,
        ]);

        //更新形象失败任务, status=0,2
        HumanAnchor::where('task_id', $taskId)->where('status', 0)->update([
            'status' => 2,
        ]);

        //更新视频失败任务, status=0,2
        HumanVideoTask::where('task_id', $taskId)->where('status', 0)->update([
            'status' => 2,
            'remark' => $remark
        ]);
    }

    /**
     * @desc 超时任务
     * @param $taskId
     * @return void
     * @date 2024/10/2 10:43
     * @author dagouzi
     */
    private static function setTimeoutTask(string $taskId): void
    {
        $item = HumanVideoTask::where('task_id', $taskId)->findOrEmpty()->toArray();

        // 任务超时  
        $Audio = HumanAudio::where('task_id', $taskId)->whereIn('status', 0)->update(['status' => 2]);
        if ($Audio) {
            switch ($item['model_version'])
            {
                case  1:
                    $scene = 'human_audio';
                    break;
                case  2:
                    $scene = 'human_audio_pro';
                    break;
                case  4:
                    $scene = 'human_audio_ym';
                    break;
                case  6:
                    $scene = 'human_audio_ymt';
                    break;
                default:
                    $scene = 'human_audio';
                    break;
            }
            self::refundTokens($item['user_id'], $item['audio_id'], $item['task_id'], $scene);
        }

        $Voice =  HumanVoice::where('task_id', $taskId)->whereIn('status', 0)->update(['status' => 2]);
        if ($Voice) {
            switch ($item['model_version'])
            {
                case  1:
                    $scene = 'human_voice';
                    break;
                case  2:
                    $scene = 'human_video_pro';
                    break;
                case  4:
                    $scene = 'human_video_ym';
                    break;
                case  6:
                    $scene = 'human_video_ymt';
                    break;
                default:
                    $scene = 'human_video';
                    break;
            }
            self::refundTokens($item['user_id'], $item['voice_id'], $item['task_id'], $scene);

        }

        $Anchor = HumanAnchor::where('task_id', $taskId)->whereIn('status', 0)->update(['status' => 2]);


        $Video = HumanVideoTask::where('task_id', $taskId)->whereIn('status', 0)->update(['status' => 2, 'remark' => '创作超时']);
        if (!$Audio && !$Voice && !$Anchor && $Video) {
            switch ($item['model_version'])
            {
                case  1:
                    $scene = 'human_video';
                    break;
                case  2:
                    $scene = 'human_video_pro';
                    break;
                case  4:
                    $scene = 'human_video_ym';
                    break;
                case  6:
                    $scene = 'human_video_ymt';
                    break;
                default:
                    $scene = 'human_video';
                    break;
            }
            self::refundTokens($item['user_id'], $item['result_id'], $item['task_id'], $scene);

        }

    }


    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string $taskId
     * @return array
     * @throws \Exception
     */
    private static function requestUrl(array $request, string $scene, int $userId, string $taskId): array
    {

        $requestService = \app\common\service\ToolsService::Human();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::AUDIO_TRAINING => ['human_audio', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO],
            self::AVATAR_TRAINING => ['human_avatar', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR],
            self::VOICE_TRAINING => ['human_voice', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE],
            self::VIDEO_TRAINING => ['human_video', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO],
            self::HUMAN_COPYWRITING => ['human_copywriting', AccountLogEnum::TOKENS_DEC_HUMAN_COPYWRITING],
            self::AUDIO_TRAINING_PRO => ['human_audio_pro', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_PRO],
            self::AVATAR_TRAINING_PRO => ['human_avatar_pro', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_PRO],
            self::VOICE_TRAINING_PRO => ['human_voice_pro', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_PRO],
            self::VIDEO_TRAINING_PRO => ['human_video_pro', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_PRO],
            self::AUDIO_TRAINING_YM => ['human_audio_ym', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM],
            self::AVATAR_TRAINING_YM => ['human_avatar_ym', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YM],
            self::VOICE_TRAINING_YM => ['human_voice_ym', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM],
            self::VIDEO_TRAINING_YM => ['human_video_ym', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM],
            self::AUDIO_TRAINING_YMT => ['human_audio_ymt', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YMT],
            self::AVATAR_TRAINING_YMT => ['human_avatar_ymt', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_YMT],
            self::VOICE_TRAINING_YMT => ['human_voice_ymt', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YMT],
            self::VIDEO_TRAINING_YMT => ['human_video_ymt', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YMT],
            self::AVATAR_TRAINING_CHANJING => ['human_avatar_chanjing', AccountLogEnum::TOKENS_DEC_HUMAN_AVATAR_CHANJING],
            self::VOICE_TRAINING_CHANJING => ['human_voice_chanjing', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_CHANJING],
            self::AUDIO_TRAINING_CHANJING => ['human_audio_chanjing', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_CHANJING],
            self::VIDEO_TRAINING_CHANJING => ['human_video_chanjing', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_CHANJING],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        switch ($scene) {

            case self::AVATAR_TRAINING:

                $response = $requestService->avatarTraining($request);
                break;

            case self::VOICE_TRAINING:

                $response = $requestService->voiceTraining($request);
                break;

            case self::AUDIO_TRAINING:

                $response = $requestService->audioTraining($request);
                break;

            case self::VIDEO_TRAINING:

                $response = $requestService->videoTraining($request);
                break;
            case self::HUMAN_COPYWRITING:

                $response = $requestService->copywritingCreate($request);
                break;
            case self::AVATAR_TRAINING_PRO:

                $response = $requestService->avatarTrainingPro($request);
                break;
            case self::VOICE_TRAINING_PRO:

                $response = $requestService->voiceTrainingPro($request);
                break;
            case self::AUDIO_TRAINING_PRO:

                $response = $requestService->audioTrainingPro($request);
                break;
            case self::VIDEO_TRAINING_PRO:

                $response = $requestService->videoTrainingPro($request);
                break;

            case self::AVATAR_TRAINING_YM:
                $response = $requestService->avatarTrainingYm($request);
                break;
            case self::VOICE_TRAINING_YM:
                $response = $requestService->voiceTrainingYm($request);
                break;
            case self::AUDIO_TRAINING_YM:

                $response = $requestService->audioTrainingYm($request);

                break;
            case self::VIDEO_TRAINING_YM:
                $response = $requestService->videoTrainingYm($request);
                break;
            case self::AVATAR_TRAINING_YMT:
                $response = $requestService->avatarTrainingYmt($request);
                break;
            case self::VOICE_TRAINING_YMT:
                $response = $requestService->voiceTrainingYmt($request);
                break;
            case self::AUDIO_TRAINING_YMT:
                $response = $requestService->audioTrainingYmt($request);
                break;
            case self::VIDEO_TRAINING_YMT:
                $response = $requestService->videoTrainingYmt($request);
                break;
            case self::AVATAR_TRAINING_CHANJING:
                $response = $requestService->avatarTrainingChanjing($request);
                break;
            case self::VOICE_TRAINING_CHANJING:
                $response = $requestService->voiceTrainingChanjing($request);
                break;
            case self::AUDIO_TRAINING_CHANJING:
                $response = $requestService->audioTrainingChanjing($request);
                break;
            case self::VIDEO_TRAINING_CHANJING:
                $response = $requestService->videoTrainingChanjing($request);
                break;

            default:
        }
        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {

            $points = $unit;

            if ($points >= 0) {

                $extra = ['算力单价' => $unit, '实际消耗算力' => $points];

                //合成视频按时长扣费
                if (in_array($scene, [
                    self::AUDIO_TRAINING, self::VIDEO_TRAINING,
                    self::AUDIO_TRAINING_PRO, self::VIDEO_TRAINING_PRO,
                    self::AUDIO_TRAINING_YM, self::VIDEO_TRAINING_YM,
                    self::AUDIO_TRAINING_YMT, self::VIDEO_TRAINING_YMT,
                    self::AUDIO_TRAINING_CHANJING, self::VIDEO_TRAINING_CHANJING
                ])) {

                    $duration = $response['data']['duration'] ?? 1;

                    $points = round($duration * $unit,2);

                    $extra = ['音视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                }

                //token扣除
                User::userTokensChange($userId, $points);

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response['data'] ?? [];
    }

    /**
     * 请求上游接口与计费
     * @param array $request
     * @param string $scene
     * @param int $userId
     * @param string $taskId
     * @return array
     * @throws \Exception
     */

    private static function tokeOutToken(string $scene, int $userId, string $taskId, string $id ,  $type = 0, $response = []): bool
    {

        $requestService = \app\common\service\ToolsService::Human();

        [$tokenScene, $tokenCode] = match ($scene) {
            self::AUDIO_TRAINING_YM => ['human_audio_ym', AccountLogEnum::TOKENS_DEC_HUMAN_AUDIO_YM],
            self::VOICE_TRAINING_YM => ['human_voice_ym', AccountLogEnum::TOKENS_DEC_HUMAN_VOICE_YM],
            self::VIDEO_TRAINING_YM => ['human_video_ym', AccountLogEnum::TOKENS_DEC_HUMAN_VIDEO_YM],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);

        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['id'] = $id;
        $request['type'] = $type;
        $request['now'] = time();

        if($response == []){
            switch ($scene) {

                case self::VOICE_TRAINING_YM:
                    $response = $requestService->detailYm($request);
                    break;
                case self::AUDIO_TRAINING_YM:
                    $response = $requestService->detailYm($request);
                    break;
                case self::VIDEO_TRAINING_YM:
                    $response = $requestService->detailYm($request);
                    break;
                default:
            }
        }

        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {
            if($scene == self::VIDEO_TRAINING_YM){
                $response['data']['status'] = $response['data']['task_status'] ?? 0;
            }

            $points = $unit;
            if ($points > 0 && $response['data']['status'] == 3) {
                $extra = [];
                //合成视频按时长扣费
                if (in_array($scene, [
                    self::AUDIO_TRAINING_YM, self::VIDEO_TRAINING_YM
                ])) {

                    $duration = $response['data']['duration'] ?? 1;
                    $points = round($duration * $unit,2);
                    $extra = ['音视频时长' => $duration, '算力单价' => $unit, '实际消耗算力' => $points];
                }

                //token扣除
                User::userTokensChange($userId, $points);
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
            return true;

        }

        return false;
    }

    /**
     * 保存base64文件
     */
    public static function saveBase64File(string $base64String, string $type)
    {

        $typePath = match ($type) {
            'avatar' => 'images',
            'audio' => 'audio',
            'video' => 'video',
        };

        $savePath = public_path('uploads/' . $typePath . '/' . date('Ymd'));

        // 检查保存路径是否存在，不存在则创建
        if (!is_dir($savePath)) {

            mkdir($savePath, 0777, true);
        }

        // 获取 MIME 类型和 Base64 数据
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $mimeType = $matches[1]; // 获取 MIME 类型（如 png、jpeg、gif 等）
            $base64String = substr($base64String, strpos($base64String, ',') + 1); // 移除 Base64 前缀
        } else {
            return "";
        }

        // 检查支持的 MIME 类型并获取对应的文件后缀
        $extensionMap = [
            // 视频类型
            'mp4' => '.mp4',
            'avi' => '.avi',
            'mov' => '.mov',
            'wmv' => '.wmv',
            'flv' => '.flv',
            'mkv' => '.mkv',
            // 音频类型
            'mp3' => '.mp3',
            'wav' => '.wav',
            'aac' => '.aac',
            'ogg' => '.ogg',
            'flac' => '.flac',
            // 图片类型
            'png' => '.png',
            'jpeg' => '.jpg',
            'jpg' => '.jpg',
            'gif' => '.gif',
            'bmp' => '.bmp',
            'webp' => '.webp'
        ];

        $fileExtension = $extensionMap[$mimeType] ?? '.bin'; // 未知类型默认为 .bin

        // 解码 Base64 数据
        $imageData = base64_decode($base64String);

        // 检查解码是否成功
        if ($imageData === false) {

            return "";
        }

        $finalFileName = generate_unique_task_id() . $fileExtension;

        // 保存图片到文件
        $filePath = $savePath . $finalFileName;

        if (file_put_contents($filePath, $imageData)) {

            return FileService::getFileUrl(str_replace(public_path(), '', $filePath));
        } else {

            return "";
        }
    }

    public static function copywriting($data){

        $keywords = $data['keywords'] ?? '';
        $number = $data['number'] ?? '';
        if (empty($keywords)  || empty($number)) {
            message('参数错误');
        }

        $taskId = generate_unique_task_id();
        $request = [
            'keywords' => $keywords,
            'number' => $number,
        ];
        $scene = self::HUMAN_COPYWRITING;

        $result = self::requestUrl($request, $scene, self::$uid, $taskId);
        if (!empty($result) && isset($result['content'])) {
            self::$returnData = $result;
        } else {
            self::setError('生成失败');
            return false;
        }
        return true;
    }


    public static function getVideoThumbnailFromUrl($videoUrl, $time = '00:00:01')
    {
        try {
            // 生成缩略图保存路径
            $dirPath = public_path() . 'uploads/images/' . date('Ymd') . '/';
            $thumbnailname = date('YmdHis') . substr(md5($videoUrl), 0, 5)
                . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT) . '.jpg';
            $thumbnailPath = $dirPath . $thumbnailname;
            // 检查文件夹是否存在，不存在则创建
            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0777, true); // 0777 是权限模式，true 表示递归创建
            }
// 构建 FFmpeg 命令，直接使用视频的 URL
            $command = "ffmpeg -i " . escapeshellarg($videoUrl) . " -ss " . escapeshellarg($time) . " -vframes 1 " . escapeshellarg($thumbnailPath) . " 2>&1";
// 执行命令
            $output = shell_exec($command);
// 检查是否成功生成缩略图
            if (file_exists($thumbnailPath)) {
                return 'uploads/images/' . date('Ymd') . '/' . $thumbnailname;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::write('获取图片任务失败11' . $e->getMessage());
            return false;
        }
    }


    public static function updateClipVideo(array $data): bool
    {

        //查询形象
        $model = HumanVideoTask::where('id', $data['id'])->where('task_id', $data['task_id'])->where('clip_status', 2)->find();
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
        }
        $url = '';
        if($data['url'] != ''){
            $url = FileService::setFileUrl($data['url']);
        }
        HumanVideoTask::update([
            'id' => $data['id'],
            'clip_status' => $data['status'],
            'clip_result_url'=>$url
        ]);
        
        return true;
    }

    public static function updateTaskName($data){
        $model = HumanVideoTask::where('id', $data['id'])->where('user_id', self::$uid)->findOrEmpty();
        if ($model->isEmpty()) {

            self::setError('参数错误');
            return false;
        }
        $data['name'] = $data['name'] ?? '默认任务名称';
        HumanVideoTask::update([
            'id' => $data['id'],
            'name'=>$data['name']
        ]);
        return true;
    }
}
