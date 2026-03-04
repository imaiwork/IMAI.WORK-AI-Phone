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


class CommentToTouchPostHandler extends BaseMessageHandler
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
            $this->payload['reply'] = $this->savePost($content);
            $this->payload['type'] =  WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'touch');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_TOUCH_POST_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_TOUCH_POST;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_TOUCH_POST_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function savePost(array $content)
    {
        try {
            if ((int)$content['task_id'] == 0) {
                return [
                    'ok' => 0, //是否处理 1是 0 否
                ];
            }

            if(!isset($content['nickName']) || (int)$content['type'] === 0) {
                return [
                    'ok' => 0, //是否处理 1是 0 否
                ];
            }

            $taskType = $this->getTaskTypeId((int)$content['task_id']);

            $task = SvLeadScrapingSettingAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', $taskType)
                ->where('account_type', $this->appType)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务不存在: ' . \think\facade\Db::getLastSql());
            }

            $setting = SvLeadScrapingSetting::where('id', $task->scraping_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务配置不存在');
            }

            $hash = hash('sha256', $content['task_id'] . $content['nickName'] . ($content['commentStr'] ?? ''));

            $insert = [
                'user_id'             => $task->user_id,
                'task_type'           => $taskType,
                'scraping_id'         => $task->scraping_id,
                'scraping_account_id' => $task->id,
                'address'             => $content['area'] ?? '',
                'pusher_timer'         => $content['pusherTimer'] ?? 0,
                'status'              => 3,
                'account'             => $content['account'] ?? '',
                'account_name'        => $content['nickName'],
                'account_type'        => $this->appType,
                'platform'            => $this->appType,
                'device_code'         => $this->payload['deviceId'],
                'task_id'             => $content['task_id'],
                'content'             => $content['commentStr'] ?? '',
                'exec_time'           => time(),
                'hash'                => $hash,
                'image'               => $this->saveBase64ToImage($content['image'] ?? '', $hash, 'touch'),
                'likes'               => $content['thumbsUpAndcollect'] ?? 0,
                'fans'                => $content['numberFans'] ?? 0,
                'avatar'              => $this->saveBase64ToImage($content['avatar'] ?? '', time() . rand(1000, 9999), 'touch'),
                'follows'             => $content['numberFollowers'] ?? 0,
                'industry_keyword'    => $content['industryKeywords'] ?? '',
                'note_title'          => $content['title'] ?? '',
                'notes'               => $content['notes'] ?? '',
                'filter_keyword'      => $content['targetKeywords'] ?? '',
                'comment_content'     => $content['replyCommentStr'] ?? '',
                'touch_content'       => $content['touch_content'] ?? '',
            ];
            SvLeadScrapingRecord::create($insert);
            return [
                'ok' => 1, //是否处理 1是 0 否
            ];
        } catch (\Exception $e) {
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

    private function getTaskType(int $type): int
    {
        $map = [
            101 => 1,
            102 => 2,
            103 => 3,
        ];
        return $map[$type] ?? 0;
    }

    private function getTaskTypeId(int $taskId): int
    {
        try {
            $task = SvLeadScrapingSettingAccount::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务不存在: ' . \think\facade\Db::getLastSql());
            }

            $setting = SvLeadScrapingSetting::where('id', $task->scraping_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务配置不存在');
            }
            return $task->task_type;
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog('异常信息' . $th, 'task_complete');
        }
        return 0;
    }
}
