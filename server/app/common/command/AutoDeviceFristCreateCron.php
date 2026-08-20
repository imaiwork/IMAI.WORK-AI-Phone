<?php


namespace app\common\command;

use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use app\common\service\sv\SvDeviceTaskExistenceService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class AutoDeviceFristCreateCron extends Command
{
    protected function configure()
    {
        $this->setName('auto_device_frist_create_cron')
            ->setDescription('自动创建设备相关任务-初次创建');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $summary = [
                'devices_total' => 0,
                'viral_created' => 0,
                'viral_created_from_record_pool' => 0,
                'viral_skipped_existing' => 0,
                'daily_task_created' => 0,
                'daily_task_skipped_existing' => 0,
                'daily_task_skipped_no_account' => 0,
                'daily_task_skipped_empty_schedule' => 0,
            ];

            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode, p.wechat_publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                //->where('p.status', 1)
                ->where('d.is_first', 1)
                ->where('d.persona_id', '>', 0)
                ->select();
            \think\facade\Log::channel('auto')->write("\n初次创建设备相关任务SQL：" . \think\facade\Db::getLastSql(), 'create');
            \think\facade\Log::channel('auto')->write("初次创建设备相关任务设备列表：" . json_encode(array_column($devices->toArray(), 'device_code')), 'create');
            $summary['devices_total'] = count($devices);
            foreach ($devices as $device) {
                \think\facade\Log::channel('auto')->write("\n" . $device->device_code . '初次自动化任务开始', 'create');
                \app\common\model\sv\SvDevice::where('device_code', $device->device_code)->update([
                    'is_first' => 0,
                    'update_time' => time(),
                ]);

                $options = AiPersonaOptionService::getOptionsByPersonaId((int)$device->persona_id);
                $deviceSlotResult = SvDeviceTaskExistenceService::emptySlotResult();
                Log::channel('auto')->write($device->device_code . '初次自动化任务设备检查：' . json_encode([
                    'persona_id' => (int)$device->persona_id,
                    'options' => $options,
                ], JSON_UNESCAPED_UNICODE), 'create');
                $viralResult = \app\api\logic\aiPersona\ViralRewriterLogic::autoViralRewriterTaskCron($device);
                if (is_array($viralResult)) {
                    $summary['viral_created'] += (int)($viralResult['created'] ?? 0);
                    $summary['viral_created_from_record_pool'] += (int)($viralResult['created_from_record_pool'] ?? 0);
                    $summary['viral_skipped_existing'] += (int)($viralResult['skipped_existing'] ?? 0);
                }

                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.options.sph_clues')) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\PreciseCluesLogic::autoPreciseCluesTaskCron($device))
                    );
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.options.sph_clues=0，跳过精准线索任务', 'create');
                }
                $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                    $deviceSlotResult,
                    $this->normalizeSlotResult(\app\api\logic\aiPersona\ActiveLogic::autoActiveTaskCron($device))
                );
                if (AiPersonaOptionService::isEnabled($options, 'auto_clues.status')) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\ClueTouchLogic::trafficTaskCron($device))
                    );
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.auto_clues.status=0，跳过获客截流任务', 'create');
                }

                if (AiPersonaOptionService::isEnabled($options, 'private_operation.status')) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\InteractiveLogic::autoInteractiveTaskCron($device))
                    );
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.private_operation.status=0，跳过私域运营任务', 'create');
                }

                if (AiPersonaOptionService::isEnabled($options, 'customer_service')) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\TakeOverLogic::autoTakeOverTaskCron($device))
                    );
                } else {
                    Log::channel('auto')->write($device->device_code . ' global_option.customer_service=0，跳过智能客服任务', 'create');
                }
                if ($device->publish_mode == 2) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\PublishLogic::materialPersonaPublishCron($device))
                    );
                }

                if ($device->wechat_publish_mode == 2) {
                    $deviceSlotResult = SvDeviceTaskExistenceService::mergeSlotResult(
                        $deviceSlotResult,
                        $this->normalizeSlotResult(\app\api\logic\aiPersona\PublishLogic::materialCirclePersonaPublishCron($device))
                    );
                }

                $summary['daily_task_created'] += (int)$deviceSlotResult['created'];
                $summary['daily_task_skipped_existing'] += (int)$deviceSlotResult['skipped_existing'];
                $summary['daily_task_skipped_no_account'] += (int)$deviceSlotResult['skipped_no_account'];
                $summary['daily_task_skipped_empty_schedule'] += (int)$deviceSlotResult['skipped_empty_schedule'];

                \think\facade\Log::channel('auto')->write($device->device_code . "初次自动化任务完成\n\n", 'create');
            }
            Log::channel('auto')->write('AutoDeviceFristCreateCron执行汇总：' . json_encode($summary, JSON_UNESCAPED_UNICODE), 'create');
            return true;
        } catch (\Exception $e) {
            \think\facade\Log::channel('auto')->write('初次自动化失败' . $e->getMessage(), 'create');
            return false;
        } finally {
            \think\facade\Log::channel('auto')->write("初次自动化任务结束\n\n", 'create');
            return true;
        }
    }

    private function normalizeSlotResult(mixed $result): array
    {
        if (!is_array($result)) {
            return SvDeviceTaskExistenceService::emptySlotResult();
        }

        return [
            'created' => (int)($result['created'] ?? 0),
            'skipped_existing' => (int)($result['skipped_existing'] ?? 0),
            'skipped_no_account' => (int)($result['skipped_no_account'] ?? 0),
            'skipped_empty_schedule' => (int)($result['skipped_empty_schedule'] ?? 0),
        ];
    }
}
