<?php

namespace app\api\lists\shanjian;

use app\api\lists\BaseApiDataLists;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\shanjian\ShanjianVideoTask;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\service\TeamBillingService;

class ShanjianVideoTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['id', 'video_setting_id', 'shanjian_type','persona_id','is_final','auto_type'],
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
        return $this->attachPublishCopy($list);
    }

    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->applyFinalFilter();
        return ShanjianVideoTask::when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
            $query->whereBetween('create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
        })->where($this->searchWhere)->count();
    }

    /**
     * 内容记录复制用：只认已经生成的发布文案，不用口播标题/正文顶上。
     * 成片 extra 优先；没有时再取这条视频最早一条非空的发布明细。
     */
    private function attachPublishCopy(array $list): array
    {
        if ($list === []) {
            return $list;
        }

        $missingIds = [];
        foreach ($list as &$item) {
            $extra = is_array($item['extra'] ?? null) ? $item['extra'] : [];
            $copy = ShanjianVideoTask::readPublishCopy($extra);
            $item['publish_title'] = $copy['title'];
            $item['publish_content'] = $copy['content'];
            $item['publish_topic'] = $this->formatPublishTopic($copy['topic']);
            if ($item['publish_title'] === '' || $item['publish_content'] === '') {
                $missingIds[] = (int)($item['id'] ?? 0);
            }
        }
        unset($item);

        $missingIds = array_values(array_filter($missingIds));
        if ($missingIds === []) {
            return $list;
        }

        $byTask = [];
        $details = SvPublishSettingDetail::where('user_id', $this->userId)
            ->whereIn('video_task_id', $missingIds)
            ->order('id', 'asc')
            ->field('video_task_id,material_title,material_subtitle,material_tag')
            ->select()
            ->toArray();
        foreach ($details as $detail) {
            $taskId = (int)($detail['video_task_id'] ?? 0);
            if ($taskId <= 0 || isset($byTask[$taskId])) {
                continue;
            }
            $content = trim((string)($detail['material_subtitle'] ?? ''));
            if ($content === '') {
                continue;
            }
            $byTask[$taskId] = [
                'title' => trim((string)($detail['material_title'] ?? '')),
                'content' => $content,
                'topic' => $this->formatPublishTopic((string)($detail['material_tag'] ?? '')),
            ];
        }

        foreach ($list as &$item) {
            $found = $byTask[(int)($item['id'] ?? 0)] ?? null;
            if ($found === null) {
                continue;
            }
            if ($item['publish_title'] === '' && $found['title'] !== '') {
                $item['publish_title'] = $found['title'];
            }
            if ($item['publish_content'] !== '') {
                continue;
            }
            $item['publish_content'] = $found['content'];
            if ($item['publish_topic'] === '') {
                $item['publish_topic'] = $found['topic'];
            }
        }
        unset($item);

        return $list;
    }

    private function formatPublishTopic(string $topic): string
    {
        $topic = trim($topic);
        if ($topic === '') {
            return '';
        }
        $parts = preg_split('/[,，\s]+/u', $topic) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $text = trim((string)$part);
            if ($text === '') {
                continue;
            }
            if (!str_starts_with($text, '#')) {
                $text = '#' . $text;
            }
            $tags[] = $text;
        }
        return implode(' ', $tags);
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
