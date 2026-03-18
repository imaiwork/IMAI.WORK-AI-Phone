<?php
namespace app\api\controller\distributionAgent;

use app\api\controller\BaseApiController;
use app\api\logic\distributionAgent\DistributionAgentCardLogic;
use app\api\validate\distributionAgent\DistributionAgentCardValidate;
use app\api\lists\distributionAgent\DistributionAgentCardPackagesLists;
use app\api\lists\distributionAgent\DistributionAgentCardLists;

class DistributionAgentCardController extends BaseApiController
{
    /**
     * @notes 获取分销制卡套餐
     * @return \think\response\Json
     */
    public function packages()
    {
        return $this->dataLists(new DistributionAgentCardPackagesLists());
    }

    /**
     * @notes 代理生成卡密
     * @return \think\response\Json
     */
    public function generate()
    {
        $params = (new DistributionAgentCardValidate())->post()->goCheck('generate');
        $result = DistributionAgentCardLogic::generate($this->userId, $params);
        if ($result === true) {
            return $this->success('卡密生成成功');
        }
        return $this->fail(DistributionAgentCardLogic::getError() ?: '生成失败');
    }

    /**
     * @notes 代理卡密列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new DistributionAgentCardLists());
    }

    /**
     * @notes 删除未使用的卡密
     * @return \think\response\Json
     */
    public function delete()
    {
        $params = (new DistributionAgentCardValidate())->post()->goCheck('delete');
        $result = DistributionAgentCardLogic::delete($this->userId, $params);
        if ($result === true) {
            return $this->success('删除成功');
        }
        return $this->fail(DistributionAgentCardLogic::getError() ?: '删除失败');
    }
}
