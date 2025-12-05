<?php

namespace app\adminapi\logic\sv;


use app\api\logic\service\TokenLogService;
use app\api\logic\sv\DataNotFoundException;
use app\api\logic\sv\DbException;
use app\api\logic\sv\ModelNotFoundException;
use app\api\logic\sv\SvBaseLogic;
use app\common\model\sv\SvVideoSetting;
use app\common\model\sv\SvVideoTask;
use think\facade\Db;
use think\facade\Log;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\user\User;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use TencentCloud\Teo\V20220106\Models\Sv;

/**
 * SvVideoTaskLogic
 * @desc 视频设置逻辑处理
 */
class SvVideoTaskLogic extends SvBaseLogic
{





    const AUDIO_TRAINING_YM = 'audioTrainingYm'; //文字转音频
    const VIDEO_TRAINING_YM = 'videoTrainingYm'; //视频训练

    const AUDIO_TRAINING_YMT = 'audioTrainingYmt'; //文字转音频
    const VIDEO_TRAINING_YMT = 'videoTrainingYmt'; //视频训练








    public static function deleteSvVideoTask($params)
    {
        try {
            if (is_string($params['id'])) {
                SvVideoTask::destroy(['id' => $params['id']]);
            } else {
                SvVideoTask::destroy($params['id']);
            }
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }



}