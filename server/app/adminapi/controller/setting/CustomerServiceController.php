<?php


namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\CustomerServiceLogic;

/**
 * 客服设置
 * Class CustomerServiceController
 * @package app\adminapi\controller\setting
 */
class CustomerServiceController extends BaseAdminController
{
    /**
     * @notes 获取客服设置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 12:05 下午
     */
    public function getConfig()
    {
        $result = CustomerServiceLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置客服设置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 12:11 下午
     */
    public function setConfig()
    {
        $params = $this->request->post();
        CustomerServiceLogic::setConfig($params);
        return $this->success('设置成功', [], 1, 1);
    }
}
