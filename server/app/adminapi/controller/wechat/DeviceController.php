<?php
namespace app\adminapi\controller\wechat;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\wechat\WechatDeviceLists;

/**
 * 设备管理
 * Class DeviceController
 * @package app\adminapi\controller
 */
class DeviceController extends BaseAdminController
{
    /**
     * @notes 列表
     */
    public function lists()
    {
        return $this->dataLists(new WechatDeviceLists());
    }
}
                        