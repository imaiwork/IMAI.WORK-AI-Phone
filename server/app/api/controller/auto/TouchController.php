<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\TouchValidate;
use app\api\logic\auto\TouchLogic;
use app\api\lists\auto\TouchLists;

/**
 * TouchController
 * @desc 设备自动任务
 * @author Qasim
 */
class TouchController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    public function update()
    {
        try {
            $params = (new TouchValidate())->post()->goCheck('update');
            $result = TouchLogic::update($params);
            if ($result) {
                return $this->success(data: TouchLogic::getReturnData());
            }
            return $this->fail(TouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new TouchValidate())->get()->goCheck('detail');
            $result = TouchLogic::detail($params);
            if ($result) {
                return $this->success(data: TouchLogic::getReturnData());
            }
            return $this->fail(TouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            TouchLogic::autoTouchTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
