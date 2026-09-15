<?php


namespace app\api\lists\shanjian;

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
class PublishDetailLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['ps.status', 'ps.material_type'],
            '%like%' => ['ps.material_title'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['ps.user_id', '=', $this->userId];
        $this->searchWhere[] = ['ps.publish_id', '=',  $this->request->get('id', '')];
        $this->searchWhere[] = ['ps.task_type', '=',  $this->request->get('task_type', 2)];
        return SvPublishSettingDetail::alias('ps')
            ->field('ps.*')
            ->where($this->searchWhere)
            ->when($this->request->get('publish_start_time') && $this->request->get('publish_end_time'), function ($query) {
                $query->whereBetween('ps.publish_time', [strtotime($this->request->get('publish_start_time')), strtotime($this->request->get('publish_end_time'))]);
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.exec_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->order('ps.publish_time', 'asc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                //发布中时,超时未收到状态消息,更新记录状态
                if((int)$item['status'] === 3 && (time() -  $item['exec_time']) > 600 ){
                    $item['status'] = 2;
                    $item['remark'] = '发布超时';
                    $item->save();
                }
                if((int)$item['status'] === 1){
                    $item['remark'] = '';
                }
                // 请求在线状态
                $item['exec_time'] = date('Y-m-d H:i:s', $item['exec_time']);
                if( $item['status'] == 0){
                    $item['exec_time'] = '';
                }

                $account = SvAccount::alias('a')
                    ->join('sv_device d', 'a.device_code = d.device_code and a.user_id = d.user_id')
                    ->where('a.user_id', $item['user_id'])
                    ->where('a.account', $item['account'])
                    ->where('a.device_code', $item['device_code'])
                    ->field('a.nickname, a.avatar, d.device_model, d.sdk_version')
                    ->findOrEmpty();
                if($account->isEmpty()){
                    $item['nickname'] = '';
                    $item['avatar'] = '';
                    $item['device_model'] = '';
                    $item['sdk_version'] = '';
                }else{
                    $item['nickname'] = $account['nickname'];
                    $item['avatar'] = $account['avatar'];
                    $item['device_model'] = $account['device_model'];
                    $item['sdk_version'] = $account['sdk_version'];
                }
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
        $this->searchWhere[] = ['ps.publish_id', '=',  $this->request->get('id', '')];
        $this->searchWhere[] = ['ps.task_type', '=',  $this->request->get('task_type', 2)];
        return SvPublishSettingDetail::alias('ps')->field('id')
                ->join('sv_account a', 'a.account = ps.account and a.device_code = ps.device_code and a.type = ps.account_type', 'left')
            ->where($this->searchWhere)
            ->when($this->request->get('publish_start_time') && $this->request->get('publish_end_time'), function ($query) {
                $query->whereBetween('ps.publish_time', [strtotime($this->request->get('publish_start_time')), strtotime($this->request->get('publish_end_time'))]);
            })
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('ps.exec_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->count();
    }
}
