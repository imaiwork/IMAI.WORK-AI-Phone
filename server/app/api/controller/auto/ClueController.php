<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\auto\ClueValidate;
use app\api\logic\auto\ClueLogic;
use app\api\lists\auto\ClueLists;

/**
 * ClueController
 * @desc 设备自动任务
 * @author Qasim
 */
class ClueController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];
    public function update()
    {
        try {
            $params = (new ClueValidate())->post()->goCheck('update');
            $result = ClueLogic::update($params);
            if ($result) {
                return $this->success(data: ClueLogic::getReturnData());
            }
            return $this->fail(ClueLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new ClueValidate())->get()->goCheck('detail');
            $result = ClueLogic::detail($params);
            if ($result) {
                return $this->success(data: ClueLogic::getReturnData());
            }
            return $this->fail(ClueLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            ClueLogic::autoClueTaskCron();
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
