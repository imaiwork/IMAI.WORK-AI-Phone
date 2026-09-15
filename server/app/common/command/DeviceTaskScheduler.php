<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Cache;
use think\facade\Log;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\enum\DeviceEnum;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\service\sv\PublishRemarkGuard;
use app\common\service\sv\PublishTaskReconcile;
use app\common\traits\DeviceTaskTrait;
use app\common\traits\TaskExceptionTrait;
use app\api\logic\ApiLogic;

class DeviceTaskScheduler extends Command
{
    use DeviceTaskTrait;
    use TaskExceptionTrait;

    private $isDev = false;

    private const CHUNK_SIZE = 200;
    private const MAX_RUN_SECONDS = 50;
    private const RUNNING_LOCK_KEY = 'task_scheduler:running';
    private const RUNNING_LOCK_TTL = 120;
    /**
     * 配置指令
     */
    protected function configure()
    {
        $this->setName('task:scheduler')
            ->setDescription('设备任务调度器')
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

        print_r("\n设备任务调度器启动...\n");
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
        $lockValue = (getmypid() ?: 0) . ':' . microtime(true);
        $lockAcquired = false;

        try {
            if (!$this->acquireRunningLock($lockValue)) {
                return;
            }
            $lockAcquired = true;

            $startedAt = time();
            // 3. 检查超时未开始的任务 (status=0 且当前时间超过end_time)
            $this->checkTimeoutTasks($currentTime, $output, $startedAt);
            // 1. 检查需要开始执行的任务 (status=0 且当前时间在区间内)
            $this->checkPendingTasks($currentTime, $output, $startedAt);
            // 2. 检查需要结束的任务 (status=1 且当前时间超过end_time)
            $this->checkRunningTasks($currentTime, $output, $startedAt);
        } catch (\Throwable $e) {
            $this->setTaskLog("设备任务调度器执行异常: " . $e->getMessage(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>执行异常: " . $e->getMessage() . "</error>");
            }
        } finally {
            if ($lockAcquired) {
                $this->releaseRunningLock($lockValue);
            }
        }
    }

    /**
     * 检查待执行任务
     */
    protected function checkPendingTasks(int $currentTime, Output $output, int $startedAt): void
    {
        $lastId = 0;
        $processed = 0;

        do {
            if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                $this->setTaskLog('设备任务调度器达到每轮时间上限, pending 提前退出', 'info');
                break;
            }

            $tasks = SvDeviceTask::alias('t')
                ->field('t.*, FROM_UNIXTIME(t.start_time) as start_time_str, FROM_UNIXTIME(t.end_time) as end_time_str')
                ->where('t.status', '=', 0)
                ->where('t.device_code', 'in', function ($query) {
                    $query->name('sv_device')->field('device_code')->where('auto_type', '=', 0);
                })
                ->where('t.auto_type', '=', 0)
                ->where('t.start_time', '<=', $currentTime)
                ->where('t.end_time', '>', $currentTime)
                ->where('t.id', '>', $lastId)
                ->order('t.id', 'asc')
                ->limit(self::CHUNK_SIZE)
                ->select();

            if ($tasks->isEmpty()) {
                break;
            }

            foreach ($tasks as $task) {
                try {
                    $task->status = 1;
                    $task->update_time = $currentTime;
                    $task->save();
                    $this->executeDeviceTask($task, $output);
                } catch (\Throwable $e) {
                    $this->setTaskLog("开始执行任务失败 ID: {$task['id']} - " . $e->getMessage(), 'error');
                    if ($this->isDev) {
                        $output->writeln("<error>开始执行任务失败 ID: {$task['id']}</error>");
                    }
                }

                $lastId = (int)$task['id'];
                $processed++;

                if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                    $this->setTaskLog('设备任务调度器达到每轮时间上限, pending 提前退出', 'info');
                    break 2;
                }
            }
        } while (count($tasks) === self::CHUNK_SIZE);

        if ($this->isDev && $processed > 0) {
            $output->writeln("[pending] 本轮处理 {$processed} 条, 最后 id={$lastId}");
        }
    }

    /**
     * 检查执行中的任务
     */
    protected function checkRunningTasks($currentTime, Output $output, int $startedAt): void
    {
        $lastId = 0;
        $processed = 0;

        do {
            if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                $this->setTaskLog('设备任务调度器达到每轮时间上限, running 提前退出', 'info');
                break;
            }

            $tasks = SvDeviceTask::field('*, FROM_UNIXTIME(start_time) as start_time_str, FROM_UNIXTIME(end_time) as end_time_str')
                ->where('status', 1)
                ->where('device_code', 'in', function ($query) {
                    $query->name('sv_device')->field('device_code')->where('auto_type', '=', 0);
                })
                ->where('auto_type', '=', 0)
                ->where('id', '>', $lastId)
                ->order('id', 'asc')
                ->limit(self::CHUNK_SIZE)
                ->select();

            if ($tasks->isEmpty()) {
                break;
            }

            foreach ($tasks as $task) {
                try {
                    $this->executeDeviceCompletedTask($task, $output);
                } catch (\Throwable $e) {
                    $this->setTaskLog("更新任务完成状态失败 ID: {$task['id']} - " . $e->getMessage(), 'error');
                    if ($this->isDev) {
                        $output->writeln("<error>更新任务完成状态失败 ID: {$task['id']}</error>");
                    }
                }

                $lastId = (int)$task['id'];
                $processed++;

                if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                    $this->setTaskLog('设备任务调度器达到每轮时间上限, running 提前退出', 'info');
                    break 2;
                }
            }
        } while (count($tasks) === self::CHUNK_SIZE);

        if ($this->isDev && $processed > 0) {
            $output->writeln("[running] 本轮处理 {$processed} 条, 最后 id={$lastId}");
        }
    }

    /**
     * 检查超时任务
     */
    protected function checkTimeoutTasks(int $currentTime, Output $output, int $startedAt): void
    {
        $lastId = 0;
        $processed = 0;

        do {
            if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                $this->setTaskLog('设备任务调度器达到每轮时间上限, timeout 提前退出', 'info');
                break;
            }

            $tasks = SvDeviceTask::field('*')
                ->where('device_code', 'in', function ($query) {
                    $query->name('sv_device')->field('device_code')->where('auto_type', '=', 0);
                })
                ->where('status', 0)
                ->where('auto_type', '=', 0)
                ->where('end_time', '<', $currentTime)
                ->where('id', '>', $lastId)
                ->order('id', 'asc')
                ->limit(self::CHUNK_SIZE)
                ->select();

            if ($tasks->isEmpty()) {
                break;
            }

            foreach ($tasks as $task) {
                try {
                    $this->processTimeoutTask($task, $currentTime, $output);
                } catch (\Throwable $e) {
                    $this->setTaskLog("更新任务超时状态失败 ID: {$task['id']} - " . $e->getMessage(), 'error');
                    if ($this->isDev) {
                        $output->writeln("<error>更新任务超时状态失败 ID: {$task['id']}</error>");
                    }
                }

                $lastId = (int)$task['id'];
                $processed++;

                if (time() - $startedAt >= self::MAX_RUN_SECONDS) {
                    $this->setTaskLog('设备任务调度器达到每轮时间上限, timeout 提前退出', 'info');
                    break 2;
                }
            }
        } while (count($tasks) === self::CHUNK_SIZE);

        if ($this->isDev && $processed > 0) {
            $output->writeln("[timeout] 本轮处理 {$processed} 条, 最后 id={$lastId}");
        }
    }

    /**
     * 处理单条超时任务
     */
    private function processTimeoutTask(SvDeviceTask $task, int $currentTime, Output $output): void
    {
        if ($task->task_type == DeviceEnum::TASK_TYPE_WECHAT_RPA) {
            $task->status = 2;
            $task->remark = '任务执行完成';
        } else {
            $task->status = 3;
            $task->remark = PublishTaskReconcile::decideTimeoutRemark(
                (string)$task->remark,
                (int)$task->task_type === DeviceEnum::TASK_TYPE_PUBLISH
            );
        }

        $task->update_time = $currentTime;
        $task->save();
        if ((int)$task->task_type === DeviceEnum::TASK_TYPE_DY_SEARCH) {
            self::dySearchCloseTimeoutUnstarted($task);
        }
        if ($this->isDev) {
            $output->writeln("[" . date('Y-m-d H:i:s') . "] 任务超时未执行 ID: {$task['id']} - {$task['task_name']}");
        }

        $this->setTaskLog("设备任务超时未执行: ID={$task['id']}, 任务名称={$task['task_name']}", 'warning');
    }

    /**
     * 执行具体的设备任务
     */
    protected function executeDeviceTask(SvDeviceTask $task, Output $output)
    {
        try {
            // 根据任务类型执行不同的业务逻辑
            switch ((int)$task->task_type) {
                case DeviceEnum::TASK_TYPE_PUBLISH: // 发布任务
                    $this->executePublishTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_TAKEOVER: // 接管任务
                    $this->executeTakeoverTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_ACTIVE: // 养号任务
                    $this->executeMaintainAccountTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_CLUES: // 获客任务
                    $this->executeCustomerAcquisitionTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_FRIENDS: // 加好友任务
                    $this->executeAddWechatTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_TOUCH: // 截流获客任务
                    $this->executeCommentClueTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE: //朋友圈发布
                    $this->executeWechatCircleTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT: // 点赞评论任务
                    $this->executeWechatCircleThumbCommentTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_CLUES_WECHAT: // 获客加微任务
                    $this->executeCluesWechatTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_RPA: // 微账号RPA任务
                    $this->executeWechatRPATask($task, $output);
                    break;
                 case DeviceEnum::TASK_TYPE_SPH_THUMB: // 视频号点赞
                    $this->executeSphThumbTask($task, $output);
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
                case DeviceEnum::TASK_TYPE_DY_SEARCH: // 18
                    $this->executeDySearchTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_DY_SEARCH_WECHAT: // 19
                    $this->executeDySearchWechatTask($task, $output);
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
                case DeviceEnum::TASK_TYPE_PUBLISH: // 发布任务
                    $this->executePublishCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_TAKEOVER: // 接管任务
                    $this->executeTakeoverCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_ACTIVE: // 养号任务
                    $this->executeMaintainAccountCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_CLUES: // 获客任务
                    $this->executeCustomerAcquisitionCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_FRIENDS: // 加好友任务
                    $this->executeAddWechatCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_TOUCH: // 截流获客任务
                    $this->executeCommentClueCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE: // 朋友圈发布任务
                    $this->executeWechatCircleCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_CIRCLE_THUMB_COMMENT: // 点赞评论任务
                    $this->executeWechatCircleThumbCommentCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_CLUES_WECHAT:
                    $this->executeCluesWechatCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_WECHAT_RPA: // 微账号RPA任务
                    $this->executeWechatRPACompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_SPH_THUMB: // 视频号点赞
                    $this->executeSphThumbCompletedTask($task, $output);
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
                case DeviceEnum::TASK_TYPE_DY_SEARCH: // 18
                    $this->executeDySearchCompletedTask($task, $output);
                    break;
                case DeviceEnum::TASK_TYPE_DY_SEARCH_WECHAT: // 19
                    $this->executeDySearchWechatCompletedTask($task, $output);
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
            ]);
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
            ]);
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
            ]);
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
            ]);
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
            ]);
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
            ]);
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
            ]);
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
            ]);
        }
    }

    protected function executeWechatRPATask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行微账号RPA任务 - 设备: {$task->device_code}");
        }

        // TODO: 实现具体的加微逻辑
        self::wechatRPATask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvWechatStrategy::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        });
        $this->setTaskLog("微账号RPA任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeWechatRPACompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行微账号RPA任务完成 - 设备: {$task->device_code}");
            }
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '微账号RPA任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("微账号RPA任务完成: ID={$task->id}, 设备={$task->device_code}");

            \app\common\model\sv\SvWechatStrategy::where('id', $task->sub_task_id)->update([
                'status' => 2,
                'update_time' => time(),
            ]);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvWechatStrategy::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        }
    }

    protected function executeCluesWechatTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行获客加微任务 - 设备: {$task->device_code}");
        }

        // TODO: 实现具体的加微逻辑
        self::cluesWechatTask($task, $output, true, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCrawlingWechatTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        });
        $this->setTaskLog("获客加微任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeCluesWechatCompletedTask(SvDeviceTask $task, Output $output)
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
                'content' => \app\common\model\sv\SvCrawlingWechatTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        }
        // else {
        //     if ($this->isDev) {
        //         $output->writeln("执行加微任务 - 设备: {$task->device_code}");
        //     }
        //     // TODO: 实现具体的加好友完成逻辑
        //     self::cluesWechatTask($task, $output, false, function ($result) use ($task) {
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
            $this->applyPublishDispatchResult($task, $result);
        });
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
            $this->closePublishTask($task);
            $this->finalizePublishAccount($task);
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行发布任务完成: ID={$task->id}, 设备={$task->device_code}");
            $this->sendPublishNotice($task);
            return;
        }

        if ($this->isDev) {
            $output->writeln("执行发布任务 - 设备: {$task->device_code}");
        }
        $this->reconcileRunningPublishTask($task, $output);
        $this->setTaskLog("发布任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 窗口内离线不写成终态失败。
     */
    private function applyPublishDispatchResult(SvDeviceTask $task, array $result): void
    {
        $decision = PublishTaskReconcile::decideDispatchResult(
            (int)$task->status,
            (int)$task->end_time,
            time(),
            (int)($result['status'] ?? 0),
            (string)($result['remark'] ?? '')
        );
        if (($decision['action'] ?? '') === PublishTaskReconcile::ACTION_NOOP) {
            return;
        }

        $task->status = (int)$decision['status'];
        $task->remark = (string)$decision['remark'];
        $task->update_time = time();
        $task->save();

        if (!empty($decision['working'])) {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
        if (!empty($decision['notify'])) {
            $this->sendPublishNotice($task);
        } elseif (($decision['action'] ?? '') === PublishTaskReconcile::ACTION_WAIT_OFFLINE) {
            $this->setTaskLog("发布任务设备离线，窗口内等待重试: ID={$task->id}, 设备={$task->device_code}", 'warning');
        }
    }

    /**
     * 窗口未结束：先认明细终态，已下发不再重发。
     */
    private function reconcileRunningPublishTask(SvDeviceTask $task, Output $output): void
    {
        $publish = SvPublishSettingDetail::where('id', $task->sub_data_id)->findOrEmpty();
        $detailStatus = $publish->isEmpty() ? null : (int)$publish->status;
        $decision = PublishTaskReconcile::decideRunningWindow($detailStatus);

        switch ($decision['action']) {
            case PublishTaskReconcile::WINDOW_FINISH:
                $task->status = DeviceEnum::TASK_STATUS_FINISHED;
                $remark = trim((string)$publish->remark);
                $task->remark = $remark !== '' ? $remark : PublishRemarkGuard::SUCCESS_REMARK;
                $task->update_time = time();
                $task->save();
                break;
            case PublishTaskReconcile::WINDOW_FAIL:
                if (!PublishRemarkGuard::isBillingLocked((string)$task->remark)) {
                    $task->status = DeviceEnum::TASK_STATUS_FAILED;
                    $task->remark = (string)$publish->remark;
                    $task->update_time = time();
                    $task->save();
                }
                break;
            case PublishTaskReconcile::WINDOW_WAIT:
                $task->update_time = time();
                $task->save();
                break;
            case PublishTaskReconcile::WINDOW_DISPATCH:
                self::rpaPublishTask($task, $output, function ($result) use ($task) {
                    $this->applyPublishDispatchResult($task, $result);
                });
                break;
            default:
                $this->setTaskLog("发布任务明细不存在，窗口内不重发: ID={$task->id}, sub_data_id={$task->sub_data_id}", 'error');
                break;
        }
    }

    /**
     * 到期收口：先认明细成功，再处理仍在发布中的行。
     */
    private function closePublishTask(SvDeviceTask $task): void
    {
        $publish = SvPublishSettingDetail::where('id', $task->sub_data_id)->findOrEmpty();
        $detailStatus = $publish->isEmpty() ? null : (int)$publish->status;
        $decision = PublishTaskReconcile::decideClose(
            (string)$task->remark,
            $detailStatus,
            $this->getLastPublishLogMessage($task)
        );

        $now = time();
        switch ($decision['action']) {
            case PublishTaskReconcile::CLOSE_BILLING:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->update_time = $now;
                $task->save();
                if (!empty($decision['fail_inflight_detail']) && !$publish->isEmpty()) {
                    $publish->status = 2;
                    $publish->remark = (string)$task->remark;
                    $publish->update_time = $now;
                    $publish->save();
                }
                break;
            case PublishTaskReconcile::CLOSE_FINISH:
                $task->status = DeviceEnum::TASK_STATUS_FINISHED;
                $remark = trim((string)$publish->remark);
                $task->remark = $remark !== '' ? $remark : PublishRemarkGuard::SUCCESS_REMARK;
                $task->update_time = $now;
                $task->save();
                break;
            case PublishTaskReconcile::CLOSE_FAIL_DETAIL:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = (string)$publish->remark;
                $task->update_time = $now;
                $task->save();
                break;
            case PublishTaskReconcile::CLOSE_SUCCESS_FROM_LOG:
                if (!$publish->isEmpty()) {
                    $publish->status = 1;
                    $publish->remark = PublishRemarkGuard::SUCCESS_REMARK;
                    $publish->update_time = $now;
                    $publish->exec_time = $now;
                    $publish->save();
                }
                $task->status = DeviceEnum::TASK_STATUS_FINISHED;
                $task->remark = PublishRemarkGuard::SUCCESS_REMARK;
                $task->update_time = $now;
                $task->save();
                break;
            case PublishTaskReconcile::CLOSE_FAIL_INFLIGHT:
                $remark = $this->getPublishRemark($task);
                if (!$publish->isEmpty()) {
                    $publish->status = 2;
                    $publish->remark = $remark;
                    $publish->update_time = $now;
                    $publish->save();
                }
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = $remark;
                $task->update_time = $now;
                $task->save();
                break;
            default:
                $task->status = DeviceEnum::TASK_STATUS_FAILED;
                $task->remark = $this->getTimeoutRemark($task);
                $task->update_time = $now;
                $task->save();
                break;
        }
    }

    private function finalizePublishAccount(SvDeviceTask $task): void
    {
        $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
            ->where('id', '<>', $task->id)
            ->where('task_type', DeviceEnum::TASK_TYPE_PUBLISH)
            ->where('status', DeviceEnum::TASK_STATUS_WAIT)
            ->where('source', DeviceEnum::TASK_SOURCE_PUBLISH)
            ->findOrEmpty();
        if (!$find->isEmpty()) {
            return;
        }

        \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->update(['status' => 2, 'update_time' => time()]);
        $account = \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->findOrEmpty();
        if ($account->isEmpty()) {
            return;
        }
        $nextAccount = \app\common\model\sv\SvPublishSettingAccount::where('publish_id', $account->publish_id)->where('status', 1)->findOrEmpty();
        if ($nextAccount->isEmpty()) {
            \app\common\model\sv\SvPublishSetting::where('id', $account->publish_id)->update(['status' => 3, 'update_time' => time()]);
        }
    }

    private function sendPublishNotice(SvDeviceTask $task): void
    {
        ApiLogic::sendNotice([
            'userId' => $task->user_id,
            'startTime' => $task->start_time_str,
            'endTime' => $task->end_time_str,
            'content' => \app\common\model\sv\SvPublishSettingAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
            'status' => $task->status,
            'autoType' => $task->auto_type,
        ]);
    }

    private function getLastPublishLogMessage(SvDeviceTask $task): string
    {
        $lastMsg = \app\common\model\sv\SvDeviceTaskLog::where('task_id', $task->id)
            ->where('task_source', $task->source)
            ->where('device_code', $task->device_code)
            ->order('id', 'desc')
            ->limit(1)
            ->findOrEmpty();
        if ($lastMsg->isEmpty()) {
            return '';
        }
        return trim((string)$lastMsg->message);
    }


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
            ]);
        });
        $this->setTaskLog("接管任务执行中: ID={$task->id}, 设备={$task->device_code}");
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
                ->where('task_type', DeviceEnum::TASK_TYPE_TAKEOVER)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->where('source', DeviceEnum::TASK_SOURCE_TAKEOVER)
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

            $this->setTaskLog("接管任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDeviceTakeOverTaskAccount::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }


    /**
     * 执行养号任务
     */
    protected function executeMaintainAccountTask(SvDeviceTask $task, Output $output)
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
            ]);
        });

        $this->setTaskLog("养号任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }


    /**
     * 执行养号任务完成逻辑
     */
    protected function executeMaintainAccountCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行养号任务完成 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的养号完成逻辑
            //self::rpaMaintainAccountEndTask($task, $output, function ($result) use ($task) {});

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
            ]);
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }



    /**
     * 执行获客任务
     */
    protected function executeCustomerAcquisitionTask(SvDeviceTask $task, Output $output)
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
            ]);
        });

        $this->setTaskLog("获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 执行获客任务完成逻辑
     */
    protected function executeCustomerAcquisitionCompletedTask(SvDeviceTask $task, Output $output)
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
                ]);
            });
            $this->setTaskLog("获客任务完成: ID={$task->id}, 设备={$task->device_code}");
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }

    protected function executeDySearchTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行抖音获客任务 - 设备: {$task->device_code}");
        }
        self::dySearchStartTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDySearchTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        });
        $this->setTaskLog("抖音获客任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeDySearchCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行抖音获客任务完成 - 设备: {$task->device_code}");
            }
            self::dySearchEndTask($task, $output, function ($result) use ($task) {
                $task->status = $result['status'];
                $task->remark = $result['remark'];
                $task->update_time = time();
                $task->save();
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
                ApiLogic::sendNotice([
                    'userId' => $task->user_id,
                    'startTime' => $task->start_time_str,
                    'endTime' => $task->end_time_str,
                    'content' => \app\common\model\sv\SvDySearchTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                    'status' => $task->status,
                    'autoType' => $task->auto_type,
                ]);
            });
            $this->setTaskLog("抖音获客任务完成: ID={$task->id}, 设备={$task->device_code}");
        } else {
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
        }
    }

    protected function executeDySearchWechatTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行抖音获客加微任务 - 设备: {$task->device_code}");
        }
        self::dySearchWechatTask($task, $output, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDySearchTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        });
        $this->setTaskLog("抖音获客加微任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    protected function executeDySearchWechatCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行抖音获客加微任务完成 - 设备: {$task->device_code}");
            }
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '加微任务完成';
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("抖音获客加微任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvDySearchTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
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
        self::cluesAddWechatFriendTask($task, $output, true, function ($result) use ($task) {
            $task->status = $result['status'];
            $task->remark = $result['remark'];
            $task->update_time = time();
            $task->save();
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCrawlingManualTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
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

            $find = SvDeviceTask::where('sub_task_id', $task->sub_task_id)
                ->where('id', '<>', $task->id)
                ->where('task_type', DeviceEnum::TASK_TYPE_FRIENDS)
                ->where('status', DeviceEnum::TASK_STATUS_WAIT)
                ->where('source', DeviceEnum::TASK_SOURCE_FRIENDS)
                ->findOrEmpty();
            if ($find->isEmpty()) {
                \app\common\model\sv\SvCrawlingManualTask::where('id', $task->sub_task_id)->update(['status' => 3, 'update_time' => time()]);
            }
            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);

            $this->setTaskLog("加微任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\sv\SvCrawlingManualTask::where('id', $task->sub_task_id)->findOrEmpty()->name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        }
        // else {
        //     if ($this->isDev) {
        //         $output->writeln("执行加微任务 - 设备: {$task->device_code}");
        //     }
        //     // TODO: 实现具体的加好友完成逻辑
        //     self::cluesAddWechatFriendTask($task, $output, false, function ($result) use ($task) {
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
     * 执行微圈任务
     */
    protected function executeWechatCircleTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行发布任务 - 设备: {$task->device_code}");
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
                ]);
            }
        });

        $this->setTaskLog("发布任务执行中: ID={$task->id}, 设备={$task->device_code}");
    }

    /**
     * 执行微圈任务完成逻辑
     */
    protected function executeWechatCircleCompletedTask(SvDeviceTask $task, Output $output)
    {
        if ($task->end_time < time()) {
            if ($this->isDev) {
                $output->writeln("执行发布任务完成 - 设备: {$task->device_code}");
            }
            self::wechatCirclePublishCompletedTask($task, $output);
            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '发布任务完成';
            $task->update_time = time();
            $task->save();

            $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_ONLINE);
            $this->setTaskLog("执行发布任务完成: ID={$task->id}, 设备={$task->device_code}");
            ApiLogic::sendNotice([
                'userId' => $task->user_id,
                'startTime' => $task->start_time_str,
                'endTime' => $task->end_time_str,
                'content' => \app\common\model\wechat\AiWechatCircleTaskConfig::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                'status' => $task->status,
                'autoType' => $task->auto_type,
            ]);
        }
    }


    protected function executeWechatCircleThumbCommentTask(SvDeviceTask $task, Output $output)
    {
        if ($this->isDev) {
            $output->writeln("执行点赞评论任务 - 设备: {$task->device_code}");
        }

        self::wechatCircleThumbCommentTask($task, $output, function ($result) use ($task) {
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
                    'content' => \app\common\model\sv\SvDeviceCircleLikeReply::where('id', $task->sub_task_id)->findOrEmpty()->task_name ?? $task->task_name,
                    'status' => $task->status,
                    'autoType' => $task->auto_type,
                ]);
            }
        });

        $this->setTaskLog("点赞评论任务执行中: ID={$task->id}, 设备={$task->device_code}");
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
            ]);
        }
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
            ]);
        });
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
            ]);
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
            ]);
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

        $this->setTaskLog("评论区评论任务执行完成: ID={$task->id}, 设备={$task->device_code}");
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
            //     ->where('task_type', DeviceEnum::TASK_TYPE_TOUCH)
            //     ->where('task_scene', DeviceEnum::TASK_SOURCE_TOUCH)
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
            ]);
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
            ]);
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
            ]);
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

    private function acquireRunningLock(string $lockValue): bool
    {
        try {
            $redis = Cache::store('redis')->handler();
            $ok = $redis->set(self::RUNNING_LOCK_KEY, $lockValue, ['nx', 'ex' => self::RUNNING_LOCK_TTL]);
            if (!$ok) {
                $this->setTaskLog('设备任务调度器正在执行, 跳过本轮并发触发', 'info');
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->setTaskLog('设备任务调度器获取运行锁失败, 跳过执行: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    private function releaseRunningLock(string $lockValue): void
    {
        try {
            $redis = Cache::store('redis')->handler();
            $script = <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
            $redis->eval($script, [self::RUNNING_LOCK_KEY, $lockValue], 1);
        } catch (\Throwable $e) {
            $this->setTaskLog('设备任务调度器释放运行锁失败: ' . $e->getMessage(), 'error');
        }
    }

    private function setTaskLog(string|array $content, string $level = 'info')
    {
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        Log::channel('device')->{$level}($content);
    }
}
