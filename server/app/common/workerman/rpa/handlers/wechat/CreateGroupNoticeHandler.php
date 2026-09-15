<?php

namespace app\common\workerman\rpa\handlers\wechat;

use app\common\model\sv\SvAccount;
use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\workerman\rpa\WorkerEnum;
use app\common\model\wechat\AiWechatCreateGroupLog;



class CreateGroupNoticeHandler extends BaseMessageHandler
{
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->payload['reply'] = $this->setCreateGroupNotice($content);

            //$this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'group');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 'error';
            $this->sendError($this->connection,  $this->payload);
        } finally {
            unset($content);
        }
    }

    private function setCreateGroupNotice(array $content): array
    {
        try {
            $device = SvDevice::field('user_id,device_code,auto_type,persona_id')->where('device_code', $this->payload['deviceId'])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $account = SvAccount::where('user_id', $device->user_id)->where('device_code', $this->payload['deviceId'])->findOrEmpty();
            if ($account->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            $groupStrategy = null;
            if ((int)$device->auto_type === 1) {
                $groupStrategy = \app\common\model\aiPersona\AiPersonaWechatInteractionConfig::where('persona_id', '=', function ($query) {
                    $query->name('sv_device')->where('device_code', $this->payload['deviceId'])->field('persona_id');
                })->findOrEmpty();
                if ($groupStrategy->isEmpty()) {
                    throw new \Exception('24h任务微信群配置不存在');
                }
            } else {
                $groupStrategy = \app\common\model\sv\SvWechatStrategy::where('user_id', $device->user_id)->where('device_code', $this->payload['deviceId'])->findOrEmpty();
                if ($groupStrategy->isEmpty()) {
                    throw new \Exception('手动任务微信群配置不存在');
                }
            }

            if ((int)$device->auto_type === 1 && !AiPersonaOptionService::isEnabledForPersonaId((int)$device->persona_id, 'private_operation.options.auto_add_group')) {
                $this->setLog('global_option.private_operation.options.auto_add_group=0，跳过自动建群通知处理', 'group');
                return [];
            }

            $task_id = generate_unique_task_id();
            if ((int)$content['status'] === 1) {

                $tokenScene = 'wechat_create_group';
                $tokenCode = \app\common\enum\user\AccountLogEnum::TOKENS_DEC_WECHAT_CREATE_GROUP;

                $unit = \app\api\logic\service\TokenLogService::checkToken($device->user_id, $tokenScene);
                $response = \app\common\service\ToolsService::Wechat()->createGroup();
                if (isset($response['code']) && $response['code'] == 10000) {
                    $points = $unit;
                    if ($points > 0) {

                        $extra = ['总消耗tokens数' => $points,  '算力单价' => $unit, '实际消耗算力' => $points, '场景' => $device->auto_type === 1 ? '24h任务创建微信群' : '手动任务创建微信群'];
                        //clogger('extra: '. json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        //token扣除
                        \app\common\model\user\User::userTokensChange($device->user_id, $points);
                        //记录日志
                        \app\common\logic\AccountLogLogic::recordUserTokensLog(true, $device->user_id, $tokenCode, $points, $task_id, $extra);
                    }
                }
            }

            $model = AiWechatCreateGroupLog::create([
                'user_id' => $device->user_id,
                'device_code' => $this->payload['deviceId'],
                'friend_id' => $content['friend_id'] ?? '',
                'wechat_id' => $content['wechat_id'] ?? $account->account,
                'sales_wechat' => $groupStrategy->sales_wechat[0] ?? '',
                'group_name' => $content['group_name'] ?? '',
                'scene' => $device->auto_type,
                'task_id' => $task_id,
                'status' => $content['status'] ?? -1,
                'result' => $content['msg'] ?? '',
                'message_content' => $content['message_content'] ?? '',
            ]);


            \app\api\logic\ApiLogic::sendNotice([
                'userId' => $device->user_id,
                'name' => $content['group_name'] ?? '',
                'time' => date('Y-m-d H:i:s', time()),
                'status' => $content['status'] === 1 ? '成功' : '失败',
            ], 406);
            return $model->toArray();
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog('创建微信群异常信息' . $th->__toString(), 'group');
            return [];
        }
    }
}
