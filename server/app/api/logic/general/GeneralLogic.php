<?php

namespace app\api\logic\general;

use app\api\logic\service\TokenLogService;
use app\common\enum\user\AccountLogEnum;
use app\common\logic\AccountLogLogic;
use app\common\logic\BaseLogic;
use app\common\model\sv\SvDevice;
use app\common\model\user\User;
use think\facade\Log;

/**
 * 通用AI服务逻辑层（免登录版本）
 * Class GeneralLogic
 *
 * 参考 CrawlingTaskLogic::ocr / localOcr：
 * 免登录接口，通过 device_code 定位 SvDevice.user_id 作为扣费主体
 * 请求日志统一写入 device 通道（runtime/log/device/）
 */
class GeneralLogic extends BaseLogic
{
    /**
     * 通用AI分析
     * @param array $params 必含 device_code
     * @return bool
     */
    public static function analyze(array $params = []): bool
    {
        try {
            Log::channel('device')->write("general_analyze " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // 1. 通过 device_code 查设备 → 取 user_id（参考 CrawlingTaskLogic::ocr）
            $device = SvDevice::where('device_code', $params['device_code'] ?? '')->findOrEmpty();
            if ($device->isEmpty()) {
                Log::channel('device')->warning("general_analyze 设备不存在 device_code={$params['device_code']}");
                self::setError('设备不存在');
                return false;
            }
            $userId = (int)$device['user_id'];

            // 2. 调中台通用AI分析接口
            $response = \app\common\service\ToolsService::General()->analyze($params);

            if (isset($response['code']) && $response['code'] == 10000) {
                // 3. 扣费骨架（参考 CrawlingTaskLogic::ocr，扣费主体为 device.user_id）
                // 场景名必须与 model_config.scene 一致（v2.13.7.sql 写入的是 general_ai_analyze）
                $scene      = 'general_ai_analyze';
                $changeType = AccountLogEnum::TOKENS_DEC_GENERAL_ANALYZE;

                $unit    = TokenLogService::checkToken($userId, $scene);
                $points  = $unit;
                $task_id = generate_unique_task_id();

                User::userTokensChange($userId, $points);

                $extra = [
                    '扣费项目'     => '通用AI分析',
                    '算力单价'     => $unit . '算力/次',
                    '实际消耗算力' => $points,
                ];
                // analyze_type 由客户端按需传入，未传时不再写入空值
                if (!empty($params['analyze_type'])) {
                    $extra['analyze_type'] = $params['analyze_type'];
                }

                AccountLogLogic::recordUserTokensLog(
                    true, $userId, $changeType, $points, $task_id, $extra
                );

                Log::channel('device')->info("general_analyze 扣费成功 device_code={$params['device_code']} user_id={$userId} 扣费={$points} task_id={$task_id}");

                self::$returnData = $response;
                return true;
            } else {
                Log::channel('device')->warning("general_analyze 中台返回失败 device_code={$params['device_code']} msg=" . ($response['msg'] ?? 'unknown'));
                self::setError($response['msg'] ?? 'AI分析失败');
                return false;
            }
        } catch (\Throwable $e) {
            Log::channel('device')->error("general_analyze 异常 device_code={$params['device_code']} error=" . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * 通用OCR识别
     * @param array $params 必含 device_code
     * @return bool
     */
    public static function ocr(array $params = []): bool
    {
        try {
            Log::channel('device')->write("general_ocr " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // 1. 通过 device_code 查设备 → 取 user_id（参考 CrawlingTaskLogic::ocr）
            $device = SvDevice::where('device_code', $params['device_code'] ?? '')->findOrEmpty();
            if ($device->isEmpty()) {
                Log::channel('device')->warning("general_ocr 设备不存在 device_code={$params['device_code']}");
                self::setError('设备不存在');
                return false;
            }
            $userId = (int)$device['user_id'];

            // 2. 调中台通用OCR接口
            $response = \app\common\service\ToolsService::General()->ocr($params);

            if (isset($response['code']) && $response['code'] == 10000) {
                // 3. 扣费骨架（参考 CrawlingTaskLogic::ocr，扣费主体为 device.user_id）
                $scene      = 'general_ocr';
                $changeType = AccountLogEnum::TOKENS_DEC_GENERAL_OCR;

                $unit    = TokenLogService::checkToken($userId, $scene);
                $points  = $unit;
                $task_id = generate_unique_task_id();

                User::userTokensChange($userId, $points);

                $extra = [
                    '扣费项目'     => '通用OCR识别',
                    '算力单价'     => $unit . '算力/次',
                    '实际消耗算力' => $points,
                ];

                AccountLogLogic::recordUserTokensLog(
                    true, $userId, $changeType, $points, $task_id, $extra
                );

                Log::channel('device')->info("general_ocr 扣费成功 device_code={$params['device_code']} user_id={$userId} 扣费={$points} task_id={$task_id}");

                self::$returnData = $response;
                return true;
            } else {
                Log::channel('device')->warning("general_ocr 中台返回失败 device_code={$params['device_code']} msg=" . ($response['msg'] ?? 'unknown'));
                self::setError($response['msg'] ?? 'OCR识别失败');
                return false;
            }
        } catch (\Throwable $e) {
            Log::channel('device')->error("general_ocr 异常 device_code={$params['device_code']} error=" . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            self::setError($e->getMessage());
            return false;
        }
    }
}
