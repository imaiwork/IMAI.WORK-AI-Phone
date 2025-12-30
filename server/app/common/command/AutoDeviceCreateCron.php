<?php


namespace app\common\command;

use app\api\logic\auto\ClueLogic;
use app\api\logic\auto\TouchLogic;
use app\api\logic\auto\ActiveLogic;
use app\api\logic\auto\TakeOverLogic;
use app\api\logic\auto\AddWechatLogic;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\logic\auto\PublishLogic;
use app\common\model\auto\AutoDeviceConfig;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * AiWechatCron
 * @desc AI微信消息推送
 * @author dagouzi
 */
class AutoDeviceCreateCron extends Command
{
    protected function configure()
    {
        $this->setName('auto_device_create_cron')
            ->setDescription('自动创建设备相关任务');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $devices = AutoDeviceConfig::where('is_first', 1)->select();
            foreach ($devices as $device) {
                
                ActiveLogic::detail([
                    'user_id' => $device->user_id,
                    'device_code' => $device->device_code,
                    'exec_date' => \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $device->user_id)->where('device_code', $device->device_code)->value('exec_date'),
                ]);
                
                ClueLogic::autoClueTaskCron(0, $device->device_code); //自动视频号获客任务
                TouchLogic::autoTouchTaskCron(0, $device->device_code); //截流获客任务
                TakeOverLogic::autoTakeoverTaskCron(0, $device->device_code); //自动私信接管任务
                ActiveLogic::autoActiveTaskCron(0, $device->device_code);//自动养号任务
                AddWechatLogic::autoAddWechatTaskCron(0, $device->device_code); //自动添加微信任务
                AutoDeviceSettingLogic::processHumanImageData($device->device_code);//自动创建发布任务
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('auto')->info('自动化失败' . $e->getMessage());
            return false;
        } finally {
            return true;
        }
        // //自动创建设备视频发布任务
        // PublishLogic::setShanjianPublish();
        // //自动创建设备拼图发布任务
        // PublishLogic::setPuzzlePublish();
        // return true;
    }
}
