<?php

namespace app\adminapi\lists\recharge;

use app\common\model\recharge\GiftPackage;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\service\FileService;
use app\common\model\recharge\GiftPackageOrder;
use app\common\model\pay\PayConfig;


/**
 * 列表
 * Class RechargeLists
 * @package app\Adminapi\lists\recharge
 */
class GiftPackageOrderLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function lists(): array
    {
        return GiftPackageOrder::alias('gpo')
            ->join('user u', 'u.id = gpo.user_id')
            ->where($this->searchWhere)
            //->where('gpo.pay_status', 1)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('gpo.pay_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('user_id') != null, function ($query) {
                $query->where('gpo.user_id', $this->request->get('user_id'));
            })
            ->field('gpo.id, gpo.sn, gpo.package_id,gpo.pay_way, gpo.order_amount, gpo.pay_status, gpo.pay_time, gpo.create_time,u.nickname,u.mobile,u.avatar')
            ->order('gpo.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if($item['pay_time'] == ''){

                    $item['pay_time'] = "未支付";
                }else{

                    $item['pay_time']       = date('Y-m-d H:i:s', $item['pay_time']);
                }
                $item['avatar']         = FileService::getFileUrl($item['avatar']);
                $item['pay_way']        = PayConfig::where('pay_way', $item['pay_way'])->value('name');

                $package = GiftPackage::where('id', $item['package_id'])->field('name, package_info')->json(['package_info'], true)->findOrEmpty();
                if ($package->isEmpty()) {

                    $item['package_name']   = '';
                    $item['package_tokens'] = 0;
                } else {

                    $item['package_name']   = $package['name'];
                    $item['package_tokens'] = $package['package_info']['tokens'];
                }
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function count(): int
    {
        return GiftPackageOrder::alias('gpo')
            ->join('user u', 'u.id = gpo.user_id')
            ->where($this->searchWhere)
            //->where('gpo.pay_status', 1)
            ->when($this->request->get('start_time') && $this->request->get('end_time'), function ($query) {
                $query->whereBetween('gpo.create_time', [strtotime($this->request->get('start_time')), strtotime($this->request->get('end_time'))]);
            })
            ->when($this->request->get('user'), function ($query) {
                $query->where('u.nickname', 'like', '%' . $this->request->get('user') . '%');
            })
            ->when($this->request->get('user_id') != null, function ($query) {
                $query->where('gpo.user_id', $this->request->get('user_id'));
            })
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-08-15 15:04:27
     */
    public function setSearch(): array
    {
        return [
            '=' => ['pay_status', 'type']
        ];
    }
}
