<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\TakeOverValidate;
use app\api\logic\auto\TakeOverLogic;
use app\api\lists\auto\TakeOverLists;

/**
 * TakeOverController
 * @desc 设备自动任务
 * @author Qasim
 */
class TakeOverController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    public function update()
    {
        try {
            $params = (new TakeOverValidate())->post()->goCheck('update');
            $result = TakeOverLogic::update($params);
            if ($result) {
                return $this->success(data: TakeOverLogic::getReturnData());
            }
            return $this->fail(TakeOverLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new TakeOverValidate())->get()->goCheck('detail');
            $result = TakeOverLogic::detail($params);
            if ($result) {
                return $this->success(data: TakeOverLogic::getReturnData());
            }
            return $this->fail(TakeOverLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            TakeOverLogic::autoTakeOverTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
