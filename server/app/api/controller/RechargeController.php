<?php

namespace app\api\controller;

use app\api\lists\recharge\RechargeLists;
use app\api\logic\RechargeLogic;
use app\api\validate\RechargeValidate;


/**
 * 充值控制器
 * Class RechargeController
 * @package app\shopapi\controller
 */
class RechargeController extends BaseApiController
{

    /**
     * @notes 获取充值列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/23 18:55
     */
    public function lists()
    {
        return $this->dataLists(new RechargeLists());
    }


    /**
     * @notes 充值
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/23 18:56
     */
    public function recharge()
    {
        $params = (new RechargeValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
        ]);
        $result = RechargeLogic::recharge($params);
        if (false === $result) {
            return $this->fail(RechargeLogic::getError());
        }
        return $this->data($result);
    }


    /**
     * @notes 充值配置
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/24 16:56
     */
    public function config()
    {
        return $this->data(RechargeLogic::config($this->userId));
    }
}
