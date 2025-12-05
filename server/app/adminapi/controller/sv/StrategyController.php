<?php


namespace app\adminapi\controller\sv;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\sv\StrategyLogic;
use think\exception\HttpResponseException;

/**
 * StrategyController
 * @desc 回复策略
 * @author Qasim
 */
class StrategyController extends BaseAdminController
{

    public array $notNeedLogin = [];


    /**
     * @desc 回复策略
     */
    public function reply()
    {
        try {
            $params = $this->request->post();
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
        $params = $this->request->get();
        $result = StrategyLogic::replyInfo($params);
        if ($result) {
            return $this->data(data: StrategyLogic::getReturnData());
        }
        return $this->fail(StrategyLogic::getError());
    }

}
