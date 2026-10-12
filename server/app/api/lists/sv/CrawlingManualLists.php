<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCrawlingManualTask;
use app\common\model\sv\SvCrawlingManualTaskRecord;
use app\common\model\wechat\AiWechat;

/**
 * 爬取任务列表
 * Class CrawlingManualLists
 */
class CrawlingManualLists extends BaseApiDataLists implements ListsSearchInterface
{
    private const SOURCE_TEXT = [
        1 => '表格导入',
        2 => '获客任务引用',
        3 => '抖音获客任务引用',
        4 => '客资线索动态获取',
    ];

    public function setSearch(): array
    {
        return [
            '='      => ['user_id', 'type', 'status'],
            '%like%' => ['name'],
        ];
    }
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvCrawlingManualTask::where($this->searchWhere)
            ->limit($this->limitOffset, $this->limitLength)
            ->select()->each(function ($item) {
                if ($item->status == 1) {
                    if ((int)$item->add_friend_type === 2) {
                        if ((int)$item->add_number > 0 && (int)$item->completed_add_count >= (int)$item->add_number) {
                            $item->status = 3;
                            $item->update_time = time();
                            $item->save();
                        }
                    } else {
                        $item->completed_add_count = SvCrawlingManualTaskRecord::where('task_id', $item->id)->where('status', 1)->count();
                        if ($item->completed_add_count >= $item->exec_add_count) {
                            $item->status = 3;
                            $item->update_time = time();
                        }
                        $item->save();
                    }
                } elseif ($item->status == 3) {
                    // $item->end_time = time();
                    // $item->save();
                } elseif ($item->status == 0) {
                    // source=4 记录由 cron 从客资池领取，建任务时为 0 属正常，不能判完成
                    if ((int)$item->source !== 4) {
                        $count = SvCrawlingManualTaskRecord::where('task_id', $item->id)->count();
                        if ($count == 0) {
                            $item->status = 3;
                            $item->update_time = time();
                            // $item->start_time = strtotime($item->create_time);
                            // $item->end_time = time();
                            $item->save();
                        }
                    }
                }
                $item->exec_day = ceil(($item->end_time - $item->start_time) / 86400);
                $item->source_text = self::SOURCE_TEXT[(int)$item->source] ?? '';
                $item->wechats = AiWechat::field('wechat_id,wechat_no,wechat_nickname,wechat_avatar,wechat_status')
                    ->where('user_id', $this->userId)
                    ->where('wechat_id', 'in', explode(',', $item->wechat_id))
                    ->select()->toArray();
                $item->start_time = date('Y-m-d H:i:s', $item->start_time);
                $item->end_time = date('Y-m-d H:i:s', $item->end_time);
                return $item;
            })->toArray();
    }
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvCrawlingManualTask::where($this->searchWhere)->count();
    }
}
