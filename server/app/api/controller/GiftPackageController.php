<?php
namespace app\api\controller;


use app\api\lists\GiftPackageLists;
use app\api\logic\GiftPackageLogic;
use app\api\validate\GiftPackageValidate;
use app\api\controller\BaseApiController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\api\controller
 */
class GiftPackageController extends BaseApiController
{
    /**
     * @notes 列表
     * @author L
     * @date 2024-08-15 11:45:40
     */
    public function lists()
    {
        if (empty($this->request->get('type'))) {
            return $this->fail('类型参数丢失');
        }
        return $this->dataLists(new GiftPackageLists());
    }


    /**
     * @notes 充值
     * @return \think\response\Json
     * @author 段誉
     * @date 2023/2/23 18:56
     */
    public function recharge()
    {
        $params = (new GiftPackageValidate())->post()->goCheck('recharge', [
            'user_id' => $this->userId,
            'terminal' => $this->userInfo['terminal'],
        ]);
        $result = GiftPackageLogic::recharge($params);
        if (false === $result) {
            return $this->fail(GiftPackageLogic::getError());
        }
        return $this->data(GiftPackageLogic::getReturnData());
    }
}
            