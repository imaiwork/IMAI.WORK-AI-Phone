<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\StrategyValidate;
use app\api\logic\sv\StrategyLogic;

/**
 * StrategyController
 * @desc 回复策略
 * @author Qasim
 */
class StrategyController extends BaseApiController
{

    public array $notNeedLogin = [];


    /**
     * @desc 回复策略
     */
    public function reply()
    {
        try {
            $params = (new StrategyValidate())->post()->goCheck('reply');
            $result = StrategyLogic::replyStrategy($params);
            if ($result) {
                return $this->success(data: StrategyLogic::getReturnData());
            }
            return $this->fail(StrategyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 回复策略信息
     */
    public function replyInfo()
    {
        $params = (new StrategyValidate())->get()->goCheck('detail');
        $result = StrategyLogic::replyInfo($params);
        if ($result) {
            return $this->success(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

}
