<?php

namespace app\common\workerman\rpa\handlers\touch;

use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
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
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_COMMENT;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_COMMENT_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally{
            unset($content);
        }
    }

    private function recordComment(array $content)
    {
        try {
            $task = SvLeadScrapingSettingAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', 1)
                ->where('account_type', $this->appType)
                ->findOrEmpty();
            if($task->isEmpty()){
                print_r(\think\facade\Db::getLastSql());
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区评论任务不存在');
            }

            $record = SvLeadScrapingRecord::where([
                                                      ['user_id', '=', $task->user_id],
                                                      ['task_type', '=', 1],
                                                      ['account_name', '=', $content['author_name']],
                                                      ['content', 'like', '%' . $content['content'] . '%']
                                                  ])->findOrEmpty();
            if (!$record->isEmpty()){
                return [
                    'isProceed' => 0,//是否处理 1是 0 否
                ];
            }
            $insert = [
                'user_id'             => $task->user_id,
                'task_type'           => 1,
                'scraping_id'         => $task->scraping_id,
                'scraping_account_id' => $task->id,
                'status'              => 3,
                'account_name'        => $content['author_name'],
                'account_type'        => $this->appType,
                'platform'            => $this->appType,
                'device_code'         => $this->payload['deviceId'],
                'task_id'             => $content['task_id'],
                'content'             => $content['content'],
                'exec_time'           => time(),
            ];
            SvLeadScrapingRecord::create($insert);

            return [
                'isProceed' => 1,//是否处理 1是 0 否
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
        } finally{
            unset($content);
        }
    }
}
