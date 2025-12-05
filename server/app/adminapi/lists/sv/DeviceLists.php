<?php
namespace app\adminapi\lists\sv;
use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvAccount;
use app\api\logic\sv\MessageLogic;

/**
 * 设备列表
 * Class DeviceLists
 * @package app\api\lists\sv
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
            ->join('user u','u.id = d.user_id')
            ->field('d.id,u.avatar,u.nickname,d.id,d.device_code,d.status,d.device_model,d.sdk_version,d.create_time')
            ->where($this->searchWhere)
            ->order('d.id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
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
            ->join('user u','u.id = d.user_id')
            ->field('u.avatar,u.nickname,d.id,d.device_code,d.status,d.device_model,d.sdk_version,d.create_time')
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
            '=' => [],
            '%like%' => ['u.nickname','d.device_code']
        ];
    }
 
}