<?php

namespace app\common\workerman\rpa\handlers\touch;

use Workerman\Connection\TcpConnection;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvLeadScrapingRecord;

class CommentToMarkClueHandler extends BaseMessageHandler
{
    protected $appType = 0;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->appType = $payload['appType'] ?? 0;
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->payload['reply'] = $this->recordMarkClue($content);
            $this->payload['type'] =  WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_MARK_CLUE_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_MARK_CLUE;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_MARK_CLUE_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }

    private function recordMarkClue(array $content)
    {
        try {
            $task = SvLeadScrapingSettingAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', 3)
                ->where('account_type', $this->appType)
                ->findOrEmpty();
            if($task->isEmpty()){
                throw new \Exception($this->platform[$this->appType] . '截流获客留痕获客任务不存在');
            }


            return [
                'isProceed' => 1,//是否处理 1是 0 否
            ];
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_MARK_CLUE_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_MARK_CLUE;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_MARK_CLUE_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }
}
