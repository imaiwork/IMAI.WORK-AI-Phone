<?php

namespace app\common\workerman\rpa\handlers\touch;

use app\api\logic\service\TokenLogService;
use app\common\enum\AutomationEnum;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvLeadScrapingSetting;

use app\common\model\user\User;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use think\facade\Log;
use Workerman\Connection\TcpConnection;


class CommentToCommentHandler extends BaseMessageHandler
{
    protected $appType = 0;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->appType = $payload['appType'] ?? 0;
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->payload['reply'] = $this->recordComment($content);
            $this->payload['type'] =  WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'touch');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_COMMENT_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_COMMENT_CHECK;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_COMMENT_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function recordComment(array $content)
    {
        try {
            

            if ((int)$content['task_id'] == 0) {
                return [
                    'isProceed' => 1, //是否处理 1是 0 否
                ];
            }
            $task = SvLeadScrapingSettingAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', 1)
                ->where('account_type', $this->appType)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务不存在: ' . \think\facade\Db::getLastSql());
            }

            TokenLogService::checkToken($task->user_id,'');

            $setting = SvLeadScrapingSetting::where('id', $task->scraping_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务配置不存在');
            }

            $hash = hash('sha256', $content['task_id'] . $content['author_name'] . $content['content']);

            if ((int)$setting->is_execed_clues  === 1) {
                $find = SvLeadScrapingRecord::where([
                    ['user_id', '=', $task->user_id],
                    ['task_type', '=', 1],
                    ['account_name', '=', $content['author_name']],
                    ['hash', '=', $hash],
                ])->findOrEmpty();
                if (!$find->isEmpty()) {
                    return [
                        'isProceed' => 0, //是否处理 1是 0 否
                    ];
                }
            }

            $record = SvLeadScrapingRecord::where([
                ['user_id', '=', $task->user_id],
                ['task_type', '=', 1],
                ['account_name', '=', $content['author_name']],
                ['hash', '=', $hash],
                //['content', 'like', '%' . $content['content'] . '%']
            ])->findOrEmpty();
            if (!$record->isEmpty()) {
                return [
                    'isProceed' => 0, //是否处理 1是 0 否
                ];
            }
            $insert = [
                'user_id'             => $task->user_id,
                'task_type'           => 1,
                'scraping_id'         => $task->scraping_id,
                'scraping_account_id' => $task->id,
                'address'             => $content['address'] ?? '',
                'pusher_timer'         => $content['pusherTimer'] ?? 0,
                'status'              => 3,
                'account_name'        => $content['author_name'],
                'account_type'        => $this->appType,
                'platform'            => $this->appType,
                'device_code'         => $this->payload['deviceId'],
                'task_id'             => $content['task_id'],
                'content'             => $content['content'],
                'exec_time'           => time(),
                'hash'                => $hash,
                'image'               => $this->saveBase64ToImage($content['image'] ?? '', $hash, 'touch'),
                'address'             => $content['address'] ?? '',
                'account'             => $content['account'] ?? '',
                'likes'               => $content['likes'] ?? 0,
                'fans'                => $content['fans'] ?? 0,
                'follows'             => $content['follows'] ?? 0,
                'industry_keyword'    => $content['industry_keyword'] ?? '',
                'notes'               => $content['notes'] ?? '',
                'filter_keyword'      => $content['filter_keyword'] ?? '',
                'comment_content'     => $content['comment_content'] ?? '',
                'touch_content'       => $content['touch_content'] ?? '',
            ];
            //SvLeadScrapingRecord::create($insert);
            $scene = AutomationEnum::SHUT_OFF_COMMENTS;
            self::requestUrl($insert, $scene, $task->user_id, $content['task_id'],  $this->payload['deviceId']);
            return [
                'isProceed' => 1, //是否处理 1是 0 否
            ];
        } catch (\Exception $e) {
            if($e->getCode() == 4059){
                \app\common\model\sv\SvDeviceTask::where('sub_task_id', $content['task_id'])
                    ->where('source', \app\common\enum\DeviceEnum::TASK_SOURCE_TOUCH)
                    ->where('device_code', $this->payload['deviceId'])->update([
                        'status' => 3,
                        'remark' => '执行失败:' . $e->getMessage(),
                        'update_time' => time(),
                    ]);
            }
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::SPH_COMPLETE_ERROR_CODE;
            $this->payload['type'] = 25;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::SPH_COMPLETE_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private static function requestUrl(array $request, string $scene, int $userId,  $taskId, $device_code)
    {
        $autoType = SvDevice::where('device_code', $device_code)->value('auto_type') ?? 0;
        if ($autoType == 0) {
            return [];
        }
        Log::channel('socket')->write('自动化扣费' . $scene . '----设备号--' . $device_code . '----任务id--' . $taskId);
        $requestService = \app\common\service\ToolsService::Automation();

        [$tokenScene, $tokenCode] = match ($scene) {
            // 自动化功能场景
            AutomationEnum::SOCIAL_MEDIA_RELEASED => ['automation_social_media_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_RELEASED],
            AutomationEnum::SHUT_OFF_COMMENTS => ['automation_shut_off_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_COMMENTS],
            AutomationEnum::SHUT_OFF_OBTAIN => ['automation_shut_off_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_OBTAIN],
            AutomationEnum::SHUT_OFF_PRIVATE_LETTER => ['automation_shut_off_private_letter', AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER],
            AutomationEnum::FRIENDS_CIRCLE_COMMENTS => ['automation_friends_circle_comments', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_COMMENTS],
            AutomationEnum::FRIENDS_CIRCLE_RELEASED => ['automation_friends_circle_released', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_RELEASED],
            AutomationEnum::FRIENDS_CIRCLE_PRAISE => ['automation_friends_circle_praise', AccountLogEnum::TOKENS_DEC_AUTOMATION_FRIENDS_CIRCLE_PRAISE],
            AutomationEnum::WECHAT_ADD_FRIEND => ['automation_wechat_add_friend', AccountLogEnum::TOKENS_DEC_AUTOMATION_WECHAT_ADD_FRIEND],
            AutomationEnum::SOCIAL_MEDIA_OBTAIN => ['automation_social_media_obtain', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_OBTAIN],
            AutomationEnum::SOCIAL_MEDIA_NURSING => ['automation_social_media_nursing', AccountLogEnum::TOKENS_DEC_AUTOMATION_SOCIAL_MEDIA_NURSING],
            AutomationEnum::OCR_LOCAL => ['automation_orc_local', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_LOCAL],
            AutomationEnum::OCR_IMG => ['automation_orc_img', AccountLogEnum::TOKENS_DEC_AUTOMATION_OCR_IMG],
        };

        //计费
        $unit = TokenLogService::checkToken($userId, $tokenScene);
        $points = $unit;
        // 添加辅助参数
        $request['task_id'] = $taskId;
        $request['user_id'] = $userId;
        $request['now'] = time();
        $extra = ['算力单价' => $unit, '实际消耗算力' => $unit];
        switch ($scene) {
            // 自动化功能处理
            case AutomationEnum::SOCIAL_MEDIA_RELEASED:
                $response = $requestService->socialMediaReleased($request);
                break;
            case AutomationEnum::SHUT_OFF_COMMENTS:
                $response = $requestService->shutOffComments($request);
                break;
            case AutomationEnum::SHUT_OFF_OBTAIN:
                $response = $requestService->shutOffObtain($request);
                break;
            case AutomationEnum::SHUT_OFF_PRIVATE_LETTER:
                $response = $requestService->shutOffPrivateLetter($request);
                break;

            case AutomationEnum::FRIENDS_CIRCLE_RELEASED:
                $response = $requestService->friendsCircleReleased($request);
                break;

            case AutomationEnum::WECHAT_ADD_FRIEND:
                $response = $requestService->wechatAddFriend($request);
                break;
            case AutomationEnum::SOCIAL_MEDIA_OBTAIN:
                $response = $requestService->socialMediaObtain($request);
                break;
            case AutomationEnum::SOCIAL_MEDIA_NURSING:
                $points = $request['time_difference_minutes'] * $unit;
                $extra = ['执行时长（分钟）' => $request['time_difference_minutes'], '算力单价' => $unit, '实际消耗算力' => $points];
                $response = $requestService->socialMediaNursing($request);
                break;

            default:
        }

        //成功响应，需要扣费
        if (isset($response['code']) && $response['code'] == 10000) {
            if ($points > 0) {
                //token扣除
                User::userTokensChange($userId, $points);
                //记录日志
                AccountLogLogic::recordUserTokensLog(true, $userId, $tokenCode, $points, $taskId, $extra);
            }
        }

        return $response['data'] ?? [];
    }
}
