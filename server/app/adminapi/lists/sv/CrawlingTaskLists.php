<?php


namespace app\adminapi\lists\sv;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvCrawlingTask;
use app\common\model\sv\SvCrawlingRecord;
use app\common\model\sv\SvDevice;


/**
 * 采集任务列表
 * Class PublishLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class CrawlingTaskLists extends BaseAdminDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => [],
            '%like%' => ['u.nickname'],
            
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        return SvCrawlingTask::alias('ct')
            ->field('ct.*, u.nickname, u.avatar')
            ->leftjoin('user u','u.id = ct.user_id')
            //->leftjoin('sv_crawling_task_device_bind b', 'b.user_id = ct.user_id and b.task_id = ct.task_id')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ct.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('name'), function ($query) {
                $query->where('ct.name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->order('ct.id','desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function($item){
                $item['tokens'] = SvCrawlingRecord::where('task_id', $item['id'])->fetchSql(false)->sum('tokens');
                $item['devices'] = SvDevice::field('device_model, device_code, status, sdk_version')->where('device_code', 'in',  json_decode($item['device_codes'], true))->select()->toArray();
                $item['total_progress'] = count(json_decode($item['keywords'], true)) * 11;
                $item['current_progress'] = SvCrawlingRecord::where('task_id', $item['id'])->count();

            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvCrawlingTask::alias('ct')
            ->field('ct.*, a.nickname, a.avatar')
            ->leftjoin('user u','u.id = ct.user_id')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ct.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('name'), function ($query) {
                $query->where('ct.name', 'like', '%' . $this->request->get('name') . '%');
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->where($this->searchWhere)
            ->count();
    }
}
