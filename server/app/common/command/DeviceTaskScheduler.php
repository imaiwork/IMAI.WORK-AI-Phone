<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceTask;
use app\common\enum\DeviceEnum;
use app\common\traits\DeviceTaskTrait;

class DeviceTaskScheduler extends Command
{
    use DeviceTaskTrait;

    private $isDev = false;
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

        print_r("\n设备任务调度器启动...'\n");
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
    protected function runAsDaemon(Output $output, $interval)
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
             // 3. 检查超时未开始的任务 (status=0 且当前时间超过end_time)
            $this->checkTimeoutTasks($currentTime, $output);
            
            // 1. 检查需要开始执行的任务 (status=0 且当前时间在区间内)
            $this->checkPendingTasks($currentTime, $output);

            // 2. 检查需要结束的任务 (status=1 且当前时间超过end_time)
            $this->checkRunningTasks($currentTime, $output);

           
        } catch (\Exception $e) {
            $this->setTaskLog("设备任务调度器执行异常: " . $e->getTraceAsString(), 'error');
            if ($this->isDev) {
                $output->writeln("<error>执行异常: " . $e->getMessage() . "</error>");
            }
        }
    }

    /**
     * 检查待执行任务
     */
    protected function checkPendingTasks($currentTime, Output $output)
    {
        $pendingTasks = SvDeviceTask::alias('t')
            ->field('t.*, FROM_UNIXTIME(t.start_time) as start_time_str, FROM_UNIXTIME(t.end_time) as end_time_str')
            ->where('t.status', '=', 0) // 只处理已标记为执行中的任务
            ->where('t.start_time', '<=', $currentTime)
            ->where('t.end_time', '>', $currentTime)
            ->order('t.start_time', 'asc')
            ->limit(10)
            ->select();
        $output->writeln(\think\facade\Db::getLastSql());
        foreach ($pendingTasks as $task) {
            try {
                // 更新任务状态为执行中
                //$task->status = 1;
                $task->update_time = $currentTime;
                $task->save();
                // 执行具体任务
                $this->executeDeviceTask($task, $output);

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
        $runningTasks = SvDeviceTask::field('*')
            ->where('status', 1)
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
    protected function checkTimeoutTasks($currentTime, Output $output)
    {
        $timeoutTasks = SvDeviceTask::field('*')
            ->where('status', 0)
            ->where('end_time', '<', $currentTime)
            ->select();

        foreach ($timeoutTasks as $task) {
            try {
                // 更新任务状态为执行失败（超时未执行）

                $task->status = 3;
                $task->remark = '任务超时未执行';
                $task->update_time = $currentTime;
                $task->save();
                if ($this->isDev) {
                    $output->writeln("[" . date('Y-m-d H:i:s') . "] 任务超时未执行 ID: {$task['id']} - {$task['task_name']}");
                }

                $this->setTaskLog("设备任务超时未执行: ID={$task['id']}, 任务名称={$task['task_name']}", 'warning');
            } catch (\Exception $e) {
                $this->setTaskLog("更新任务超时状态失败 ID: {$task['id']} - " . $e->getTraceAsString(), 'error');
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
        if ($task->account_type == DeviceEnum::ACCOUNT_TYPE_SPH) {
            self::sphPublishTask($task, $output, function ($result) use ($task) {
                if ($result['status'] !== -1) {
                    $task->status = $result['status'];
                    $task->remark = $result['remark'];
                    $task->sub_data_id = $result['publish_id'] ?? 0;
                    $task->update_time = time();
                    $task->save();
                    $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
                }
            });
        } else {
            self::rpaPublishTask($task, $output, function ($result) use ($task) {
                if ($result['status'] !== -1) {
                    $task->status = $result['status'];
                    $task->remark = $result['remark'];
                    $task->sub_data_id = $result['publish_id'] ?? 0;
                    $task->update_time = time();
                    $task->save();
                    $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
                }
            });
        }
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

            $task->status = DeviceEnum::TASK_STATUS_FINISHED;
            $task->remark = '发布任务完成';
            $task->update_time = time();
            $task->save();

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
            $this->setTaskLog("加微任务完成: ID={$task->id}, 设备={$task->device_code}");
        } else {

            if ($this->isDev) {
                $output->writeln("执行发布任务 - 设备: {$task->device_code}");
            }

            // TODO: 实现具体的发布逻辑
            // 例如：调用设备服务发布内容
            if ($task->account_type == DeviceEnum::ACCOUNT_TYPE_SPH) {
                self::sphPublishTask($task, $output, function ($result) use ($task) {
                    if ($result['status'] !== -1) {
                        $task->status = $result['status'];
                        $task->remark = $result['remark'];
                        $task->sub_data_id = $result['publish_id'] ?? 0;
                        $task->update_time = time();
                        $task->save();
                        $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
                    }
                });
            } else {
                self::rpaPublishTask($task, $output, function ($result) use ($task) {
                    if ($result['status'] !== -1) {
                        $task->status = $result['status'];
                        $task->remark = $result['remark'];
                        $task->sub_data_id = $result['publish_id'] ?? 0;
                        $task->update_time = time();
                        $task->save();
                        $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
                    }
                });
            }
            // 模拟任务执行
            $this->setTaskLog("发布任务执行中: ID={$task->id}, 设备={$task->device_code}");
        }
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
        if ($task->account_type == DeviceEnum::ACCOUNT_TYPE_XHS) {
            self::rpaTakeoverTask($task, $output, function ($result) use ($task) {
                $task->status = $result['status'];
                $task->remark = $result['remark'];
                $task->update_time = time();
                $task->save();
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            });
        }
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
            });
            $this->setTaskLog("获客任务完成: ID={$task->id}, 设备={$task->device_code}");
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
        } else {
            if ($this->isDev) {
                $output->writeln("执行加微任务 - 设备: {$task->device_code}");
            }
            // TODO: 实现具体的加好友完成逻辑
            self::cluesAddWechatFriendTask($task, $output, function ($result) use ($task) {
                $task->status = $result['status'];
                $task->remark = $result['remark'];
                $task->update_time = time();
                $task->save();
                $this->updateDeviceStatus($task->device_code, DeviceEnum::DEVICE_STATUS_WORKING);
            });
            $this->setTaskLog("加微任务执行中: ID={$task->id}, 设备={$task->device_code}");
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
        Log::channel('device')->{$level}($content);
    }
}
