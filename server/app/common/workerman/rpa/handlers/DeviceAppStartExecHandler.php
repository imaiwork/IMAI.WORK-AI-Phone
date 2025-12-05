<?php

namespace app\common\workerman\rpa\handlers;

use app\common\workerman\rpa\BaseMessageHandler;
use app\common\model\sv\SvDeviceRpa;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;

class DeviceAppStartExecHandler extends BaseMessageHandler

{
    protected $deviceList;
    protected $deviceInfo;
    protected $content;
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content']) ? json_decode($payload['content'], true) : $payload['content'];
        try {
            $this->content = $content;
            $this->msgType = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->uid = $uid;
            $this->payload = $payload;
            $this->userId = $content['userId'] ?? 0;
            $this->connection = $connection;
            $this->payload['reply'] = $this->appExecReport($content);

            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('异常信息' . $e, 'app_exec');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_EXEC_APP_ERROR_CODE;
            $this->payload['type'] = 91;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::DEVICE_EXEC_APP_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }

    private function appExecReport($content)
    {
        try {
            $find = SvDeviceRpa::where('id', $content['task_id'])->findOrEmpty();
            if ($find->isEmpty()) {
                throw new \Exception('设备rpa配置不存在');
            }

            if ((string)$find->device_code !== (string)$content['deviceId']) {
                throw new \Exception('设备号不一致');
            }
            // if((int)$find->app_type !== (int)$content['appType']){
            //     throw new \Exception('应用类型不一致');
            // }

            $find->start_time = $content['startTime'];
            $find->end_time = date('Y-m-d H:i:s', strtotime($content['startTime']) + ($find->exec_duration * 60));
            $find->status = 1;
            $find->save();
            SvDeviceRpa::where('device_code', $find->device_code)
                ->where('id', '<>', $find->id)
                ->update([
                    'status' => 0,
                    'end_time' => date('Y-m-d H:i:s', time()),
                    'update_time' => time(),
                ]);
            return [
                'deviceId' => $this->payload['deviceId'],
                'status' => 1,
                'time' => date('Y-m-d H:i:s'),
                'msg' => 'rpa执行时间设置成功',
                'task_id' => $find->id,
                'app_name' => $find->app_name,

            ];
        } catch (\Throwable $e) {
            $this->setLog('异常信息' . $e, 'app_exec');
            $this->payload['reply'] = $e->getMessage();
            $this->payload['code'] =  WorkerEnum::DEVICE_EXEC_APP_ERROR_CODE;
            $this->payload['type'] = 91;
            $this->payload['content'] = [
                'code' =>  WorkerEnum::DEVICE_EXEC_APP_ERROR_CODE,
                'msg' => '异常信息:' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId']
            ];
            $this->sendError($this->connection,  $this->payload);
        }
    }
}
