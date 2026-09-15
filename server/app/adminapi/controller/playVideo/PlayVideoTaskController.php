<?php

declare(strict_types=1);

namespace app\adminapi\controller\playVideo;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\playVideo\PlayVideoTaskLists;
use app\common\enum\playVideo\PlayVideoEnum;
use app\common\model\playVideo\PlayVideoTask;
use app\common\service\playVideo\PlayVideoBillingService;
use think\response\Json;

/**
 * 爆款玩法任务（后台）。菜单/权限 SQL 随管理页一起做（P1），
 * 本期先提供接口：列表 + 手动补偿退款（验收 S7：重复点无副作用）。
 */
class PlayVideoTaskController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new PlayVideoTaskLists());
    }

    /**
     * 手动补偿退款：仅失败/超时且仍处预扣态的任务可退；幂等（bill_status CAS + 流水查重双闸）。
     */
    public function refund(): Json
    {
        $sn = trim((string)$this->request->post('sn', ''));
        if ($sn === '') {
            return $this->fail('sn 不能为空');
        }
        $task = PlayVideoTask::where('sn', $sn)->findOrEmpty();
        if ($task->isEmpty()) {
            return $this->fail('任务不存在');
        }
        if (!in_array((int)$task->status, [PlayVideoEnum::STATUS_FAILED, PlayVideoEnum::STATUS_TIMEOUT], true)) {
            return $this->fail('仅失败/超时任务可补偿退款');
        }
        try {
            PlayVideoBillingService::refundIfHeld($task, '后台补偿退款');
        } catch (\Throwable $e) {
            return $this->fail('退款失败：' . $e->getMessage());
        }
        $task->refresh();
        return $this->success('已处理', ['bill_status' => (int)$task->bill_status], 1, 1);
    }
}
