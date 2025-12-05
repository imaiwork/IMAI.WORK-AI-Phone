<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDevice;
use app\common\model\sv\SvDeviceRpa;
use app\common\model\sv\SvAccount;

/**
 * 设备列表
 * Class DeviceLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class DeviceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvDevice::field('id,device_code,status,device_model,sdk_version,create_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                // 请求在线状态
                $account = SvAccount::where('device_code',$item->device_code)->field('id,type,account')->select()->toArray();
                $item['account'] =  $account;
                $this->addDeviceRpa($item);
            })
            ->toArray();
    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return SvDevice::where($this->searchWhere)->count();
    }

    private  function addDeviceRpa(SvDevice $device)
    {
        $maps = array(
            ['app_icon' => '', 'app_type' => 1, 'app_name' => '视频号', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 1],
            ['app_icon' => '', 'app_type' => 3, 'app_name' => '小红书', 'exec_duration' => 200, 'is_enable' => 1, 'weight' => 0],
            ['app_icon' => '', 'app_type' => 4, 'app_name' => '抖音', 'exec_duration' => 200, 'is_enable' => 0, 'weight' => 2],
            ['app_icon' => '', 'app_type' => 5, 'app_name' => '快手', 'exec_duration' => 200, 'is_enable' => 0, 'weight' => 3],
        );

        $appCount = SvDeviceRpa::where('device_code', $device->device_code)->count();
        if($appCount == 0){
            foreach ($maps as &$item) {
                $item['device_code'] = $device->device_code;
                $item['user_id'] = $this->userId;
                $item['create_time'] = time();
            }
            $model = new SvDeviceRpa();
            $model->insertAll($maps);
        }
        
        
    }
}
