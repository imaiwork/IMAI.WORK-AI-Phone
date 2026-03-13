<?php

namespace app\common\workerman\rpa\handlers\touch;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSettingAccount;
use app\common\model\sv\SvLeadScrapingSetting;
use app\common\model\user\User;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;


class CommentToMsgHandler extends BaseMessageHandler
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
            $this->payload['reply'] = $this->recordMsg($content);
            $this->payload['type'] = WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'task_complete');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_MSG_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_MSG_CHECK;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_MSG_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function recordMsg(array $content)
    {
        try {
            if ((int)$content['task_id'] == 0) {
                return [
                    'isProceed' => 1, //是否处理 1是 0 否
                ];
            }

            $task = SvLeadScrapingSettingAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', 2)
                ->where('account_type', $this->appType)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception($this->platform[$this->appType] . '截流获客评论区私信任务不存在: ' . \think\facade\Db::getLastSql());
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
                    ['task_type', '=', 2],
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
                ['task_type', '=', 2],
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
                'task_type'           => 2,
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
                'hash'                => $hash,
                'image'               => $this->saveBase64ToImage($content['image'] ?? '', $hash, 'touch'),
                'address'             => $content['address'] ?? '',
                'pusher_timer'        => $content['pusherTimer'] ?? 0,
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
            $autoType = SvDevice::where('device_code', $this->payload['deviceId'])->value('auto_type') ?? 0;
            if ($autoType == 1) {
                $response = \app\common\service\ToolsService::Automation()->shutOffPrivateLetter($content);
                if (isset($response['code']) && $response['code'] == 10000) {
                    //检查扣费
                    $unit = TokenLogService::checkToken($task->user_id, 'automation_shut_off_private_letter');
                    $points = $unit;
                    //token扣除
                    User::userTokensChange($task->user_id, $points);
                    $extra = ['算力单价' => $unit, '实际消耗算力' => $points];
                    $desc = AccountLogEnum::TOKENS_DEC_AUTOMATION_SHUT_OFF_PRIVATE_LETTER;
                    //扣费记录
                    AccountLogLogic::recordUserTokensLog(true, $task->user_id, $desc, $points, $content['task_id'], $extra);
                }
            }


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
            $this->payload['code'] =  WorkerEnum::RPA_COMMENT_TO_MSG_FAIL;
            $this->payload['type'] = WorkerEnum::RPA_COMMENT_TO_MSG_CHECK;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::RPA_COMMENT_TO_MSG_FAIL,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }
}
