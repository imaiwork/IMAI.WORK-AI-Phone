<?php


namespace app\common\command;

use app\api\logic\auto\ClueLogic;
use app\api\logic\auto\TouchLogic;
use app\api\logic\auto\ActiveLogic;
use app\api\logic\auto\TakeOverLogic;
use app\api\logic\auto\AddWechatLogic;
use app\api\logic\auto\LikeReplyLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\auto\AutoDeviceWechatCircleConfigLogic;
use app\api\logic\auto\PublishLogic;
use app\common\model\auto\AutoDeviceConfig;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;
use app\common\model\sv\SvDevice;
use think\facade\Cache;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class AutoDeviceVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('auto_device_video_synthesis')
            ->setDescription('自动合成设备视频任务');
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

            print_r("\n ip人设视频合成开始...'\n");
            $clientIp = request()->ip();
            $userAgent = request()->header('user-agent');
            Log::channel('ipVideoSynthesis')->info("ip人设自动合成设备视频任务，任务触发源 - IP: {$clientIp}, UA: {$userAgent}");
            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                ->where('d.synthesis_m', 0)
                //->where('p.is_configured', 1)
                ->where('p.status', 1)
                ->where('d.is_first', 0)
                ->where('d.persona_id', '>', 0)
                ->limit(10)
                ->select();
            foreach ($devices as $device) {
                // 使用缓存判断60分钟内是否已执行，避免重复执行
                $cacheKey = 'command_video_synthesis_' . $device->device_code;
                if ( Cache::store('material_redis')->has($cacheKey)) {
                    Log::channel('ipVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $device->device_code);
                    continue;
                }
                // 设置60分钟缓存
                Cache::store('material_redis')->set($cacheKey, 1, 3600);
                Log::channel('ipVideoSynthesis')->write('ip人设自动合成设备视频任务：' . $device->device_code);
                try {
                    \app\api\logic\videoSynthesis\CopywritingAiGenerationLogic::videoSynthesis($device->device_code);
                } catch (\app\common\exception\MaterialNotReadyException $e) {
                    // 素材转码未就绪:清掉防重缓存,让下一轮 cron 继续重试
                    Cache::store('material_redis')->delete($cacheKey);
                    Log::channel('ipVideoSynthesis')->write(
                        '素材转码未就绪,本轮跳过待下一轮，device_code=' . $device->device_code . '，' . $e->getMessage()
                    );
                } catch (\Throwable $e) {
                    Log::channel('ipVideoSynthesis')->info(
                        '设备视频合成异常 device=' . $device->device_code . ' err=' . $e->getMessage()
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('ipVideoSynthesis')->info('ip人设自动合成设备视频任务失败' . $e->getMessage());
            return false;
        } finally {
            print_r("\n ip人设视频合成结束...'\n");

            return true;
        }
    }
}
