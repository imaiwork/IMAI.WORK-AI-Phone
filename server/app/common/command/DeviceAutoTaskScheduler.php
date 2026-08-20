<?php

declare(strict_types=1);

namespace app\common\command;

use app\api\logic\ApiLogic;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\model\sv\SvDeviceTaskLog;
use app\common\model\sv\SvDeviceViral;
use app\common\model\wechat\AiWechatCircleTask;
use app\common\service\aiPersona\ViralRewriterDeadlineFallbackService;
use app\common\traits\DeviceAutoTaskTrait;
use app\common\traits\TaskExceptionTrait;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

class DeviceAutoTaskScheduler extends Command
{
    use DeviceAutoTaskTrait;
    use TaskExceptionTrait;

    private $isDev = false;

    private int $noticeType = 404;

    private ?ViralRewriterDeadlineFallbackService $fallbackService = null;
    /**
     * 配置指令
     */
    protected function configure()
    {
        $this->setName('auto_task:scheduler')
            ->setDescription('设备自动化任务调度器')
            ->addOption('daemon', 'd', Option::VALUE_NONE, '以守护进程方式运行')
            ->addOption('interval', 'i', Option::VALUE_OPTIONAL, '检查间隔时间(秒)', 60)
            ->addOption('isdev', 'c', Option::VALUE_NONE, '是否开发模式');
    }

    /**
     * 执行命令
     */
    protected function execute(Input $input, Output $output)
    {
        $isDaemon = $input->getOption('daemon');
        $interval = (int)$input->getOption('interval');
        $this->isDev = (bool)$input->getOption('isdev');

        print_r("\n设备24小时自动化任务调度器启动...'\n");

        if ($this->isDev) {
            $output->writeln("检查间隔: {$interval}秒");
            $output->writeln('按 Ctrl+C 退出');
        }

        if ($isDaemon) {
            $this->runAsDaemon($output, $interval);
        } else {
            $this->runOnce($output);
        }

        return 0;
    }

    /**
     * 以守护进程方式运行
     */
    protected function runAsDaemon(Output $output, int $interval)
    {
        while (true) {
            $this->checkAndExecuteTasks($output);
            sleep($interval);
        }
    }

    /**
     * 单次运行
     */
    protected function runOnce(Output $output)
    {
        $this->checkAndExecuteTasks($output);
    }

    /**
     * 检查并执行任务
     */
    protected function checkAndExecuteTasks(Output $output)
    {
        $currentTime = time();

        try {
            // 0. 爆款仿写兜底：视频3点至6点，图文5点至7点
            $this->getFallbackService()->scanAndTrigger($currentTime, $this->isDev, $output);
            // 3. 检查超时未开始的任务 (status=0 且当前时间超过end_time)
            $this->checkTimeoutTasks($currentTime, $output);

            // 1. 检查需要开始执行的任务 (status=0 且当前时间在区间内)
            $this->checkPendingTasks($currentTime, $output);

            // 2. 检查需要结束的任务 (status=1 且当前时间超过end_time)
            $this->checkRunningTasks($currentTime, $output);
        } catch (\Exception $e) {
            $this->setTaskLog("设备任务调度器执行异常: " . $e->__toString(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>执行异常: " . $e->getMessage() . "</error>");
            }
        }
    }

    /**
     * 检查待执行任务
     */
    protected function checkPendingTasks(int $currentTime, Output $output)
    {
        $pendingTasks = SvDeviceTask::alias('t')
            ->field('t.*, p.persona_type, FROM_UNIXTIME(t.start_time) as start_time_str, FROM_UNIXTIME(t.end_time) as end_time_str')
            ->join('ai_persona p', 'p.id = t.persona_id and p.user_id = t.user_id')
            ->where('t.status', '=', 0) // 只处理已标记为执行中的任务
            ->where('t.device_code', 'in', function ($query) {
                $query->name('sv_device')->field('device_code')->where('auto_type', '=', 1);
            })
            ->where('t.auto_type', '=', 1) // 只处理自动任务
            ->where('t.start_time', '<=', $currentTime)
            ->where('t.end_time', '>', $currentTime)
            ->where('t.persona_id', '>', 0)
            ->order('t.start_time', 'asc')
            //->limit(10)
            ->select();
        //$this->setTaskLog(\think\facade\Db::getLastSql());
        foreach ($pendingTasks as $task) {
            try {
                if (!\app\api\logic\aiPersona\BasePersonaLogic::checkScheduleIsCreate([
                    'user_id' => $task->user_id,
                    'device_code' => $task->device_code,
                    'persona_id' => $task->persona_id,
                    'scene' => $task->task_scene,
                    'start_time' => date('H:i', $task->start_time),
                    'end_time' => date('H:i', $task->end_time),
                    'time_config' => json_decode($task->time_config, true),
                ])) {
                    $task->status = 2;
                    $task->remark = '该时段任务已关闭';
                    $task->update_time = $currentTime;
                    $task->save();
                } else {
                    // 更新任务状态为执行中
                    $claimed = SvDeviceTask::where('id', $task->id)
                        ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                        ->update([
                            'status' => DeviceEnum::TASK_STATUS_RUNNING,
                            'update_time' => $currentTime,
                        ]);
                    if (!$claimed) {
                        $this->setTaskLog("auto task already claimed ID={$task->id}", 'warning');
                        continue;
                    }
                    $task->status = DeviceEnum::TASK_STATUS_RUNNING;
                    $task->update_time = $currentTime;
                    // 执行具体任务
                    $this->executeDeviceTask($task, $output);
                }


                // $output->writeln("[" . date('Y-m-d H:i:s') . "] 开始执行任务 ID: {$task['id']} - {$task['task_name']}");
                // $this->setTaskLog("设备任务开始执行: ID={$task['id']}, 任务名称={$task['task_name']}, 设备={$task['device_code']}");
            } catch (\Exception $e) {
                $this->setTaskLog("开始执行任务失败 ID: {$task['id']} - " . $e->getTraceAsString(), 'error');

                if ($this->isDev) {
                    $output->writeln("<error>开始执行任务失败 ID: {$task['id']}</error>");
                }
            }
        }
    }

    /**
     * 检查执行中的任务
     */
    protected function checkRunningTasks($currentTime, Output $output)
    {
        $runningTasks = SvDeviceTask::field('*, FROM_UNIXTIME(start_time) as start_time_str, FROM_UNIXTIME(end_time) as end_time_str')
            ->where('status', 1) // 只处理已标记为执行中的任务
            ->where('auto_type', '=', 1) // 只处理自动任务
            ->where('device_code', 'in', function ($query) {
                $query->name('sv_device')->field('device_code')->where('auto_type', '=', 1);
            })
            ->select();

        foreach ($runningTasks as $task) {
            try {
                // 执行具体任务
                $this->executeDeviceCompletedTask($task, $output);

                // $output->writeln("[" . date('Y-m-d H:i:s') . "] 任务执行完成 ID: {$task['id']} - {$task['task_name']}");
                // $this->setTaskLog("设备任务执行完成: ID={$task['id']}, 任务名称={$task['task_name']}");
            } catch (\Exception $e) {
                $this->setTaskLog("更新任务完成状态失败 ID: {$task['id']} - " . $e->getTraceAsString(), 'error');

                if ($this->isDev) {
                    $output->writeln("<error>更新任务完成状态失败 ID: {$task['id']}</error>");
                }
            }
        }
    }

    /**
     * 检查超时任务
     */
    protected function checkTimeoutTasks(int $currentTime, Output $output)
    {
        $timeoutTasks = SvDeviceTask::field('*')
            ->where('status', 0) // 只处理已标记为执行中的任务
            ->where('device_code', 'in', function ($query) {
                $query->name('sv_device')->field('device_code')->where('auto_type', '=', 1);
            })
            ->where('auto_type', '=', 1) // 只处理自动任务
            ->where('end_time', '<', $currentTime)
            ->select();

        foreach ($timeoutTasks as $task) {
            try {
                // 更新任务状态为执行失败（超时未执行）

                $task->status = 3;
                $task->remark = '执行任务的设备没有提前开启';
                $task->update_time = $currentTime;
                $task->save();
                if ($this->isDev) {
                    $output->writeln("[" . date('Y-m-d H:i:s') . "] 任务超时未执行 ID: {$task['id']} - {$task['task_name']}");
                }

                $this->setTaskLog("设备任务超时未执行: ID={$task['id']}, 任务名称={$task['task_name']}", 'warning');
            } catch (\Exception $e) {
                $this->setTaskLog("更新任务超时状态失败 ID: {$task['id']} - " . $e->__toString(), 'error');
                if ($this->isDev) {
                    $output->writeln("<error>更新任务超时状态失败 ID: {$task['id']}</error>");
                }
            }
        }
    }

    /**
     * 执行具体的设备任务
     */
    protected function executeDeviceTask(SvDeviceTask $task, Output $output)
    {
        try {
            // 根据任务类型执行不同的业务逻辑
            switch ((int)$task->task_type) {
                case DeviceEnum::AUTO_TYPE_CLUES: // 获客任务
                    $this->executeCluesTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_PUBLISH: // 发布任务
                    $this->executePublishTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_COMMENT_CLUE: // 评论区获客任务
                    $this->executeCommentClueTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_WECHAT_FRIEND: // 加好友任务
                    $this->executeAddWechatTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_ACTIVE: // 养号任务
                    $this->executeActiveTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_CLEAN_PHONE: // 清理手机任务
                    $this->executeCleanPhoneTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_TAKE_OVER: // 私信接管任务
                    $this->executeTakeOverTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE: // 朋友圈发布任务
                    $this->executeWechatCircleTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT: // 朋友圈点赞评论任务
                    $this->executeWechatCircleThumbCommentTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SAME_CITY_EXPOSURE: //同城曝光任务
                    $this->executeSameCityExposureTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SAME_CITY_CUTOFF: //同城截流任务
                    $this->executeSameCityCutoffTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_GROUP_BUY: // 团购任务
                    $this->executeGroupBuyTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SPH_THUMB: // 视频号点赞
                    $this->executeSphThumbTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_VIRAL_REWRITER: // 爆款仿写任务
                    $this->executeViralRewriterTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_PRECISE_CLUES: // 精准获客任务
                    $this->executePreciseCluesTask($task, $output);
                    break;
                default:
                    throw new \Exception("未知的任务类型: {$task->task_type}");
            }
        } catch (\Exception $e) {
            // 任务执行异常，更新状态为执行失败
            $task->remark = '任务执行失败：' . $e->getMessage();
            $task->status = 3;
            $task->update_time = time();
            $task->save();

            $this->setTaskLog("设备任务执行失败 ID: {$task->id} - " . $e->getTraceAsString(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>任务执行失败 ID: {$task->id} - " . $e->getMessage() . "</error>");
            }

            throw $e; // 重新抛出异常，让上层捕获
        }
    }

    /**
     * 执行设备任务完成逻辑
     */
    protected function executeDeviceCompletedTask(SvDeviceTask $task, Output $output)
    {
        try {
            // 根据任务类型执行不同的业务逻辑
            switch ((int)$task->task_type) {
                case DeviceEnum::AUTO_TYPE_CLUES: // 获客任务
                    $this->executeCluesCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_PUBLISH: // 发布任务
                    $this->executePublishCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_COMMENT_CLUE: // 评论区获客任务
                    $this->executeCommentClueCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_WECHAT_FRIEND: // 加好友任务
                    $this->executeAddWechatCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_ACTIVE: // 养号任务
                    $this->executeActiveCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_CLEAN_PHONE: // 清理手机任务
                    $this->executeCleanPhoneCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TYPE_TAKE_OVER: // 私信接管任务
                    $this->executeTakeOverCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE: // 朋友圈发布任务
                    $this->executeWechatCircleCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT: // 朋友圈点赞评论任务
                    $this->executeWechatCircleThumbCommentCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SAME_CITY_EXPOSURE: //同城曝光任务
                    $this->executeSameCityExposureCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SAME_CITY_CUTOFF: //同城截流任务
                    $this->executeSameCityCutoffCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_GROUP_BUY: // 团购任务
                    $this->executeGroupBuyCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SPH_THUMB: // 视频号点赞
                    $this->executeSphThumbCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_VIRAL_REWRITER: // 爆款仿写任务
                    $this->executeViralRewriterCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_PRECISE_CLUES: // 精准获客任务
                    $this->executePreciseCluesCompletedTask($task, $output);
                    break;
                default:
                    throw new \Exception("未知的任务类型: {$task->task_type}");
            }
        } catch (\Exception $e) {
            // 任务执行异常，更新状态为执行失败
            $task->remark = '任务执行失败：' . $e->getMessage();
            $task->status = 3;
            $task->update_time = time();
            $task->save();

            $this->setTaskLog("设备任务执行失败 ID: {$task->id} - " . $e->getMessage(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>任务执行失败 ID: {$task->id} - " . $e->getMessage() . "</error>");
            }

            throw $e; // 重新抛出异常，让上层捕获
        }
    }

    protected function executePreciseCluesTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行精准获客任务 - 设备: {$task->device_code}");
        }

        self::preciseCluesTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("精准获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executePreciseCluesCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行精准获客任务完成 - 设备: {$task->device_code}");
            }
            self::preciseCluesCompletedTask($task);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '精准获客任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行精准获客任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }


    protected function executeViralRewriterTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行爆款仿写任务 - 设备: {$task->device_code}");
        }

        self::viralRewriterTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvGroupBuyTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("爆款仿写任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeViralRewriterCompletedTask(SvDeviceTask $task, Output $output)
    {
        $fallbackResult = $this->getFallbackService()->triggerForTask($task);
        if ($fallbackResult['triggered'] ?? false) {
            $this->setTaskLog("爆款仿写兜底触发: ID={$task->id}, 设备={$task->device_code}, 结果={$fallbackResult['msg']}", 'viral_bottom');
        }

        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行爆款仿写任务完成 - 设备: {$task->device_code}");
            }
            self::viralRewriterCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '爆款仿写任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行爆款仿写任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceViral::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }

    protected function executeSphThumbTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行视频号点赞任务 - 设备: {$task->device_code}");
        }

        self::sphThumbTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvGroupBuyTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("视频号点赞任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeSphThumbCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行视频号点赞任务完成 - 设备: {$task->device_code}");
            }
            self::sphThumbCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '视频号点赞任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行视频号点赞任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' =>  $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }

    /**
     * 执行同城曝光任务
     */
    protected function executeSameCityExposureTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行同城曝光任务 - 设备: {$task->device_code}");
        }

        self::sameCityExposureTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCityExposureTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("同城曝光任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeSameCityExposureCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行同城曝光任务完成 - 设备: {$task->device_code}");
            }
            self::sameCityExposureCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '同城曝光任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行同城曝光任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCityExposureTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }

    /**
     * 执行同城截流任务
     */
    protected function executeSameCityCutoffTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行同城截流任务 - 设备: {$task->device_code}");
        }

        self::sameCityCutoffTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCityTouchTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("同城截流任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeSameCityCutoffCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行同城截流任务完成 - 设备: {$task->device_code}");
            }
            self::sameCityCutoffCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '同城截流任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行同城截流任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCityTouchTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }

    /**
     * 执行团购任务
     */
    protected function executeGroupBuyTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行团购任务 - 设备: {$task->device_code}");
        }

        self::groupBuyTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvGroupBuyTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("团购任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeGroupBuyCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行团购任务完成 - 设备: {$task->device_code}");
            }
            self::groupBuyCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '团购任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行团购任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvGroupBuyTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }


    protected function executeWechatCircleThumbCommentTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行朋友圈点赞评论任务 - 设备: {$task->device_code}");
        }

        self::wechatCircleThumbCommentTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceCircleLikeReply::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("朋友圈点赞评论任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeWechatCircleThumbCommentCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行点赞评论任务完成 - 设备: {$task->device_code}");
            }
            self::wechatCircleThumbCommentCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '点赞评论任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行点赞评论任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceCircleLikeReply::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
    }


    /**
     * 执行获客任务
     */
    protected function executeCluesTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行获客任务 - 设备: {$task->device_code}");
        }
        // TODO: 实现具体的获客逻辑
        self::sphCluesStartTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCrawlingTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 执行获客任务完成逻辑
     */
    protected function executeCluesCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行获客任务完成 - 设备: {$task->device_code}");
            }

            // TODO: 实现具体的获客完成逻辑
            // 例如：更新任务状态为完成
            self::sphCluesEndTask($task, $output, function ($result) use ($task) {
                $task->status = $result['status'];
                $task->remark = $result['remark'];
                $task->update_time = time();
                $task->save();
                \app\common\model\sv\SvCrawlingTask::where('id', $task->sub_task_id)->update(['status' => 3, 'update_time' => time()]);
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
                ApiLogic::sendNotice([
                    'userId' => $task->user_id,
                    'startTime' => $task->start_time_str,
                    'endTime' => $task->end_time_str,
                    'content' => \app\common\model\sv\SvCrawlingTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                    'status' => $task->status,
                    'autoType' => $task->auto_type,
                ], $this->noticeType);
            });
            $this->setTaskLog("获客任务完成: ID={$task->id}, 设备={$task->device_code}");
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }


    /**
     * 执行发布任务
     */
    protected function executePublishTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行发布任务 - 设备: {$task->device_code}");
        }

        // TODO: 实现具体的发布逻辑
        // 例如：调用设备服务发布内容
        self::rpaPublishTask($task, $output, function ($result) use ($task) {
            if ($result['status'] === -1) {
                SvDeviceTask::where('id', $task->id)
                    ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
                    ->update([
                        'status' => DeviceEnum::TASK_STATUS_WAIT,
                        'remark' => $result['remark'] ?? '',
                        'update_time' => time(),
                    ]);
                return;
            }

            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();

            // 失败时写入任务日志, 避免完成态回填时只看到设备进度文案
            if ((int)$result['status'] === DeviceEnum::TASK_STATUS_FAILED) {
                SvDeviceTaskLog::create([
                    'user_id' => $task->user_id,
                    'task_id' => $task->id,
                    'task_source' => $task->source,
                    'device_code' => $task->device_code,
                    'message' => (string)($result['remark'] ?? ''),
                    'image' => '',
                    'create_time' => time(),
                ]);
            } else {
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            }

            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });
        // 模拟任务执行
        $this->setTaskLog("发布任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 执行发布任务完成逻辑
     */
    protected function executePublishCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行发布任务完成 - 设备: {$task->device_code}");
            }

            // 已因算力不足等系统原因失败时, 只同步明细, 不再用设备进度日志覆盖失败原因
            $alreadyFailed = (int)$task->status === DeviceEnum::TASK_STATUS_FAILED;
            $preserveRemark = trim((string)$task->remark);
            $preserveSystemFail = $preserveRemark !== ''
                && (str_contains($preserveRemark, '算力不足') || str_starts_with($preserveRemark, '任务执行失败'));

            if (!$alreadyFailed) {
                $task->status = DeviceEnum::TASK_STATUS_FINISHED;
                $task->remark = '发布任务完成';
                $task->update_time = time();
                $task->save();
            }

            $publish =  \app\common\model\sv\SvPublishSettingDetail::where('id', $task->sub_data_id)->where('status', 3)->findOrEmpty();
            if (!$publish->isEmpty()) {
                $remark = $preserveSystemFail ? $preserveRemark : $this->getPublishRemark($task);
                $publish->status = 2;
                $publish->remark = $remark;
                $publish->update_time = time();
                $publish->save();

                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = $remark;
                $task->update_time = time();
                $task->save();
            }

            $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
                ->where('id', '<>', $task->id)
                ->where('task_type', DeviceEnum::TASK_TYPE_PUBLISH)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->update(['status' => 2, 'update_time' => time()]);
                // 检查是否还有其他账号在等待
                $account = \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->findOrEmpty();
                if (!$account->isEmpty()) {
                    // 检查是否还有其他账号在等待
                    $nextAccount = \app\common\model\sv\SvPublishSettingAccount::where('publish_id', $account->publish_id)->where('status', 1)->findOrEmpty();
                    if ($nextAccount->isEmpty()) {
                        // 没有其他账号在等待，更新发布设置为完成
                        \app\common\model\sv\SvPublishSetting::where('id', $account->publish_id)->update(['status' => 3, 'update_time' => time()]);
                    }
                }
            }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
            $this->setTaskLog("执行发布任务完成: ID={$task->id}, 设备={$task->device_code}");
        }
    }

    /**
     * 执行3客户互动任务
     */
    protected function executeWechatCircleTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行客户互动任务 - 设备: {$task->device_code}");
        }

        self::wechatCirclePublishTask($task, $output, function ($result) use ($task) {
            if ($result['status'] !== -1) {
                $task->status = $result['status'];
                $task->remark = $result['remark'];
                $task->update_time = time();
                $task->save();
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
                ApiLogic::sendNotice([
                    'userId' => $task->user_id,
                    'startTime' => $task->start_time_str,
                    'endTime' => $task->end_time_str,
                    'content' => \app\common\model\wechat\AiWechatCircleTaskConfig::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                    'status' => $task->status,
                    'autoType' => $task->auto_type,
                ], $this->noticeType);
            }
        });

        $this->setTaskLog("发布任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }
    /**
     * 执行3客户互动任务完成逻辑
     */
    protected function executeWechatCircleCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行客户互动任务 - 设备: {$task->device_code}");
        }
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行发布任务完成 - 设备: {$task->device_code}");
            }

            $completionResult = $this->getWechatCirclePublishCompletionResult($task);

            $task->status = $completionResult['success']
                ? DeviceEnum::TASK_STATUS_FINISHED
                : DeviceEnum::TASK_STATUS_FAILED;
            $task->remark = $completionResult['remark'];
            $task->update_time = time();
            $task->save();

            if (!$completionResult['success']) {
                $this->syncWechatCirclePublishFailed($task, $completionResult['remark']);
            }

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\wechat\AiWechatCircleTaskConfig::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
            $logPrefix = $completionResult['success'] ? '执行发布任务完成' : '执行发布任务失败';
            $this->setTaskLog("{$logPrefix}: ID={$task->id}, 设备={$task->device_code}, 结果={$completionResult['remark']}");
        }
    }

    private function getWechatCirclePublishCompletionResult(SvDeviceTask $task): array
    {
        $lastLog = SvDeviceTaskLog::where('task_id', $task->id)
            ->where('task_source', $task->source)
            ->where('device_code', $task->device_code)
            ->where('user_id', $task->user_id)
            ->order('id', 'desc')
            ->findOrEmpty();

        if ($lastLog->isEmpty()) {
            return [
                'success' => false,
                'remark' => '执行过程中设备断网导致任务中断',
            ];
        }

        $message = trim((string)$lastLog->message);
        if (in_array($message, ['发布完成', '发布任务完成'], true)) {
            return [
                'success' => true,
                'remark' => '发布任务完成',
            ];
        }

        return [
            'success' => false,
            'remark' => $message !== '' ?  '朋友圈发布失败：最后一条执行日志为 ' . $message : '朋友圈发布失败：最后一条执行日志为空',
        ];
    }

    private function syncWechatCirclePublishFailed(SvDeviceTask $task, string $remark): void
    {
        AiWechatCircleTask::where('id', $task->sub_data_id)->update([
            'send_status' => 3,
            'finish_time' => date('Y-m-d H:i:s'),
            'update_time' => time(),
        ]);
    }


    /**
     * 执行评论区获客任务
     */
    protected function executeCommentClueTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行截流获客任务 - 设备: {$task->device_code}");
        }

        try {
            switch ((int)$task->task_scene) {
                case DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT: // 评论区评论
                    $this->executeCommentToCommentTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG: // 评论区私信
                    $this->executeCommentToMsgTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE: // 留痕获客
                    $this->executeCommentToMarkClueTask($task, $output);
                    break;
                default:
                    throw new \Exception("未知的截流获客任务场景: {$task->task_scene}");
            }
        } catch (\Throwable $th) {
            $task->remark = '任务执行失败：' . $th->getMessage();
            $task->status = 3;
            $task->update_time = time();
            $task->save();

            $this->setTaskLog("设备任务执行失败 ID: {$task->id} - " . $th->getMessage(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>任务执行失败 ID: {$task->id} - " . $th->getMessage() . "</error>");
            }

            throw $th; // 重新抛出异常，让上层捕获
        }
        $this->setTaskLog("截流获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeCommentToCommentTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行评论区评论任务 - 设备: {$task->device_code}");
        }

        self::touchCommentToCommentTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("评论区评论任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeCommentToMsgTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行评论区私信任务 - 设备: {$task->device_code}");
        }

        self::touchCommentToMsgTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("评论区私信任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeCommentToMarkClueTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行留痕获客任务 - 设备: {$task->device_code}");
        }

        self::touchCommentToMarkClueTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("留痕获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }
    /**
     * 执行评论区获客任务完成逻辑
     */
    protected function executeCommentClueCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行评论区评论任务 - 设备: {$task->device_code}");
        }

        try {
            switch ((int)$task->task_scene) {
                case DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT: // 评论区评论
                    $this->executeCommentToCommentCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG: // 评论区私信
                    $this->executeCommentToMsgCompletedTask($task, $output);
                    break;
                case DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE: // 留痕获客
                    $this->executeCommentToMarkClueCompletedTask($task, $output);
                    break;
                default:
                    throw new \Exception("未知的评论区评论任务场景: {$task->task_scene}");
            }
        } catch (\Throwable $th) {
            $task->remark = '任务执行失败：' . $th->getMessage();
            $task->status = 3;
            $task->update_time = time();
            $task->save();

            $this->setTaskLog("设备任务执行失败 ID: {$task->id} - " . $th->getMessage(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>任务执行失败 ID: {$task->id} - " . $th->getMessage() . "</error>");
            }

            throw $th; // 重新抛出异常，让上层捕获
        }
    }

    protected function executeCommentToCommentCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行评论区评论任务完成 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的养号完成逻辑
            //self::rpaMaintainAccountEndTask($task, $output, function ($result) use ($task) {});

            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '评论区评论任务完成';
            $task->update_time = time();
            $task->save();

            // $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
            //     ->where('id', '<>', $task->id)
            //     ->where('task_type', DeviceEnum::AUTO_TYPE_COMMENT_CLUE)
            //     ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_COMMENT_COMMENT)
            //     ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            //     ->findOrEmpty();
            // if ($find->isEmpty()) {
            //     //截流子任务处理
            //     $account = \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty();
            //     if ($account->isEmpty()) {
            //         \app\common\model\sv\SvDeviceActive::where('id', $account->active_id)->update([
            //             'status' => DeviceEnum::TASK_STATUS_FINISHED,
            //             'update_time' => time(),
            //         ]);
            //     }
            // }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("评论区评论任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }


    protected function executeCommentToMsgCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行评论区私信任务完成 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的养号完成逻辑
            //self::rpaMaintainAccountEndTask($task, $output, function ($result) use ($task) {});

            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '评论区私信任务完成';
            $task->update_time = time();
            $task->save();

            // $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
            //     ->where('id', '<>', $task->id)
            //     ->where('task_type', DeviceEnum::AUTO_TYPE_COMMENT_CLUE)
            //     ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_COMMENT_MSG)
            //     ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            //     ->findOrEmpty();
            // if ($find->isEmpty()) {
            //     $account = \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty();
            //     if ($account->isEmpty()) {
            //         \app\common\model\sv\SvDeviceActive::where('id', $account->active_id)->update([
            //             'status' => DeviceEnum::TASK_STATUS_FINISHED,
            //             'update_time' => time(),
            //         ]);
            //     }
            // }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("评论区私信任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }


    protected function executeCommentToMarkClueCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行留痕获客任务完成 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的养号完成逻辑
            //self::rpaMaintainAccountEndTask($task, $output, function ($result) use ($task) {});

            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '留痕获客任务完成';
            $task->update_time = time();
            $task->save();

            // $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
            //     ->where('id', '<>', $task->id)
            //     ->where('task_type', DeviceEnum::AUTO_TYPE_COMMENT_CLUE)
            //     ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_MARK_CLUE)
            //     ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
            //     ->findOrEmpty();
            // if ($find->isEmpty()) {
            //     $account = \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty();
            //     if ($account->isEmpty()) {
            //         \app\common\model\sv\SvDeviceActive::where('id', $account->active_id)->update([
            //             'status' => DeviceEnum::TASK_STATUS_FINISHED,
            //             'update_time' => time(),
            //         ]);
            //     }
            // }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("留痕获客任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvLeadScrapingSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }

    /**
     * 执行加微任务
     */
    protected function executeAddWechatTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行加微任务 - 设备: {$task->device_code}");
        }

        // TODO: 实现具体的加微逻辑
        self::cluesAddWechatFriendTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });
        $this->setTaskLog("加微任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }


    /** 
     * 执行加好友任务完成逻辑
     */
    protected function executeAddWechatCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行加微任务完成 - 设备: {$task->device_code}");
            }
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '加微任务完成';
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("加微任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        }
        // else {
        //     if ($this->isDev) {
        //         $output->writeln("执行加微任务 - 设备: {$task->device_code}");
        //     }
        //     // TODO: 实现具体的加好友完成逻辑
        //     self::cluesAddWechatFriendTask($task, $output, function ($result) use ($task) {
        //         $task->status = $result['status'];
        //         $task->remark = $result['remark'];
        //         $task->update_time = time();
        //         $task->save();
        //         $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        //     });
        //     $this->setTaskLog("加微任务执行中: ID={$task->id}, 设备={$task->device_code}");
        // }
    }


    /**
     * 执行养号任务
     */
    protected function executeActiveTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行养号任务 - 设备: {$task->device_code}");
        }
        // TODO: 实现具体的养号逻辑
        self::rpaMaintainAccountTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });

        $this->setTaskLog("养号任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }


    /**
     * 执行养号任务完成逻辑
     */
    protected function executeActiveCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行养号任务完成 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的养号完成逻辑
            //self::rpaMaintainAccountEndTask($task, $output, function ($result) use ($task) {});
            if ($task->auto_type == 1) {
                $taskId = generate_unique_task_id();
                $start_time = $task->start_time;
                $end_time = $task->end_time;
                // 计算时间差（秒）
                $time_difference_seconds = $end_time - $start_time;
                // 将时间差转换为分钟，并向下取整
                $time_difference_minutes = ceil($time_difference_seconds / 60);
                // 养号按分钟计费:先按实扣额校验+扣费,再调远端(与 AutomationBillingService 一致)
                $request = [
                    'task_id' => $taskId,
                    'user_id' => $task->user_id,
                    'time_difference_minutes' => $time_difference_minutes,
                ];
                try {
                    \app\common\service\AutomationBillingService::requestAndCharge(
                        $request,
                        \app\common\enum\AutomationEnum::SOCIAL_MEDIA_NURSING,
                        (int)$task->user_id,
                        $taskId,
                        (string)$task->device_code
                    );
                } catch (\Throwable $e) {
                    // 算力不足:任务标记失败,不再当成完成
                    if ((int)$e->getCode() === 4059) {
                        $task->status = DeviceEnum::TASK_STATUS_FAILED;
                        $task->remark = $e->getMessage();
                        $task->update_time = time();
                        $task->save();
                        return;
                    }
                    throw $e;
                }
            }
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '养号任务完成';
            $task->update_time = time();
            $task->save();

            $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
                ->where('id', '<>', $task->id)
                ->where('task_type', DeviceEnum::TASK_TYPE_ACTIVE)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->where('source', DeviceEnum::TASK_SOURCE_ACTIVE)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $account = \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty();
                if ($account->isEmpty()) {
                    \app\common\model\sv\SvDeviceActive::where('id', $account->active_id)->update([
                        'status' => DeviceEnum::TASK_STATUS_FINISHED,
                        'update_time' => time(),
                    ]);
                }
            }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("养号任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceActiveAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }
    /**
     * 执行清理手机任务
     */
    protected function executeCleanPhoneTask(SvDeviceTask $task, Output $output) {}
    /**
     * 执行清理手机任务完成逻辑
     */
    protected function executeCleanPhoneCompletedTask(SvDeviceTask $task, Output $output) {}



    /**
     * 执行接管任务
     */
    protected function executeTakeoverTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行接管任务 - 设备: {$task->device_code}");
        }

        // TODO: 实现具体的接管逻辑
        self::rpaTakeoverTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceTakeOverTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        });
        $this->setTaskLog("私信接管任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 执行接管任务完成逻辑
     */
    protected function executeTakeoverCompletedTask(SvDeviceTask $task, Output $output)
    {

        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行接管任务完成 - 设备: {$task->device_code}");
            }

            // self::rpaTakeoverEndTask($task, $output, function ($result) use ($task) {});

            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '接管任务完成';
            $task->update_time = time();
            $task->save();

            $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
                ->where('id', '<>', $task->id)
                ->where('task_type', DeviceEnum::AUTO_TYPE_TAKE_OVER)
                ->where('task_scene', DeviceEnum::AUTO_TASK_SCENE_TAKE_OVER)
                ->where('status', DeviceEnum::TASK_STATUS_RUNNING)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                $account = \app\common\model\sv\SvDeviceTakeOverTaskAccount::where('id', $task->sub_task_id)->findOrEmpty();
                if ($account->isEmpty()) {
                    \app\common\model\sv\SvDeviceTakeOverTask::where('id', $account->take_over_id)->update([
                        'status' => DeviceEnum::TASK_STATUS_FINISHED,
                        'update_time' => time(),
                    ]);
                }
            }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("私信接管任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceTakeOverTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ], $this->noticeType);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }


    /** 
     * 检查设备绑定状态
     */
    protected function checkDeviceBinding($deviceCode): bool
    {
        // TODO: 实现设备绑定检查逻辑
        // 这里需要根据您的业务逻辑来判断设备是否绑定

        // 示例：查询设备绑定表
        // return Db::name('device_binding')->where('device_code', $deviceCode)->where('status', 1)->exists();

        // 暂时返回true，您需要根据实际业务实现
        return true;
    }

    private function updateDeviceStatus(string $deviceCode, int $status)
    {
        SvDevice::where('device_code', $deviceCode)->update(['status' => $status, 'update_time' => time()]);
    }
    private function setTaskLog(string|array $content, string $level = 'info')
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('auto')->{$level}($content);
    }

    private function getFallbackService(): ViralRewriterDeadlineFallbackService
    {
        if ($this->fallbackService === null) {
            $this->fallbackService = new ViralRewriterDeadlineFallbackService();
        }

        return $this->fallbackService;
    }
}
