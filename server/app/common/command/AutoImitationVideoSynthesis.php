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
use app\api\logic\videoSynthesis\CopywritingImitationLogic;
use app\common\model\aiPersona\AiPersonaSynthesisConfig;
use app\common\model\aiPersona\AiPersonaSynthesisCopywriting;
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
class AutoImitationVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('auto_imitation_video_synthesis')
            ->setDescription('自动合成模仿视频任务');
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
            Log::channel('explosionVideoSynthesis')->info("ip人设自动合成模仿视频任务，任务触发源 - IP: {$clientIp}, UA: {$userAgent}");
            $copywritings = AiPersonaSynthesisCopywriting::alias('a')
                ->field('a.*,p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'a.persona_id = p.id')
                ->where('p.publish_mode', 1)
                ->where('a.use_state', 0)
                ->limit(5)
                ->select();
            foreach ($copywritings as $copywriting) {
                // 使用缓存判断60分钟内是否已执行，避免重复执行
                $cacheKey = 'command_video_synthesis_' . $copywriting->id;
                if ( Cache::store('material_redis')->has($cacheKey)) {
                    Log::channel('explosionVideoSynthesis')->write('设备60分钟内已执行过，跳过：' . $copywriting->id);
                    continue;
                }
                // 设置60分钟缓存
                Cache::store('material_redis')->set($cacheKey, 1, 3600);
                Log::channel('explosionVideoSynthesis')->write('ip人设自动合成模仿视频任务：' . $copywriting->id);
                try {
                    CopywritingImitationLogic::copywritingImitation($copywriting->id);
                } catch (\app\common\exception\MaterialNotReadyException $e) {
                    // 素材转码未就绪:清掉防重缓存,让下一轮cron继续重试
                    Cache::store('material_redis')->delete($cacheKey);
                    Log::channel('explosionVideoSynthesis')->write('素材转码未就绪,本轮跳过待下一轮：' . $copywriting->id . ' ' . $e->getMessage());
                } catch (\Throwable $e) {
                    Log::channel('explosionVideoSynthesis')->info('文案视频合成异常，id=' . $copywriting->id . '，错误：' . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('explosionVideoSynthesis')->info('ip人设自动合成模仿视频任务失败' . $e->getMessage());
            return false;
        } finally {
            print_r("\n ip人设模仿视频合成结束...'\n");

            return true;
        }
    }
}
