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
 * ResetVideoSynthesis
 * @desc 重置视频合成设备视频任务
 * @author dagouzi
 */
class ResetVideoSynthesis extends Command
{
    protected function configure()
    {
        $this->setName('reset_video_synthesis')
            ->setDescription('重置视频合成设备视频任务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            print_r("\n 重置视频合成设备视频任务开始...\n");
            $currentHour = date('H');
            $currentMinute = date('i');
            $currentTime = floatval($currentHour) + floatval($currentMinute) / 60;
            $startTime = 9.0;
            $endTime = 23.0;

            if ($currentTime < $startTime || $currentTime >= $endTime) {
                print_r("\n 时间非9:00-23:30，任务跳过\n");
                return true;
            }
            $mIds = SvDevice::where('synthesis_m', 1)->limit(30)->column('id');
            if (!empty($mIds)) {
                SvDevice::whereIn('id', $mIds)->update(['synthesis_m' => 0]);
                Log::channel('ipVideoSynthesis')->write('重置 synthesis_m 成功，ID列表：' . implode(',', $mIds));
                print_r("重置 synthesis_m 成功，数量：" . count($mIds) . "，ID列表：" . implode(',', $mIds) . "\n");
            } else {
                Log::channel('ipVideoSynthesis')->write('重置 synthesis_m 无数据');
                print_r("重置 synthesis_m 无数据\n");
            }

            $retryCount = SvDevice::where('synthesis_m_retry_count', '>', 0)
                ->update(['synthesis_m_retry_count' => 0]);
            Log::channel('ipVideoSynthesis')->write('重置 synthesis_m_retry_count 成功，数量：' . $retryCount);
            print_r("重置 synthesis_m_retry_count 成功，数量：{$retryCount}\n");

            $wIds = SvDevice::where('synthesis_w', 1)->limit(30)->column('id');
            if (!empty($wIds)) {
                SvDevice::whereIn('id', $wIds)->update(['synthesis_w' => 0]);
                Log::channel('wechatVideoSynthesis')->write('重置 synthesis_w 成功，ID列表：' . implode(',', $wIds));
                print_r("重置 synthesis_w 成功，数量：" . count($wIds) . "，ID列表：" . implode(',', $wIds) . "\n");
            } else {
                Log::channel('wechatVideoSynthesis')->write('重置 synthesis_w 无数据');
                print_r("重置 synthesis_w 无数据\n");
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('wechatVideoSynthesis')->info('重置视频合成设备视频任务失败' . $e->getMessage());
            return false;
        } finally {
            print_r("\n 重置视频合成设备视频任务结束...\n");
            return true;
        }
    }
}
