<?php


namespace app\api\controller\oem;

use app\api\controller\BaseApiController;
use app\api\logic\oem\OemLogic;
use think\exception\HttpResponseException;

/**
 * OemController
 * @author Qasim
 */
class OemController extends BaseApiController
{

    public array $notNeedLogin = ['check'];
    /**
     * @desc 平台
     */
    public function check()
    {
        try {
            $result = OemLogic::check();
            if ($result) {
                return $this->success(data: OemLogic::getReturnData());
            }
            return $this->fail(OemLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }






}
