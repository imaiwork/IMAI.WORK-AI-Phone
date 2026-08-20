<?php

namespace app\api\controller\station;

use app\api\controller\BaseApiController;
use app\common\service\deviceauth\DeviceAuthCodeSyncService;
use app\common\service\station\StationAuthService;
use think\response\Json;

class DeviceAuthCodeController extends BaseApiController
{
    public array $notNeedLogin = ['sync'];

    public function sync(): Json
    {
        try {
            $rawBody = (string)$this->request->getContent();
            StationAuthService::verify($rawBody);

            $payload = json_decode($rawBody, true);
            if (!is_array($payload)) {
                return $this->stationFail('请求体格式错误');
            }
            $codes = $payload['codes'] ?? [];
            if (!is_array($codes)) {
                return $this->stationFail('codes 必须为数组');
            }

            $result = DeviceAuthCodeSyncService::syncFromPayload($codes);
            return $this->stationSuccess('success', $result);
        } catch (\Exception $e) {
            $code = (int)$e->getCode();
            if ($code === 401) {
                return $this->stationFail($e->getMessage(), 401);
            }
            return $this->stationFail($e->getMessage());
        }
    }

    protected function stationSuccess(string $message, array $data = []): Json
    {
        return json([
            'code'    => 10000,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function stationFail(string $message, int $httpCode = 200): Json
    {
        return json([
            'code'    => 10090,
            'message' => $message,
            'data'    => (object)[],
        ], $httpCode >= 400 ? $httpCode : 200);
    }
}
