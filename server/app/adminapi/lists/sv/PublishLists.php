<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvPublishSettingAccount;

use app\common\model\sv\SvPublishSettingDetail;
/**
 * 发布列表
 * Class AccountLists
 * @package app\adminapi\lists\sv
 * @author Lee
 */
class PublishLists extends BaseAdminDataLists implements ListsSearchInterface
{

  public function setSearch(): array
    {
        return [
            '=' => ['ps.status', 'ps.media_type'],
            '%like%' => ['ps.name'],
            '%like%' => ['a.account'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        return SvPublishSettingAccount::alias('ps')
            ->field('ps.*, a.nickname, a.avatar')
            ->field('u.nickname as user_nickname')
            ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->join('user u', 'u.id = ps.user_id', 'left')
            ->where($this->searchWhere)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('ps.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if(((int)$item['published_count'] === (int)$item['count']) && (int)$item['status'] === 1){
                    $item['status'] = 2;
                    $item->save();
                }
                
                // 请求在线状态
                $detial = SvPublishSettingDetail::where('publish_account_id',$item->id)->where('status', 0)->order('id', 'asc')->limit(1)->find();
                $item['next_publish_time'] = !empty($detial) ? $detial['publish_time'] : '';
                $item['exec_time'] = !empty($detial) ? date('Y-m-d H:i:s', $item['exec_time']) : '';

                $startDate =strtotime($item['publish_start']);
                $endDate = strtotime($item['publish_end']);
                $item['publish_cycle'] = (int)(($endDate - $startDate) / 86400);

                

            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return SvPublishSettingAccount::alias('ps')->field('id')
            ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->join('user u', 'u.id = ps.user_id', 'left')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}