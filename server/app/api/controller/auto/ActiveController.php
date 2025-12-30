<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\ActiveValidate;
use app\api\logic\auto\ActiveLogic;
use app\api\lists\auto\ActiveLists;

/**
 * ActiveController
 * @desc 自动任务
 * @author Qasim
 */
class ActiveController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    public function update()
    {
        try {
            $params = (new ActiveValidate())->post()->goCheck('update');
            $result = ActiveLogic::update($params);
            if ($result) {
                return $this->success(data: ActiveLogic::getReturnData());
            }
            return $this->fail(ActiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new ActiveValidate())->get()->goCheck('detail');
            $result = ActiveLogic::detail($params);
            if ($result) {
                return $this->success(data: ActiveLogic::getReturnData());
            }
            return $this->fail(ActiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            ActiveLogic::autoActiveTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
