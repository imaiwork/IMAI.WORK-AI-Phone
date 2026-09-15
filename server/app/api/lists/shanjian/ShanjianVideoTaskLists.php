<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\service\TeamBillingService;

class ShanjianVideoTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['video_setting_id', 'shanjian_type','persona_id','is_final','auto_type'],
            'in' => ['status'],
        ];
    }

    /**
     * 默认隐藏中间过渡任务(is_final=0, 如开启智剪的 type=5),
     * 列表/发布优先取最终可用视频(is_final=1); 显式传入 is_final 时按需过滤
     */
    private function applyFinalFilter(): void
    {
        if ($this->request->get('is_final', '') === '') {
            $this->searchWhere[] = ['is_final', '=', 1];
        }
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->applyFinalFilter();
        $spendable = $this->getSpendableTokens();
        $list = ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })
            ->where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) use ($spendable) {
               $item->append(['queue_status_text', 'download_status_text']);
               $failed = (int)$item->status === ShanjianVideoTask::STATUS_FAILED;
               // 蝉镜 type5 桥接任务禁止重试（与 retryFailedGenerate 口径一致）
               $chanjingBridge = $failed && ShanjianVideoTaskLogic::isChanjingBridgeTask($item);
               $item->can_retry = $failed && !$chanjingBridge && $spendable > 0;
               if ($failed && $chanjingBridge) {
                   $item->retry_disabled_reason = '该视频由数字人引擎合成，暂不支持重试';
               } else {
                   $item->retry_disabled_reason = $failed && $spendable <= 0 ? '算力不足' : '';
               }
               if ($failed){
                   $item->video_token = 0;
               }

            })->toArray();
        return $list;
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->applyFinalFilter();
        return ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }

    private function getSpendableTokens(): float
    {
        try {
            return (float)TeamBillingService::spendableTokens((int)$this->userId);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
