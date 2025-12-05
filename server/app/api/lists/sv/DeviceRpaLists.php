<?php


namespace app\api\lists\sv;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\sv\SvDeviceRpa;
use app\common\service\FileService;

/**
 * 设备rpa配置列表
 * Class DeviceRpaLists
 * @package app\api\lists\sv
 * @author Qasim
 */
class DeviceRpaLists extends BaseApiDataLists implements ListsSearchInterface

{
    public function setSearch(): array
    {
        return [
            '=' => [],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        $this->searchWhere[] = ['is_enable', '=', 1];
        $this->searchWhere[] = ['device_code', '=',  $this->request->get('device_code', '')];
        return SvDeviceRpa::field('*')
            ->where($this->searchWhere)
            ->order('weight', 'asc')
            //->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                //$item['app_name'] = $this->appName($item['app_type']);
                $item['app_icon'] = $item['app_icon'] ? FileService::getFileUrl($item['app_icon']) : '';
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
        $this->searchWhere[] = ['is_enable', '=', 1];
        $this->searchWhere[] = ['device_code', '=',  $this->request->get('device_code', '')];
        return SvDeviceRpa::where($this->searchWhere)->count();
    }

    private function appName($app_type)
    {
        $maps = [
            3 => '小红书',
            4 => '视频号',
            5 => '抖音',
            6 => '快手',
            7 => 'BOSS直聘'
        ];
        return $maps[$app_type] ?? '其他';
    }
}
