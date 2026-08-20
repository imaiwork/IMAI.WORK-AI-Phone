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
class WechatLocalVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('wechat_local_video_synthesis')
            ->setDescription('微信本地自动合成设备视频任务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            print_r("\n 微信本地自动合成设备视频任务开始...'\n");
            // 使用Redis判断当天是否已执行，避免重复执行
            Log::channel('wechatVideoSynthesis')->write('微信本地自动合成设备视频任务');
            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                ->where('p.persona_type', 3)
                ->whereIn('p.wechat_publish_mode', [1, 3])
                //->where('p.is_configured', 1)
                ->where('p.status', 1)
                ->where('d.is_first', 0)
                ->where('d.persona_id', '>', 0)
                ->select();
            foreach ($devices as $device) {
                // 使用缓存判断60分钟内是否已执行，避免重复执行
                $cacheKey = 'wechat_video_synthesis_minute_' . $device->device_code;
                if ( Cache::store('redis')->has($cacheKey)) {
                    Log::channel('wechatVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $device->device_code); 
                    continue;
                }
                // 设置60分钟缓存
                Cache::store('redis')->set($cacheKey, 1, 3600);
                Log::channel('wechatVideoSynthesis')->write('微信本地自动合成设备视频任务：' . $device->device_code);
                try {
                    \app\api\logic\aiPersona\VideoSynthesis::wechatVideoSynthesis($device->device_code);
                } catch (\app\common\exception\MaterialNotReadyException $e) {
                    // 素材转码未就绪:清掉防重缓存,让下一轮 cron 重试
                    Cache::store('redis')->delete($cacheKey);
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
        // //自动创建设备视频发布任务
        // PublishLogic::setShanjianPublish();
        // //自动创建设备拼图发布任务
        // PublishLogic::setPuzzlePublish();
        // return true;
    }
}
