<?php

namespace app\common\service\aiPersona;

use app\common\enum\DeviceEnum;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\wechat\AiWechatCircleTask;
use think\facade\Log;

/**
 * 人设运行模式切换后的设备 24h 任务收尾
 *
 * 运行模式只影响「发布」这一类任务：手机模式由绑定手机发，无手机模式改成云端出片、
 * 用户自己发，手机不建内容发布/朋友圈发布任务；抓爆款、养号、客服、获客、私域、线索
 * 这些跟发布无关的自动化，两种模式下都照常跑。
 *
 * 24h 任务生成入口：
 *   - `auto_device_create_cron`（每天 0:00-3:00 每分钟 15 台）：按 `is_first=0` 的设备建当天运营/爆款任务
 *   - `auto_device_publish_create_cron`（每天 0:00-6:00 每分钟 2 台）：成品库直发的内容发布/朋友圈发布建单（publish_mode / wechat_publish_mode = 2）
 *   - `auto_device_frist_create_cron`（每 5 分钟）：按 `is_first=1` 的设备立刻补建（含发布），建完置回 0
 *
 * 所以模式一变，当天已经建好的任务面貌就对不上了，而改 run_mode 的接口原来什么都不做：
 *   - 切到无手机模式：当天已建的发布任务留在库里，`auto_task:scheduler` 不看 run_mode，
 *     照样往手机上派，等于用户切了模式手机还在自己发；
 *   - 切回手机模式：发布任务要等到次日 0 点那一轮才补上，当天整天不发。
 *     人设类型变更、报告重建、工作流解绑都会置 `is_first=1` 让 5 分钟那条 cron 当天补上，
 *     唯独运行模式漏了这一步。
 *
 * 本服务补齐这两侧，切模式的接口写完人设表后调一次即可。
 */
class PersonaRunModeSwitchService
{
    /** 由运行模式决定建不建的任务场景：内容发布 / 朋友圈发布 */
    private const PUBLISH_SCENES = [
        DeviceEnum::AUTO_TASK_SCENE_CONTENT_PUBLISH,
        DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH,
    ];

    /**
     * 运行模式变更后的设备处置（模式没变则不动任何东西）
     *
     * @param int $personaId 人设 ID
     * @param int $oldMode   变更前 run_mode
     * @param int $newMode   变更后 run_mode
     */
    public static function apply(int $personaId, int $oldMode, int $newMode): void
    {
        if ($personaId <= 0) {
            return;
        }

        $oldMode = AiPersona::normalizeRunMode($oldMode);
        $newMode = AiPersona::normalizeRunMode($newMode);
        if ($oldMode === $newMode) {
            return;
        }

        // auto_type=0 的设备本来就不进这两条 cron，不用管
        $devices = SvDevice::where('persona_id', $personaId)
            ->where('auto_type', 1)
            ->select();
        if ($devices->isEmpty()) {
            self::log($personaId, $oldMode, $newMode, [], '无绑定的自动化设备');
            return;
        }

        $handled = [];
        foreach ($devices as $device) {
            $deviceCode = (string)$device->device_code;
            // 单台设备失败不该把用户的切模式操作整个顶回去：运行模式本身已经写进人设表，
            // 兜底还有次日 0 点那一轮，这里只记日志继续处理其余设备。
            try {
                if ($newMode === AiPersona::RUN_MODE_CLOUD) {
                    $handled[$deviceCode] = self::cancelTodayPublishTasks($deviceCode);
                } else {
                    // 交给 auto_device_frist_create_cron（每 5 分钟）当天补建发布任务，
                    // 已存在的时段槽位由 SvDeviceTaskExistenceService 去重，不会建重复
                    SvDevice::where('device_code', $deviceCode)->update([
                        'is_first'    => 1,
                        'update_time' => time(),
                    ]);
                    $handled[$deviceCode] = ['marked_rebuild' => 1];
                }
            } catch (\Throwable $e) {
                Log::channel('auto')->write(
                    '人设' . $personaId . '运行模式切换处理设备失败：' . json_encode([
                        'device_code' => $deviceCode,
                        'old_mode'    => $oldMode,
                        'new_mode'    => $newMode,
                        'error'       => $e->getMessage(),
                    ], JSON_UNESCAPED_UNICODE),
                    'create'
                );
            }
        }

        self::log(
            $personaId,
            $oldMode,
            $newMode,
            $handled,
            $newMode === AiPersona::RUN_MODE_CLOUD ? '当天发布任务已取消' : '已标记当天补建发布任务'
        );
    }

    /**
     * 取消当天还没开始的发布任务及其发布明细
     *
     * 只动「未开始」的（status=0）：已经派给手机在跑的那条让它跑完，
     * 半路抽掉明细只会让手机端报错，收益也只有一条。
     */
    private static function cancelTodayPublishTasks(string $deviceCode): array
    {
        $day = date('Y-m-d');
        $dayStart = date('Y-m-d 00:00:00');
        $dayEnd = date('Y-m-d 23:59:59');

        $tasks = SvDeviceTask::where('device_code', $deviceCode)
            ->where('auto_type', 1)
            ->where('day', $day)
            ->where('status', DeviceEnum::TASK_STATUS_WAIT)
            ->whereIn('task_scene', self::PUBLISH_SCENES)
            ->select();
        $taskCount = count($tasks);
        if ($taskCount > 0) {
            $tasks->delete();
        }

        $details = SvPublishSettingDetail::where('device_code', $deviceCode)
            ->where('auto_type', 1)
            ->whereBetween('publish_time', [$dayStart, $dayEnd])
            ->select();
        $detailCount = count($details);
        if ($detailCount > 0) {
            $details->delete();
        }

        $circles = AiWechatCircleTask::where('device_code', $deviceCode)
            ->where('auto_type', 1)
            ->whereBetween('send_time', [$dayStart, $dayEnd])
            ->select();
        $circleCount = count($circles);
        if ($circleCount > 0) {
            $circles->delete();
        }

        return [
            'tasks'           => $taskCount,
            'publish_details' => $detailCount,
            'circle_tasks'    => $circleCount,
        ];
    }

    private static function log(int $personaId, int $oldMode, int $newMode, array $devices, string $action): void
    {
        Log::channel('auto')->write(
            '人设运行模式切换：' . json_encode([
                'persona_id' => $personaId,
                'old_mode'   => $oldMode,
                'new_mode'   => $newMode,
                'action'     => $action,
                'devices'    => $devices,
            ], JSON_UNESCAPED_UNICODE),
            'create'
        );
    }
}
