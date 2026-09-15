<?php

namespace app\common\workerman\rpa\handlers\touch;

use app\api\logic\service\TokenLogService;
use app\common\enum\AutomationEnum;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\service\AutomationBillingService;
use app\common\service\sv\ShutoffCommentAiService;
use app\common\service\sv\ShutoffDmTouchHelper;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use think\facade\Cache;
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
            $this->setLog('异常信息' . $e, 'shutoff_cm');
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
        $lockKey = '';
        $lockHeld = false;
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

            TokenLogService::checkToken($task->user_id, '');

            $setting = SvLeadScrapingSetting::where('id', $task->scraping_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务配置不存在');
            }

            $hash = hash('sha256', $content['author_name'] . $content['content']);

            if ((int)$setting->is_execed_clues  === 1) {
                $find = SvLeadScrapingRecord::where([
                    ['user_id', '=', $task->user_id],
                    ['task_type', '=', 1],
                    ['account_name', '=', $content['author_name']],
                    ['hash', '=', $hash],
                ])->findOrEmpty();
                if (!$find->isEmpty()) {
                    return ShutoffDmTouchHelper::buildCheckReply(false, '', '', '该线索已触达');
                }
            }

            $record = SvLeadScrapingRecord::where([
                ['user_id', '=', $task->user_id],
                ['task_type', '=', 1],
                ['account_name', '=', $content['author_name']],
                ['hash', '=', $hash],
            ])->findOrEmpty();
            if (!$record->isEmpty()) {
                return ShutoffDmTouchHelper::buildCheckReply(false, '', '', '该评论已处理');
            }

            $commentText = ShutoffDmTouchHelper::resolveCommentText($content);
            $touchType = (int)($setting->touch_type ?? ShutoffDmTouchHelper::TOUCH_TYPE_SPEECH);
            $agentId = (int)($setting->agent_id ?? 0);

            if (ShutoffDmTouchHelper::isAiTouch($touchType, $agentId)) {
                if ($commentText === '') {
                    $this->setLog('截流评论跳过:评论原文为空', 'shutoff_cm');
                    return ShutoffDmTouchHelper::buildCheckReply(false, '', '', '评论原文为空');
                }
                $lockKey = $this->generateLockKey((int)$task->id, $hash);
                if (!$this->tryAcquireGenerateLock($lockKey, $lockHeld)) {
                    return ShutoffDmTouchHelper::buildCheckReply(false, '', '', '该评论正在处理中');
                }
                $aiResult = ShutoffCommentAiService::generate([
                    'userId' => (int)$task->user_id,
                    'robotId' => $agentId,
                    'commentText' => $commentText,
                    'deviceCode' => (string)$this->payload['deviceId'],
                    'taskId' => (string)$content['task_id'],
                    'authorName' => (string)($content['author_name'] ?? ''),
                    'noteTitle' => (string)($content['notes'] ?? ($content['note_title'] ?? '')),
                ]);
                $this->setLog('截流AI评论生成结果:' . json_encode($aiResult, JSON_UNESCAPED_UNICODE), 'shutoff_cm');
                $decision = ShutoffDmTouchHelper::decideProceed($touchType, $agentId, $commentText, $aiResult);
                if ((int)($decision['error_code'] ?? 0) === 4059) {
                    throw new \Exception((string)($decision['skip_reason'] ?? '算力不足'), 4059);
                }
                if ((int)($decision['isProceed'] ?? 0) !== 1) {
                    $this->setLog('截流AI评论跳过:' . (string)($decision['skip_reason'] ?? 'AI评论生成失败'), 'shutoff_cm');
                    return $decision;
                }
                $this->chargeAutomationIfNeeded($content, (int)$task->user_id);
                $lockHeld = false;
                return $decision;
            }

            $decision = ShutoffDmTouchHelper::decideProceed($touchType, $agentId, $commentText);
            if ((int)($decision['isProceed'] ?? 0) !== 1) {
                $this->setLog('截流评论跳过:' . (string)($decision['skip_reason'] ?? ''), 'shutoff_cm');
                return $decision;
            }
            $this->chargeAutomationIfNeeded($content, (int)$task->user_id);
            return $decision;
        } catch (\Exception $e) {
            if ((int)$e->getCode() === 4059) {
                \app\common\model\sv\SvDeviceTask::where('sub_task_id', $content['task_id'])
                    ->where('source', \app\common\enum\DeviceEnum::TASK_SOURCE_TOUCH)
                    ->where('device_code', $this->payload['deviceId'])->update([
                        'status' => 3,
                        'remark' => '执行失败:' . $e->getMessage(),
                        'update_time' => time(),
                    ]);
            }
            $this->setLog('异常信息' . $e, 'shutoff_cm');
            throw $e;
        } finally {
            if ($lockHeld && $lockKey !== '') {
                $this->releaseGenerateLock($lockKey);
            }
            unset($content);
        }
    }

    /**
     * 自动化动作费：仅 auto_type=1 时扣；手工由 BillingService 直接跳过
     */
    private function chargeAutomationIfNeeded(array $content, int $userId): void
    {
        AutomationBillingService::requestAndCharge(
            $content,
            AutomationEnum::SHUT_OFF_COMMENTS,
            $userId,
            $content['task_id'] ?? 0,
            (string)$this->payload['deviceId']
        );
    }

    private function generateLockKey(int $taskId, string $hash): string
    {
        return 'shutoff_cm_lock:' . $taskId . ':' . $hash;
    }

    /**
     * @param-out bool $held 是否实际持锁（Redis 异常降级为无锁放行时为 false）
     */
    private function tryAcquireGenerateLock(string $lockKey, bool &$held): bool
    {
        $held = false;
        try {
            $redis = Cache::store('redis')->handler();
            if (method_exists($redis, 'set')) {
                $ok = (bool)$redis->set($lockKey, '1', ['nx', 'ex' => 30]);
                $held = $ok;
                return $ok;
            }
            if (!$redis->setnx($lockKey, '1')) {
                return false;
            }
            $redis->expire($lockKey, 30);
            $held = true;
            return true;
        } catch (\Throwable $e) {
            $this->setLog('截流评论加锁失败:' . $e->getMessage(), 'shutoff_cm');
            return true;
        }
    }

    private function releaseGenerateLock(string $lockKey): void
    {
        try {
            Cache::store('redis')->handler()->del($lockKey);
        } catch (\Throwable $e) {
            $this->setLog('截流评论解锁失败:' . $e->getMessage(), 'shutoff_cm');
        }
    }
}
