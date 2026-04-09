<?php


namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\logic\aiPersona\ClueTouchLogic;
use app\api\validate\aiPersona\ClueTouchValidate;
use think\exception\HttpResponseException;

/**
 * ClueTouchController
 * @desc IP人设触达自动任务
 * @author Qasim
 */
class ClueTouchController extends BaseApiController
{

    public array $notNeedLogin = ['cron'];
    

    public function detail()
    {
        try {
            $params = (new ClueTouchValidate())->get()->goCheck('detail');
            $result = ClueTouchLogic::detail($params);
            if ($result) {
                return $this->success(data: ClueTouchLogic::getReturnData());
            }
            return $this->fail(ClueTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function update()
    {
        try {
            $params = (new ClueTouchValidate())->post()->goCheck('update');
            $result = ClueTouchLogic::update($params);
            if ($result) {
                return $this->success(data: ClueTouchLogic::getReturnData());
            }
            return $this->fail(ClueTouchLogic::getError());
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
            ClueTouchLogic::trafficTaskCron($device, 1);
        } catch (HttpResponseException $e) {
            print_r($e->__toString());
            die;
        }
    }
}
