<?php


namespace app\api\logic\device;

use app\api\logic\ApiLogic;
use app\common\model\aiPersona\AiPersona;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceExecutionSchedule;
use app\common\model\sv\SvDeviceExecutionScheduleUser;
use app\common\model\sv\SvDeviceTask;
use think\facade\Db;

use app\common\model\marketing\MarketingTemplateSchedule;
use app\common\model\marketing\MarketingTemplate;
use app\common\model\aiPersona\AiPersonaWorkflowScheduleUser;
use app\common\enum\DeviceEnum;

/**
 * 设备计划任务逻辑
 * Class ScheduleLogic    
 * @package app\api\logic\device
 */
class ScheduleLogic extends ApiLogic
{

    public static function lists($params)
    {
        try {
            $device = SvDevice::where('device_code', $params['device_code'])->findOrEmpty();
            if ($device->isEmpty()) {
                self::setError('设备不存在');
                return false;
            }
            if ((int)$device->auto_type === 1) {
                $persona = AiPersona::where('user_id', self::$uid)->where('id', $device->persona_id)->limit(1)->findOrEmpty();
                if ($persona->isEmpty()) {
                    self::setError('请先配置人设');
                    return false;
                }

                if ($persona->workflow_template_id == 0) {
                    $persona = \app\api\logic\aiPersona\BasePersonaLogic::createPersonaExclusiveWorkflow($persona);
                }

                $schedule = MarketingTemplateSchedule::where('template_id', $persona->workflow_template_id)
                    ->where('scene', 'not in', [16, 17])
                    ->order('start_time', 'asc')
                    ->select()
                    ->each(function ($item) use ($persona) {
                        $item->status = 1;
                        $find = AiPersonaWorkflowScheduleUser::where('persona_id', $persona->id)
                            ->where('template_id', $persona->workflow_template_id)
                            ->where('scene', $item->scene)
                            ->where('start_time', $item->start_time)
                            ->where('end_time', $item->end_time)
                            ->order('id', 'desc')
                            ->limit(1)
                            ->findOrEmpty();
                        if ($find->isEmpty()) {
                            $item->status = 1;
                        } else {
                            $item->status = $find->status;
                        }
                        return $item;
                    })->toArray();
                if (!in_array(16, array_values(array_unique(array_column($schedule, 'scene'))))) {
                    $schedule = array_merge($schedule, DeviceEnum::getDefaultScheduleScene());
                    $key = array_column($schedule, 'start_time');
                    array_multisort($key, SORT_ASC, $schedule);
                }
                $isShowFlag = true;
                $config = \app\common\model\aiPersona\AiPersonaSynthesisConfig::where('persona_id', $persona->id)->order('id desc')->findOrEmpty();
                if ($config->isEmpty()) {
                    $isShowFlag = (int)$persona->persona_type !== 3;
                } else {
                    $isShowFlag = (int)$config->copywriting_source === \app\common\model\aiPersona\AiPersonaSynthesisConfig::COPYWRITING_SOURCE_IMITATE;
                }
                if (!$isShowFlag) {
                    $schedule = array_filter($schedule, function ($item) {
                        return $item['scene'] !== 16;
                    });
                    $schedule = array_values($schedule);
                }

                self::$returnData = $schedule;
            } else {
                $lists = SvDeviceTask::field('id,task_type,source,account_type,task_scene as scene,day,start_time,end_time')
                    ->where('user_id', self::$uid)
                    ->where('auto_type', 0)
                    ->where('device_code', $params['device_code'])
                    ->where('day', date('Y-m-d'))
                    ->order('start_time', 'asc')
                    ->select()
                    ->each(function ($item) {
                        $item->time = [date('H:i', $item->start_time), date('H:i', $item->end_time)];
                        $item->task_category = !in_array($item->source, [5, 7, 8]) ? DeviceEnum::getAccountTypeDesc($item->account_type) . DeviceEnum::getTaskTypeDesc($item->task_type) : DeviceEnum::getTaskSceneDesc($item->task_type);
                        if (
                            in_array($item->task_type, [DeviceEnum::TASK_TYPE_TAKEOVER, DeviceEnum::AUTO_TYPE_TAKE_OVER]) &&
                            $item->source == DeviceEnum::TASK_SOURCE_TAKEOVER &&
                            $item->account_type == DeviceEnum::ACCOUNT_TYPE_SPH
                        ) {
                            $item->task_category = '微信私信接管';
                        }
                        $item->platform = [
                            $item->account_type,
                        ];
                        $item->status = 1;
                        return $item;
                    })
                    ->toArray();
                self::$returnData = $lists;
            }
            return true;
        } catch (\Throwable $th) {
            self::setError($th->getMessage());
            return false;
        }
    }


    public static function update($params)
    {
        // 开启事务
        Db::startTrans();
        try {
            $schedule = MarketingTemplateSchedule::where('id', $params['id'])->findOrEmpty();
            if ($schedule->isEmpty()) {
                self::setError('执行计划不存在');
                return false;
            }
            $find = AiPersonaWorkflowScheduleUser::where('schedule_id', $params['id'])
                ->where('persona_id', $params['persona_id'])
                ->where('template_id', $schedule->template_id)
                ->where('scene', $schedule->scene)
                ->where('start_time', $schedule->start_time)
                ->where('end_time', $schedule->end_time)
                ->where('user_id', self::$uid)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $find = AiPersonaWorkflowScheduleUser::create([
                    'user_id' => self::$uid,
                    'persona_id' => $params['persona_id'],
                    'template_id' => $schedule->template_id,
                    'schedule_id' => $schedule->id,
                    'scene' => $schedule->scene,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => $params['status'],
                    'create_time' => time(),
                ]);
            } else {
                $find->status = $params['status'];
                $find->update_time = time();
                $find->save();
            }

            Db::commit();
            self::$returnData = $find->toArray();
            return true;
        } catch (\Throwable $th) {
            Db::rollback();
            //print_r($th->__toString());die;
            self::setError($th->getMessage());
            return false;
        }
    }
}
