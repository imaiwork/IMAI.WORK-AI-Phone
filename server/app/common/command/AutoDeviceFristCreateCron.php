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

            $devices = AutoDeviceConfig::where('is_first', 0)->select();
            foreach ($devices as $device) {
                if (!$this->getAutoConfigStatus($device)) {
                    continue;
                }
                if ((int)$device->is_first === 0) {
                    ActiveLogic::detail([
                        'user_id' => $device->user_id,
                        'device_code' => $device->device_code,
                        'exec_date' => \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $device->user_id)->where('device_code', $device->device_code)->value('exec_date'),
                    ]);
                    AddWechatLogic::autoAddWechatTaskCron(1, $device->device_code); //自动添加微信任务
                    ClueLogic::autoClueTaskCron(1, $device->device_code); //自动视频号获客任务
                    TakeOverLogic::autoTakeoverTaskCron(1, $device->device_code); //自动私信接管任务
                    ActiveLogic::autoActiveTaskCron(1, $device->device_code); //自动养号任务
                    TouchLogic::autoTouchTaskCron(1, $device->device_code); //截流获客任务
                    $device->is_first = 1;
                    $device->save();
                }
            }
            return true;
        } catch (\Exception $e) {
            Log::channel('auto')->info('初次自动化失败' . $e->getMessage());
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

    private function getAutoConfigStatus($find)
    {

        $clue_setting = \app\common\model\auto\AutoDeviceClueConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        $touch_setting = \app\common\model\auto\AutoDeviceTouchConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        $takeover_setting = \app\common\model\auto\AutoDeviceTakeOverConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        //$active_setting = \app\common\model\auto\AutoDeviceActiveConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        $publish_setting = \app\common\model\auto\AutoDeviceSetting::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        $add_wechat_setting = \app\common\model\auto\AutoDeviceAddWechatConfig::where('user_id', $find->user_id)->where('device_code', $find->device_code)->findOrEmpty()->isEmpty() ? 0 : 1;
        if ($clue_setting === 1 && $touch_setting === 1 && $takeover_setting === 1 && $publish_setting === 1 && $add_wechat_setting === 1) {
            return true;
        }
        $msg = array(
            'device_code' => $find->device_code,
            'msg' => '设备' . $find->device_code . '初次自动化失败, 任务未配置完成',
            'clue_setting' => $clue_setting,
            'touch_setting' => $touch_setting,
            'takeover_setting' => $takeover_setting,
            'publish_setting' => $publish_setting,
            'add_wechat_setting' => $add_wechat_setting,
        );
        Log::channel('auto')->info(json_encode($msg, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return false;
    }
}
