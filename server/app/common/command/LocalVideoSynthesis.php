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
class LocalVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('local_video_synthesis')
            ->setDescription('本地自动合成设备视频任务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            print_r("\n 本地自动合成设备视频任务开始...'\n");
            // 使用Redis判断当天是否已执行，避免重复执行
            Log::channel('ipVideoSynthesis')->write('本地自动合成设备视频任务');
            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                ->where('p.persona_type', 3)
                //->where('p.is_configured', 1)
                ->where('p.status', 1)
                ->where('d.is_first', 0)
                ->where('d.persona_id', '>', 0)
                ->select();
            foreach ($devices as $device) {
                // 使用缓存判断60分钟内是否已执行，避免重复执行
                $cacheKey = 'video_synthesis_m_' . $device->device_code;
                if ( Cache::store('redis')->has($cacheKey)) {
                    Log::channel('ipVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $device->device_code);
                    continue;
                }
                // 设置60分钟缓存
                Cache::store('redis')->set($cacheKey, 1, 3600);
                Log::channel('ipVideoSynthesis')->write('ip人设自动合成设备视频任务：' . $device->device_code);
                \app\api\logic\device\DeviceLogic::videoSynthesis($device->device_code);
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('ipVideoSynthesis')->info('ip人设自动合成设备视频任务失败' . $e->getMessage());
            return false;
        } finally {
            print_r("\n ip人设视频合成结束...'\n");

            return true;
        }
        // //自动创建设备视频发布任务
        // PublishLogic::setShanjianPublish();
        // //自动创建设备拼图发布任务
        // PublishLogic::setPuzzlePublish();
        // return true;
    }
}
