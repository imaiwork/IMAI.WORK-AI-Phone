<?php


namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;

use app\api\validate\aiPersona\InteractiveValidate;
use app\api\logic\aiPersona\InteractiveLogic;
use app\common\model\sv\SvDevice;

/**
 * InteractiveController
 * @desc 私域互动管家控制器
 * @author Qasim
 */
class InteractiveController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];
    public function update()
    {
        try {
            $params = (new InteractiveValidate())->post()->goCheck('update');
            $result = InteractiveLogic::update($params);
            if ($result) {
                return $this->success(data: InteractiveLogic::getReturnData());
            }
            return $this->fail(InteractiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new InteractiveValidate())->get()->goCheck('detail');
            $result = InteractiveLogic::detail($params);
            if ($result) {
                return $this->success(data: InteractiveLogic::getReturnData());
            }
            return $this->fail(InteractiveLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron()
    {
        try {
            $deviceId = $this->request->get('device_code');
            # 获客与截流任务
            $device = \app\common\model\sv\SvDevice::where('auto_type', 1)->where('device_code', $deviceId)->limit(1)->findOrEmpty();
            InteractiveLogic::autoInteractiveTaskCron($device, 1);
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
