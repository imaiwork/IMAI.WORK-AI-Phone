<?php

namespace app\common\workerman\rpa\handlers\imai;

use app\common\model\sv\SvCityExposureRecord;
use app\common\model\sv\SvCityExposureTask;
use app\common\model\sv\SvCityExposureTaskAccount;
use app\common\workerman\rpa\BaseMessageHandler;
use app\common\workerman\rpa\WorkerEnum;
use Workerman\Connection\TcpConnection;
use app\common\enum\AutomationEnum;
use app\common\service\AutomationBillingService;
/**
 * CityExposureHandler
 * @desc 同城曝光任务消息处理器
 */
class CityExposureHandler extends BaseMessageHandler
{
    /**
     * 当前设备的 App 类型（平台类型）
     * @var int
     */
    protected $appType = 0;

    /**
     * 消息入口
     */
    public function handle(TcpConnection $connection, string $uid, array $payload): void
    {
        $content = !is_array($payload['content'])
            ? json_decode($payload['content'], true)
            : $payload['content'];

        try {
            $this->msgType    = WorkerEnum::DESC[$payload['type']] ?? $payload['type'];
            $this->appType    = $payload['appType'] ?? 0;
            $this->uid        = $uid;
            $this->payload    = $payload;
            $this->userId     = $content['userId'] ?? 0;
            $this->connection = $connection;

            $this->payload['reply'] = $this->savePost($content);
            $this->payload['type']  = WorkerEnum::RPA_SEND_MESSAGE;
            $this->sendResponse($uid, $this->payload, $this->payload['reply']);
        } catch (\Exception $e) {
            $this->setLog('同城曝光任务异常：' . $e->getMessage(), 'city_exposure');
            $this->payload['reply']   = $e->getMessage();
            $this->payload['code']    = WorkerEnum::RPA_CITY_EXPOSURE_FAIL;
            $this->payload['type']    = WorkerEnum::RPA_CITY_EXPOSURE;
            $this->payload['content'] = [
                'code'     => WorkerEnum::RPA_CITY_EXPOSURE_FAIL,
                'msg'      => '异常信息：' . $e->getMessage(),
                'deviceId' => $payload['deviceId'] ?? '',
            ];
            $this->sendError($this->connection, $this->payload);
        } finally {
            unset($content);
        }
    }

    /**
     * 保存同城曝光记录
     * 同城曝光业务较轻量：无留痕触达内容，只记录访问行为
     *
     * @param array $content 设备上报的消息体
     * @return array ['ok' => 1|0]
     */
    private function savePost(array $content): array
    {
        try {
            // 无效 task_id 直接跳过
            if ((int)($content['task_id'] ?? 0) === 0) {
                return ['ok' => 0];
            }

            // 获取子任务的 task_type
            $taskType = $this->getTaskTypeId((int)$content['task_id']);

            // 查询子任务（账号维度）
            $task = SvCityExposureTaskAccount::where('id', $content['task_id'])
                ->where('device_code', $this->payload['deviceId'])
                ->where('task_type', $taskType)
                ->where('account_type', $this->appType)
                ->findOrEmpty();

            if ($task->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') .
                        '同城曝光任务不存在: ' . \think\facade\Db::getLastSql()
                );
            }

            // 查询主任务配置
            $setting = SvCityExposureTask::where('id', $task->city_exposure_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') . '同城曝光任务配置不存在'
                );
            }

            // 生成去重 hash（账号 + 执行时间）
            $hash = hash('sha256', ($content['account'] ?? '') . time());

            // 写入曝光记录（对应 iw_sv_city_exposure_record 表）
            $account = str_replace(WorkerEnum::getAccountTypeDesc($this->payload['appType'] ?? 3) . '号：', '', $content['account'] ?? '');
            // 同城曝光无触达内容，字段比截流/团购精简
            $insert = [
                'user_id'                  => $task->user_id,
                'task_type'                => $taskType,
                'city_exposure_id'         => $task->city_exposure_id,   // 主任务 ID
                'city_exposure_account_id' => $task->id,                 // 子任务 ID
                'status'                   => 3,                         // 发送中
                'account'                  => $account,
                'account_name'             => $content['nickName'] ?? '',
                'account_type'             => $this->appType,
                'platform'                 => $this->appType,
                'device_code'              => $this->payload['deviceId'],
                'task_id'                  => $content['task_id'],
                'exec_time'                => time(),
                'hash'                     => $hash,
                'avatar'                   => $this->toolUtil->saveBase64ToImage(
                    $content['avatar'] ?? '',
                    time() . rand(1000, 9999),
                    'city_exposure'
                ),
                'image'                    => $this->toolUtil->saveBase64ToImage(
                    $content['image'] ?? '',
                    $hash,
                    'city_exposure'
                ),
                'extra'                    => $content ?? [],
            ];

            SvCityExposureRecord::create($insert);
            $scene = AutomationEnum::CITY_EXPOSURE;
            self::requestUrl($insert, $scene, $task->user_id, $content['task_id'],  $this->payload['deviceId']);
            return ['ok' => 1];
        } catch (\Exception $e) {
            $this->setLog('同城曝光任务 savePost 异常：' . $e, 'city_exposure');
            $this->payload['reply']   = $e->getMessage();
            $this->payload['code']    = WorkerEnum::RPA_CITY_EXPOSURE_FAIL;
            $this->payload['type']    = WorkerEnum::RPA_CITY_EXPOSURE;
            $this->payload['content'] = [
                'code'     => WorkerEnum::RPA_CITY_EXPOSURE_FAIL,
                'msg'      => '异常信息：' . $e->getMessage(),
                'deviceId' => $this->payload['deviceId'],
            ];
            $this->sendError($this->connection, $this->payload);

            return ['ok' => 0];
        } finally {
            unset($content);
        }
    }

    /**
     * 根据子任务 ID 获取 task_type
     *
     * @param int $taskId  SvCityExposureTaskAccount.id
     * @return int
     */
    private function getTaskTypeId(int $taskId): int
    {
        try {
            $task = SvCityExposureTaskAccount::where('id', $taskId)->findOrEmpty();
            if ($task->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') .
                        '同城曝光任务不存在: ' . \think\facade\Db::getLastSql()
                );
            }

            $setting = SvCityExposureTask::where('id', $task->city_exposure_id)->findOrEmpty();
            if ($setting->isEmpty()) {
                throw new \Exception(
                    ($this->platform[$this->appType] ?? '') . '同城曝光任务配置不存在'
                );
            }

            return (int)$task->task_type;
        } catch (\Throwable $th) {
            $this->setLog('getTaskTypeId 异常：' . $th, 'city_exposure');
        }

        return 0;
    }

    private static function requestUrl(array $request, string $scene, int $userId, string $taskId, string $device_code)
    {
        return AutomationBillingService::requestAndCharge($request, $scene, $userId, $taskId, $device_code);
    }
}
