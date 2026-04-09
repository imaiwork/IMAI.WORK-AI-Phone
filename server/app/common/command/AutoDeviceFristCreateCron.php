<?php


namespace app\common\command;

use app\common\model\sv\SvDevice;
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

            $devices = SvDevice::alias('d')
                ->field('d.*, p.is_configured, p.status, p.persona_type, p.publish_mode')
                ->join('ai_persona p', 'd.persona_id = p.id')
                ->where('d.auto_type', 1)
                //->where('p.is_configured', 1)
                ->where('p.status', 1)
                ->where('d.is_first', 1)
                ->where('d.persona_id', '>', 0)
                //->where('d.device_code', '0c0d339d1e5c60679d')
                ->select();
            foreach ($devices as $device) {
                
                \app\api\logic\aiPersona\ActiveLogic::autoActiveTaskCron($device);//养号
                \app\api\logic\aiPersona\ClueTouchLogic::trafficTaskCron($device);//获客 截流 留痕
                \app\api\logic\aiPersona\InteractiveLogic::autoInteractiveTaskCron($device);//点赞评论 加好友
                \app\api\logic\aiPersona\TakeOverLogic::autoTakeoverTaskCron($device);//接管私信
                if ($device->publish_mode == 2) {
                    \app\api\logic\aiPersona\PublishLogic::materialPersonaPublishCron($device);//根据素材生成24h视频发布任务
                }

                \app\common\model\sv\SvDevice::where('device_code', $device->device_code)->update([
                    'is_first' => 0,
                    'update_time' => time(),
                ]);


            }
            return true;
        } catch (\Exception $e) {
            Log::channel('auto')->info('初次自动化失败' . $e->getMessage());
            return false;
        } finally {
            return true;
        }
    }
}
