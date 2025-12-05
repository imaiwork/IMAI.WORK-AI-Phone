<?php


namespace app\api\lists\device;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvPublishSettingDetail;
use app\common\model\sv\SvPublishSettingAccount;
use app\common\model\sv\SvPublishSetting;
use app\common\model\sv\SvAccount;
use app\common\model\wechat\AiWechat;
/**
 * 发布设置列表
 * Class PublishLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class PublishAccountLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['ps.status',  'ps.media_type', 'ps.device_code', 'ps.account_type','ps.publish_id','ps.account'],
            '%like%' => ['ps.material_title', 'ps.name'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.task_type', '=', $this->request->get('task_type', 2)];
        return SvPublishSettingAccount::alias('ps')
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
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.task_type', '=',  $this->request->get('task_type', 2)];
        return SvPublishSettingAccount::alias('ps')->field('id')
                ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->where($this->searchWhere)
            ->count();
    }
}
