<?php

namespace app\adminapi\controller\distributionAgent;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\distributionAgent\DistributionAgentSubLists;
use app\adminapi\logic\distributionAgent\DistributionAgentUserLogic;
use app\adminapi\validate\distributionAgent\DistributionAgentUserValidate;

/**
 * 分销代理用户管理控制器
 * Class DistributionAgentUserController
 * @package app\adminapi\controller\distributionAgent
 */
class DistributionAgentUserController extends BaseAdminController
{
    /**
     * @notes 代理详情接口
     * @return \think\response\Json
     */
    public function detail()
    {
        $params = (new DistributionAgentUserValidate())->goCheck('detail');
        $detail = DistributionAgentUserLogic::detail($params['user_id']);
        return $this->success('', $detail);
    }

    /**
     * @notes 下级列表接口
     * @return \think\response\Json
     */
    public function subLists()
    {
        return $this->dataLists(new DistributionAgentSubLists());
    }
}
