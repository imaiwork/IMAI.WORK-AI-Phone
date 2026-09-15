<?php

declare(strict_types=1);

namespace app\common\command;

use app\common\service\draw\DrawGenerateService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

/**
 * 出图任务兜底：轮询未完成任务 + 回收提交中途被掐断的任务
 *
 * 背景：DrawGenerateService::pollPending() 此前全仓库没有任何调用方，出图链路实际上
 * 没有任何兜底；而「PENDING 且 mid_task_id 为空」的僵尸任务连 pollPending 都捞不到，
 * 算力会永远挂在 BILL_HELD。2026-09-09 上游停摆 14 分钟就留下 92 条、2700 算力。
 *
 * ⚠️ 本命令跟整张任务表跑在同一个 HTTP 请求里（宝塔每分钟 curl /crontab），共用
 * nginx 的 600s 预算。轮询是逐条打中台的同步请求，批量给大了会吃光预算、把排在
 * 后面的任务一起拖死，所以 POLL_LIMIT 取小值；回收是纯数据库操作，可以给大一些。
 */
class DrawRecoverCron extends Command
{
    /** 每轮轮询条数：逐条打中台，必须给小 */
    private const POLL_LIMIT = 20;

    /** 每轮回收条数：纯 DB + 退费，无外部请求 */
    private const RECOVER_LIMIT = 50;

    protected function configure()
    {
        $this->setName('draw:recover')
            ->setDescription('出图任务轮询与超时回收退费');
    }

    protected function execute(Input $input, Output $output)
    {
        $service = new DrawGenerateService();

        $polled = 0;
        try {
            $polled = $service->pollPending(self::POLL_LIMIT);
        } catch (\Throwable $e) {
            // 轮询失败不能影响回收：回收才是把算力退回去的那一步
            Log::error('draw poll pending failed: ' . $e->getMessage());
        }

        $recovered = 0;
        try {
            $recovered = $service->recoverStalePending(self::RECOVER_LIMIT);
        } catch (\Throwable $e) {
            Log::error('draw recover stale pending failed: ' . $e->getMessage());
        }

        // 补退搁浅的预扣：任务已判终态、退款那步却失败过，两个来源都靠这一层收口
        $restranded = 0;
        try {
            $restranded = $service->refundStrandedHolds(self::RECOVER_LIMIT);
        } catch (\Throwable $e) {
            Log::error('draw refund stranded holds failed: ' . $e->getMessage());
        }

        if ($recovered > 0 || $restranded > 0) {
            Log::warning('draw recovered tasks', ['recovered' => $recovered, 'restranded' => $restranded]);
        }

        $output->writeln('polled=' . $polled . ' recovered=' . $recovered . ' restranded=' . $restranded);
        return 0;
    }
}
