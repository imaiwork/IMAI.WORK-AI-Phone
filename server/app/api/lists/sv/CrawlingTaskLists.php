<?php

namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingTaskDeviceBind;

/**
 * 爬取任务列表
 * Class CrawlingTaskLists
 */
class CrawlingTaskLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '='      => ['user_id', 'type'],
            'in'     => ['status'],
            '%like%' => ['name', 'device_code', 'keywords'],
        ];
    }

    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $list = SvCrawlingTask::where($this->searchWhere)
            ->order(['id' => 'desc'])
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['device_codes'] = json_decode($item['device_codes'], true);
                $item['keywords'] = json_decode($item['keywords'], true);
                $reg_content = SvCrawlingRecord::where('task_id', $item['id'])->where('reg_content', '<>', '')->group('reg_content')->column('reg_content');
                $item['crawl_number'] = $reg_content ? count(explode(",", implode(",", $reg_content))) : 0;


                if($item['status'] == 3 && $item['number_of_implemented_keywords'] < $item['implementation_keywords_number']){
                    $item['status'] = 4;
                    $item['update_time'] = time();
                    $item->save();
                    SvCrawlingTaskDeviceBind::where('task_id', $item['id'])->update(['status' => 4]);
                }
            })
            ->toArray();
        return $list;
    }

    public function count(): int
    {
        return SvCrawlingTask::where($this->searchWhere)->count();
    }
}
