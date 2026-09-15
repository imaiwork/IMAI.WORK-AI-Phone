<?php


namespace app\api\lists\auto;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\auto\AutoDeviceConfig;
use app\common\service\UserDisplaySanitizer;

/**
 * 设备自动任务列表
 * Class DeviceLists
 * @package app\api\lists\auto
 * @author Qasim
 */
class DeviceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['status', 'device_code'],
            '%like%' => ['device_name']
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['dt.user_id', '=', $this->userId];

        return AutoDeviceConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                if (isset($item['human_image']) && is_array($item['human_image'])) {
                    $item['human_image'] = UserDisplaySanitizer::normalizeHumanImageForUser($item['human_image']);
                }
                return $item;
            })
            ->toArray();

    }


    /**
     * @notes  获取数量
     * @return int
     */
    public function count(): int
    {
        return AutoDeviceConfig::alias('dt')
            ->field('dt.*')
            ->where($this->searchWhere)
            ->count();
    }
}
