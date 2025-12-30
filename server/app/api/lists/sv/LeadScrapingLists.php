<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvLeadScrapingRecord;
use app\common\model\sv\SvLeadScrapingSettingAccount;

/**
 * 截流设置列表
 * Class LeadScrapingLists
 * @package app\api\lists\sv
 */
class LeadScrapingLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['ps.status', 'ps.task_type'],
            '%like%' => ['ps.name', 'a.account']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.media_type', '=', $this->request->get('media_type', 1)];
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 1)];
        return SvLeadScrapingSettingAccount::alias('ps')
            ->field('ps.*, a.nickname, a.avatar')
            ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('ps.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if(((int)$item['send_count'] === (int)$item['count']) && (int)$item['status'] === 1){
                    $item['status'] = 2;
                    $item->save();
                }
                
                // 请求在线状态
                $detial = SvLeadScrapingRecord::where('scraping_account_id',$item->id)->where('status', 0)->order('id', 'asc')->limit(1)->find();
                $item['next_LeadScraping_time'] = !empty($detial) ? $detial['LeadScraping_time'] : '';
                $item['exec_time'] = !empty($detial) ? date('Y-m-d H:i:s', $item['exec_time']) : '';

                $startDate =strtotime($item['LeadScraping_start']);
                $endDate = strtotime($item['LeadScraping_end']);
                $item['LeadScraping_cycle'] = (int)(($endDate - $startDate) / 86400);

                

            })
            ->toArray();
    }

    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.media_type', '=', $this->request->get('media_type', 1)];
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 1)];
        return SvLeadScrapingSettingAccount::alias('ps')->field('id')
                ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
