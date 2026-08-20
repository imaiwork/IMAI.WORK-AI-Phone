<?php
namespace app\adminapi\lists\sv;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\deviceauth\DeviceCdkCode;
use app\common\model\sv\SvDevice;

/**
 * 设备列表
 * Class DeviceLists
 * @package app\adminapi\lists\sv
 * @author Qasim
 */
class DeviceLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 列表
     * @return array
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function lists(): array
    {
        return SvDevice::alias('d')
            ->join('user u', 'u.id = d.user_id')
            ->field('d.id,u.avatar,u.nickname,d.device_name,d.device_code,d.status,d.device_model,d.sdk_version,d.create_time,d.auth_status,d.auth_type,d.auth_start_time,d.auth_expire_time,d.auto_type,d.last_cdk_code_id')
            ->where($this->searchWhere)
            ->order('d.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $lastCdk = !empty($item['last_cdk_code_id']) ? DeviceCdkCode::findOrEmpty((int)$item['last_cdk_code_id']) : null;
                $item['device_name'] = !empty($item['device_name']) ? $item['device_name'] : ($item['device_model'] ?: '');
                $item['auth_type_name'] = ($lastCdk && !$lastCdk->isEmpty()) ? self::authTypeFormat($lastCdk->type) : '';
                $item['cdk_type_name'] = $item['auth_type_name'];
                $item['auth_start_time'] = !empty($item['auth_start_time']) && $item['auth_start_time'] > 0 ? date('Y-m-d H:i:s', $item['auth_start_time']) : '';
                $item['auth_expire_time'] = !empty($item['auth_expire_time']) && $item['auth_expire_time'] > 0 ? date('Y-m-d H:i:s', $item['auth_expire_time']) : '永久';
                $item['auth_code'] = ($lastCdk && !$lastCdk->isEmpty()) ? (string)$lastCdk->code : '';
                unset($item['last_cdk_code_id']);
                return $item;
            })
            ->toArray();
    }

    /**
     * @notes 统计
     * @return int
     * @throws @\think\db\exception\DbException
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function count(): int
    {
        return SvDevice::alias('d')
            ->join('user u', 'u.id = d.user_id')
            ->where($this->searchWhere)
            ->count();
    }

    /**
     * @notes 搜索条件
     * @return array
     * @author L
     * @date 2024-07-10 09:40:09
     */
    public function setSearch(): array
    {
        return [
            '=' => ['d.user_id'],
            '%like%' => ['u.nickname', 'd.device_code']
        ];
    }

    private static function authTypeFormat($type): string
    {
        $map = [
            '0' => '无',
            '1' => '永久卡',
            '2' => '周卡',
            '3' => '月卡',
            '4' => '季卡',
            '5' => '半年卡',
            '6' => '年卡',
        ];

        return $map[(string)$type] ?? '其它';
    }
}
