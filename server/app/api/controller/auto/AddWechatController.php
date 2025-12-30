<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\AddWechatValidate;
use app\api\logic\auto\AddWechatLogic;
use app\api\lists\auto\AddWechatLists;

/**
 * AddWechatController
 * @desc 自动任务
 * @author Qasim
 */
class AddWechatController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];

    public function update()
    {
        try {
            $params = (new AddWechatValidate())->post()->goCheck('update');
            $result = AddWechatLogic::update($params);
            if ($result) {
                return $this->success(data: AddWechatLogic::getReturnData());
            }
            return $this->fail(AddWechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new AddWechatValidate())->get()->goCheck('detail');
            $result = AddWechatLogic::detail($params);
            if ($result) {
                return $this->success(data: AddWechatLogic::getReturnData());
            }
            return $this->fail(AddWechatLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            AddWechatLogic::autoAddWechatTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
