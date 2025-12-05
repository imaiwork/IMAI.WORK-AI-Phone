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
        return SvCrawlingManualTask::where($this->searchWhere)->select()->each(function ($item) {
            if($item->status == 1){
                $item->completed_add_count = SvCrawlingManualTaskRecord::where('task_id', $item->id)->where('status', 'in', [0, 1])->count();
                if($item->completed_add_count >= $item->exec_add_count){
                    $item->status = 3;
                    $item->update_time = time();
                }
                if(!isset($item['start_time']) || is_null($item->start_time)){
                    $item->start_time = strtotime($item->create_time);
                }
                $item->end_time = SvCrawlingManualTaskRecord::where('task_id', $item->id)->max('update_time');
                if(!isset($item->end_time) || is_null($item->end_time)){
                    $item->end_time = time();
                }
                $item->save();
            }elseif($item->status == 3){
                $item->end_time = time();
                $item->save();
            }elseif($item->status == 0){
                $count = SvCrawlingManualTaskRecord::where('task_id', $item->id)->count();
                if($count == 0){
                    $item->status = 3;
                    $item->update_time = time();
                    $item->start_time = strtotime($item->create_time);
                    $item->end_time = time();
                    $item->save();
                }
            }
            $item->exec_day = ceil(($item->end_time - $item->start_time) / 86400);
            $item->wechats = AiWechat::field('wechat_id,wechat_no,wechat_nickname,wechat_avatar,wechat_status')
                ->where('user_id', $this->userId)
                ->where('wechat_id', 'in', explode(',', $item->wechat_id))
                ->select()->toArray();
            return $item;
        })->toArray();
    }
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvCrawlingManualTask::where($this->searchWhere)->count();
    }
}