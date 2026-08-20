<?php


namespace app\common\command;

use app\common\enum\DeviceEnum;
use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\sv\SvDevice;
use app\common\service\aiPersona\AiPersonaOptionService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;

/**
 * AiWechatCron
 * @desc 微信本地自动合成设备视频任务
 * @author dagouzi
 */
class WechatVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('wechat_video_synthesis')
            ->setDescription('微信本地自动合成设备视频任务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {

            $currentHour = date('H');
            $currentMinute = date('i');
            $currentTime = floatval($currentHour) + floatval($currentMinute) / 60;
            $startTime = 1.0;
            $endTime = 6.0;

            if ($currentTime < $startTime || $currentTime >= $endTime) {
                print_r("\n 当前时间不在执行时间段（01:00-06:00）内，任务跳过\n");
                return true;
            }
            print_r("\n 微信本地自动合成设备视频任务开始...'\n");
            $clientIp = request()->ip();
            $userAgent = request()->header('user-agent');
            Log::channel('wechatVideoSynthesis')->info("任务触发源 - IP: {$clientIp}, UA: {$userAgent}");
            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                ->whereIn('p.wechat_publish_mode', [1, 3])
                  ->where('d.synthesis_w', 0)
                ->where('p.status', 1)
                ->where('d.is_first', 0)
                ->where('d.persona_id', '>', 0)
                ->select();
            foreach ($devices as $device) {
                // 使用缓存判断60分钟内是否已执行，避免重复执行
                $cacheKey = 'command_wechat_video_synthesis_' . $device->device_code;
                if ( Cache::store('material_redis')->has($cacheKey)) {
                    Log::channel('wechatVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $device->device_code); 
                    continue;
                }
                if (!AiPersonaOptionService::isEnabledForPersonaId((int)$device->persona_id, 'video_clip')) {
                    Log::channel('wechatVideoSynthesis')->write('global_option.video_clip=0，跳过设备视频合成：' . $device->device_code);
                    SvDevice::where('device_code', $device->device_code)->update(['synthesis_w' => 1]);
                    continue;
                }

                // 对齐 AutoVideoSynthesis：无朋友圈发布时间段则跳过，且不锁 synthesis_w
                $publishCount = MarketingTemplateSchedule::getTodayPublishTaskCount(
                    (int)$device->persona_id,
                    DeviceEnum::AUTO_TASK_SCENE_WECHAT_CIRCLE_PUBLISH
                );
                if ($publishCount <= 0) {
                    Log::channel('wechatVideoSynthesis')->write('朋友圈发布时段为空，跳过设备视频合成：' . json_encode([
                        'device_code' => $device->device_code,
                        'persona_id' => (int)$device->persona_id,
                        'user_id' => (int)$device->user_id,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    continue;
                }

                Cache::store('material_redis')->set($cacheKey, 1, 3600);
                Log::channel('wechatVideoSynthesis')->write('微信本地自动合成设备视频任务：' . $device->device_code);
                try {
                    \app\api\logic\aiPersona\VideoSynthesis::wechatVideoSynthesis($device->device_code);
                } catch (\app\common\exception\MaterialNotReadyException $e) {
                    // 素材转码未就绪:清掉防重缓存,让下一轮 cron 重试
                    Cache::store('material_redis')->delete($cacheKey);
                    Log::channel('wechatVideoSynthesis')->write('素材转码未就绪,本轮跳过待下一轮：' . $device->device_code . ' ' . $e->getMessage());
                } catch (\Throwable $e) {
                    Log::channel('wechatVideoSynthesis')->info('设备视频合成异常 device=' . $device->device_code . ' err=' . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('wechatVideoSynthesis')->info('微信本地自动合成设备视频任务失败' . $e->getMessage());
            return false;
        } finally {
            print_r("\n 微信本地自动合成设备视频任务结束...'\n");
            return true;
        }
    }
}
