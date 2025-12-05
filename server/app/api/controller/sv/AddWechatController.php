<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\sv\SvAddWechatRecordValidate;
use app\api\validate\sv\SvAddWechatStrategyValidate;
use app\api\logic\sv\SvAddWechatStrategyLogic;
use app\api\logic\sv\SvAddWechatRecordLogic;
use app\api\lists\sv\SvAddWechatRecordLists;

/**
 * StrategyController
 * @desc 回复策略
 * @author Qasim
 */
class AddWechatController extends BaseApiController
{

    public array $notNeedLogin = [];

        /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new SvAddWechatRecordLists());
    }

    public function retry(){
        try {
            $params = (new SvAddWechatRecordValidate())->post()->goCheck('retry');
            $result = SvAddWechatRecordLogic::retry($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvAddWechatRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete(){
        try {
            $params = (new SvAddWechatRecordValidate())->post()->goCheck('delete');
            $result = SvAddWechatRecordLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvAddWechatRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function strategyDetail(){
        try {
            $params = (new SvAddWechatStrategyValidate())->get()->goCheck('detail');
            $result = SvAddWechatStrategyLogic::strategyDetail($params);
            if ($result) {
                return $this->data(SvAddWechatStrategyLogic::getReturnData());
            }
            return $this->fail(SvAddWechatStrategyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function strategyUpdate(){
        try {
            $params = (new SvAddWechatStrategyValidate())->post()->goCheck('update');
            $result = SvAddWechatStrategyLogic::strategyUpdate($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvAddWechatStrategyLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}