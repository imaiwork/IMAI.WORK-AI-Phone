<?php


namespace app\api\lists\wechat;

use app\api\lists\BaseApiDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\model\wechat\AiWechatDevice;
use app\common\model\wechat\AiWechat;
use app\api\logic\wechat\MessageLogic;

/**
 * 设备列表
 * Class DeviceLists
 * @package app\api\lists\wechat
 * @author Qasim
 */
class DeviceLists extends BaseApiDataLists implements ListsSearchInterface
{
    public function setSearch(): array
    {
        return [
            '=' => ['device_status'],
        ];
    }

    /**
     * @notes 获取列表
     * @return array
     */
    public function lists(): array
    {
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatDevice::field('id,device_code,device_status,device_model,sdk_version,create_time')
            ->where($this->searchWhere)
            ->order('id', 'desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->each(function ($item) {
                $item['wechat_id'] = AiWechat::where('device_code', $item['device_code'])->value('wechat_id') ?? '';

                $item['device_status'] = 0;

                // 请求在线状态
                $result = MessageLogic::getOnlineStatus($item['wechat_id'], $item['device_code'], 1);
                if ($result) {
                    $item['device_status'] = MessageLogic::getReturnData();
                    $item->device_status = $item['device_status'];
                    $item->save();
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
        $this->searchWhere[] = ['user_id', '=', $this->userId];
        return AiWechatDevice::where($this->searchWhere)->count();
    }
}
