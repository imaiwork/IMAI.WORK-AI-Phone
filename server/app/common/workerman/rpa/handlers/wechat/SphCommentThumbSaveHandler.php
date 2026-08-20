<?php

namespace app\common\workerman\rpa\handlers\wechat;

use app\common\model\sv\SvAccount;
use app\common\workerman\rpa\BaseMessageHandler;
use Workerman\Connection\TcpConnection;

use app\common\model\sv\SvDevice;
use app\common\workerman\rpa\WorkerEnum;



class SphCommentThumbSaveHandler extends BaseMessageHandler
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

            $this->payload['reply'] = $this->setCommentThumbNotice($content);

            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'thumb');

            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_ERROR_CODE;
            $this->payload['type'] = 400;
            //$this->sendError($this->connection,  $this->payload);
            $this->sendResponse($this->uid, $this->payload, $this->payload['reply']);
        } finally {
            unset($content);
        }
    }

    private function setCommentThumbNotice(array $content): array
    {
        try {
            $device = SvDevice::field('user_id,device_code,auto_type')->where('device_code', $this->payload['deviceId'])->findOrEmpty();
            if ($device->isEmpty()) {
                throw new \Exception('设备不存在');
            }

            $account = SvAccount::where('user_id', $device->user_id)->where('device_code', $this->payload['deviceId'])->where('type', $this->payload['appType'])->findOrEmpty();
            if ($account->isEmpty()) {
                throw new \Exception('用户不存在');
            }

            $taskId = $content['taskId'] ?? 0;
            $task = \app\common\model\sv\SvDeviceTakeOverTaskAccount::field('*')
                ->where('id', $taskId)
                ->where('device_code', '=', $device->device_code)
                ->where('account_type', $account->type)
                ->limit(1)
                ->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception('任务不存在:' . \think\facade\Db::getLastSql());
            }

            $hash = hash('sha256',  $content['content'] ?? '');

            $find = \app\common\model\sv\SvDeviceTakeOverRecord::where('user_id', $device->user_id)
                ->where('hash', $hash)
                ->findOrEmpty();
            if (!$find->isEmpty()) {
                throw new \Exception('评论点赞已存在');
            }

            $model = \app\common\model\sv\SvDeviceTakeOverRecord::create([
                'user_id' => $device->user_id,
                'task_account_id' => $task->id,
                'auto_type' => $device->auto_type,
                'device_code' => $device->device_code,
                'account' => $account->account,
                'nickname' => $account->nickname,
                'avatar' => $account->avatar,
                'user_account' => $content['userAccount'] ?? '',
                'user_nickname' => $content['userNickname'] ?? '',
                'user_avatar' => $content['userAvatar'] ?? '',
                'type' => 3,
                'content' => $content['content'] ?? '',
                'hash' => $hash,
                'create_time' => time(),
            ]);
            return $model->toArray();
        } catch (\Throwable $th) {
            //throw $th;
            $this->setLog('评论点赞异常信息' . $th->__toString(), 'thumb');
            throw $th;
        }
    }
}
