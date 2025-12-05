<?php

namespace app\api\logic;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\audio\Audio;
use app\common\model\audio\AudioInfo;
use think\Exception;
use app\common\model\user\UserTokensLog;
use app\common\service\FileService;
use app\common\model\user\User;

class AudioLogic extends ApiLogic
{

    /**
     * 详情
     * @param int $audioId
     * @return bool
     * @author L
     * @data 2024/6/29 10:30
     */
    public static function detail(int $audioId): bool
    {
        try {
            $audioInfo = AudioInfo::where('user_id', self::$uid)
                ->where('id', $audioId)
                ->findOrEmpty();

            if ($audioInfo->isEmpty()) {
                throw new \Exception('任务不存在');
            }

            $audioInfo->url     = FileService::getFileUrl($audioInfo->url);
            $audioInfo->ws_url  = $audioInfo->ws_url ?: '';
            $audioInfo->text    = $audioInfo->text ?: '';
            self::$returnData   = $audioInfo->toArray();
            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 删除音频转写记录 以及音频文件
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:02
     */
    public static function delete(array $data): bool
    {
        try {

            if (is_string($data['id'])) {
                AudioInfo::destroy(['id' => $data['id'], 'user_id' => self::$uid]);
            } else {
                AudioInfo::whereIn('id', $data['id'])->where('user_id', self::$uid)->select()->delete();
            }

            return true;
        } catch (\Exception $exception) {
            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 创建任务
     * @param array $params
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/6/29 10:46
     */
    public static function task(array $params): bool
    {
        $unit = TokenLogService::checkToken(self::$uid, 'meeting');

        try {

            $request = [
                'language'      => $params['language'],
                'task_type'     => $params['task_type'],
                'url'           => $params['url'],
                'speaker'       => $params['speaker'],
                'translation'   => $params['translation'],
            ];

            if ($params['task_type'] == 2) {

                $request['tips'] = 'start';
            }

            $response = \app\common\service\ToolsService::Asr()->text($request);

            if (!isset($response['data']['task_id']) || !isset($response['data']['duration'])) {

                self::setError('转写失败');
                return false;
            }

            $audioInfo = AudioInfo::create([
                'task_id'     => $response['data']['task_id'],
                'url'         => FileService::setFileUrl($params['url']),
                'status'      => 0,
                'user_id'     => self::$uid,
                'task_type'   => $params['task_type'],
                'language'    => $params['language'],
                'speaker'     => $params['speaker'],
                'translation' => $params['translation'],
                'name'        => $params['name']
            ]);

            if ($audioInfo->task_type == 1) { //离线

                $audioInfo->status  = 3;

                $audioInfo->task_id = $response['data']['task_id'];

                $audioInfo->save();

                $unit = TokenLogService::checkToken(self::$uid, 'meeting');

                //时长
                $duration = $response['data']['duration'] ?? 60;

                //折算分钟
                $minutes = ((int)($duration / 60)) + ($duration % 60 > 0 ? 1 : 0);

                $points = round($minutes * $unit,2);

                //token扣除
                User::userTokensChange(self::$uid, $points);

                $extra = ['音视频时长' => $minutes, '算力单价' => $unit, '实际消耗算力' => $points];

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_MEETING, $points, $audioInfo->task_id, $extra);
            } else { //实时

                $audioInfo->status  = 1; //录音中
                $audioInfo->ws_url  = $response['data']['ws_url'];
                $audioInfo->save();
            }

            self::$returnData = $audioInfo->toArray();
            return true;
        } catch (\think\exception\HttpResponseException $exception) {

            throw $exception;
        } catch (\Throwable $exception) {

            self::setError($exception->getMessage());
            return false;
        }
    }


    /**
     * 创建任务
     * @param array $params
     * @return bool
     * @throws \Exception
     * @author L
     * @data 2024/6/29 10:46
     */
    public static function batch(array $data): bool
    {
        $unit = TokenLogService::checkToken(self::$uid, 'meeting');

        try {

            if (count($data) > 10) {

                self::setError("批量最多支持10个文件");
                return false;
            }

            foreach ($data as $params) {

                $request = [
                    'language'      => $params['language'],
                    'task_type'     => 1,
                    'url'           => $params['url'],
                    'speaker'       => $params['speaker'],
                    'translation'   => $params['translation'],
                ];

                $audioInfo = AudioInfo::create([
                    'url'           => FileService::setFileUrl($params['url']),
                    'status'        => 0,
                    'user_id'       => self::$uid,
                    'task_type'     => 1,
                    'language'      => $params['language'],
                    'speaker'       => $params['speaker'],
                    'translation'   => $params['translation'],
                    'name'          => $params['name']
                ]);

                $response = \app\common\service\ToolsService::Asr()->text($request);
                if (isset($response['code']) && $response['code'] == 10005) {
                    $audioInfo->status = 5;
                    $audioInfo->remark = 'FAILED';
                    $audioInfo->save();
                    throw new \Exception('当前平台算力余额不足，请联系站长');
                }
                if (!isset($response['data']['task_id']) || !isset($response['data']['duration'])) {

                    $audioInfo->status = 5;
                    $audioInfo->remark = 'FAILED';
                    $audioInfo->save();
                    continue;
                }


                $audioInfo->status  = 3;

                $audioInfo->task_id = $response['data']['task_id'];

                $audioInfo->save();

                //时长
                $duration = $response['data']['duration'] ?? 60;

                //折算分钟
                $minutes = ((int)($duration / 60)) + ($duration % 60 > 0 ? 1 : 0);

                $points = round($minutes * $unit,2);

                //token扣除
                User::userTokensChange(self::$uid, $points);

                $extra = ['音视频时长' => $minutes, '算力单价' => $unit, '实际消耗算力' => $points];

                //记录日志
                AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_MEETING, $points, $audioInfo->task_id, $extra);
            }

            self::$returnData = [];
            return true;
        } catch (\think\exception\HttpResponseException $exception) {

            throw $exception;
        } catch (\Throwable $exception) {

            self::setError($exception->getMessage());
            return false;
        }
    }

    /**
     * 获取任务状态
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function status(string $taskId): bool
    {

        $audioInfo = AudioInfo::where('task_id', $taskId)->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        $request = [
            'task_id' => $taskId
        ];

        $response = \app\common\service\ToolsService::Asr()->status($request);

        if ($response['code'] != 10000) {

            self::setError("获取任务状态失败");
            return false;
        }

        self::updateAudioInfo($response['data']);
        self::$returnData = $response['data'];
        return true;
    }


    /**
     * 重试
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function retry(int $audioId): bool
    {

        $audioInfo = AudioInfo::where('id', $audioId)->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        if (!in_array($audioInfo->status, [0, 5])) { //待处理 转写失败

            self::setError("当前状态无法重试");
            return false;
        }

        if ($audioInfo->task_type != 1) {

            self::setError("当前任务类型无法重试");
            return false;
        }

        $request = [
            'language'      => $audioInfo->language,
            'task_type'     => 1,
            'url'           => $audioInfo->url,
            'speaker'       => $audioInfo->speaker,
            'translation'   => $audioInfo->translation,
        ];

        $response = \app\common\service\ToolsService::Asr()->text($request);

        if (!isset($response['data']['task_id']) || !isset($response['data']['duration'])) {

            self::setError('转写失败');
            return false;
        }

        $audioInfo->status  = 3;
        $audioInfo->task_id = $response['data']['task_id'];
        $audioInfo->save();

        self::$returnData = $response['data'];
        return true;
    }

    /**
     * 富文本
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function text(array $data): bool
    {
        $audioInfo = AudioInfo::where('id', $data['id'])->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        $audioInfo->text = $data['text'];
        $audioInfo->save();

        self::$returnData = $audioInfo->toArray();
        return true;
    }

    /**
     * 暂停
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function pause(int $audioId): bool
    {
        $audioInfo = AudioInfo::where('id', $audioId)->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        $audioInfo->status = 2;
        $audioInfo->save();

        self::$returnData = $audioInfo->toArray();
        return true;
    }

    /**
     * 继续录音
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function continue(int $audioId): bool
    {
        $audioInfo = AudioInfo::where('id', $audioId)->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        $audioInfo->status = 1;
        $audioInfo->save();

        self::$returnData = $audioInfo->toArray();
        return true;
    }

    /**
     * 停止录音
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function stop(int $audioId, string $url): bool
    {
        $audioInfo = AudioInfo::where('id', $audioId)->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            self::setError("任务不存在");
            return false;
        }

        $audioInfo->url = $url;

        $request = [
            'language'      => $audioInfo->language,
            'task_type'     => $audioInfo->task_type,
            'url'           => $audioInfo->url,
            'speaker'       => $audioInfo->speaker,
            'translation'   => $audioInfo->translation,
            'tips'          => 'stop'
        ];

        $response = \app\common\service\ToolsService::Asr()->text($request);

        if (!isset($response['data']['task_id']) || !isset($response['data']['duration'])) {

            $audioInfo->status = 5;
            $audioInfo->remark = 'FAILED';
            $audioInfo->save();
            self::setError('转写失败');
            return false;
        }

        $audioInfo->status = 3; //转写中
        $audioInfo->task_id = $response['data']['task_id'];
        $audioInfo->save();

        $unit = TokenLogService::checkToken(self::$uid, 'meeting');

        //时长
        $duration = $response['data']['duration'] ?? 60;

        //折算分钟
        $minutes = ((int)($duration / 60)) + ($duration % 60 > 0 ? 1 : 0);

        $points = ceil($minutes * $unit);

        $extra = ['音视频时长' => $minutes, '算力单价' => $unit, '实际消耗算力' => $points];

        //记录日志
        AccountLogLogic::recordUserTokensLog(true, self::$uid, AccountLogEnum::TOKENS_DEC_MEETING, $points, $audioInfo->task_id, $extra);

        self::$returnData = $audioInfo->toArray();
        return true;
    }

    /**
     * 更新音频信息
     * @param array $data
     * @return bool
     * @author L
     * @data 2024/6/29 16:18
     */
    public static function updateAudioInfo(array $data): bool
    {

        $audioInfo = AudioInfo::where('task_id', $data['TaskId'])->findOrEmpty();

        if ($audioInfo->isEmpty()) {

            return false;
        }

        $audioInfo->response    = $data;
        $audioInfo->remark      = $data['TaskStatus'];

        if ($data['TaskStatus'] == "COMPLETED") {

            $audioInfo->status = 4;
        }

        if ($data['TaskStatus'] == "FAILED") {

            $audioInfo->status = 5;
        }

        $audioInfo->save();

        if ($audioInfo->status === 5) {

            //查询是否已返还
            if (UserTokensLog::where('user_id', $audioInfo->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_MEETING)->where('action', 1)->where('task_id', $audioInfo->task_id)->count() == 0) {

                $points = UserTokensLog::where('user_id', $audioInfo->user_id)->where('change_type', AccountLogEnum::TOKENS_DEC_MEETING)->where('task_id', $audioInfo->task_id)->value('change_amount') ?? 0;

                AccountLogLogic::recordUserTokensLog(false, $audioInfo->user_id, AccountLogEnum::TOKENS_DEC_MEETING, $points, $audioInfo->task_id);
            }
        }

        return true;
    }
}
