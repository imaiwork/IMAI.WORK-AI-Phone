<?php
namespace app\api\controller\distributionAgent;

use app\api\controller\BaseApiController;
use app\api\lists\distributionAgent\DistributionAgentSubLists;
use app\api\logic\distributionAgent\DistributionAgentLogic;
use app\api\validate\distributionAgent\DistributionAgentValidate;

/**
 * 分销代理前端控制器
 * Class DistributionAgentController
 * @package app\api\controller
 */
class DistributionAgentController extends BaseApiController
{
    /**
     * @notes 获取分销信息
     * @return \think\response\Json
     */
    public function info()
    {
        $result = DistributionAgentLogic::info($this->userId);
        return $this->success('', $result);
    }

    /**
     * @notes 下级列表
     * @return \think\response\Json
     */
    public function subLists()
    {
        return $this->dataLists(new DistributionAgentSubLists());
    }

    /**
     * @notes 设置下级等级
     * @return \think\response\Json
     */
    public function setLevel()
    {
        $params = (new DistributionAgentValidate())->post()->goApiCheck('setLevel');
        DistributionAgentLogic::setLevel($this->userId, $params);
        return $this->success('操作成功');
    }

    /**
     * @notes 移除代理
     * @return \think\response\Json
     */
    public function removeSub()
    {
        $params = (new DistributionAgentValidate())->post()->goApiCheck('removeSub');
        $res = DistributionAgentLogic::removeSub($this->userId, $params);
        if ($res === true) {
            return $this->success('移除成功');
        }
        return $this->fail(DistributionAgentLogic::getError() ?: '移除失败');
    }

    /**
     * @notes 赠送算力
     * @return \think\response\Json
     */
    public function giftTokens()
    {
        $params = (new DistributionAgentValidate())->post()->goApiCheck('transfer');
        $res = DistributionAgentLogic::giftTokens($params);
        if ($res === true) {
            return $this->success('赠送成功');
        }
        return $this->fail(DistributionAgentLogic::getError() ?: '赠送失败');
    }

    /**
     * @notes 设置邀请二维码
     * @return \think\response\Json
     */
    public function setQrCode()
    {
        $params = (new DistributionAgentValidate())->post()->goApiCheck('setQrCode');
        DistributionAgentLogic::setQrCode($this->userId, $params['qr_code']);
        return $this->success('操作成功');
    }

    /**
     * @notes 获取上级二维码
     * @return \think\response\Json
     */
    public function getSuperiorQrCode()
    {
        $result = DistributionAgentLogic::getSuperiorQrCode($this->userId);
        return $this->success('', $result);
    }

    /**
     * @notes 获取绑定小程序码
     * @return \think\response\Json
     */
    public function getBindMnpCode()
    {
        $params = $this->request->get();
        $result = DistributionAgentLogic::getBindMnpCode($params,$this->userId);
        if ($result) {
            return $this->data(DistributionAgentLogic::getReturnData());
        }
        return $this->fail(DistributionAgentLogic::getError());
    }

    /**
     * @notes 获取绑定小程序链接
     * @return \think\response\Json
     */
    public function getBindMnpUrl()
    {
        $params = $this->request->get();
        $result = DistributionAgentLogic::getBindMnpUrl($params,$this->userId);
        if ($result) {
            return $this->data(DistributionAgentLogic::getReturnData());
        }
        return $this->fail(DistributionAgentLogic::getError());
    }

    /**
     * @notes 获取绑定小程序链接
     * @return \think\response\Json
     */
    public function getAgentConfig()
    {
        $result = DistributionAgentLogic::getAgentConfig();
        return $this->success('success',$result);
    }
}
