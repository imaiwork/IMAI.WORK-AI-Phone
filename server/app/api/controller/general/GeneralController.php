<?php

namespace app\api\controller\general;

use app\api\controller\BaseApiController;
use app\api\logic\general\GeneralLogic;

/**
 * 通用AI服务控制器（免登录）
 * Class GeneralController
 *
 * 参考 CrawlingTaskController::ocr / localOcr：
 * 免登录接口，通过 device_code 定位 SvDevice.user_id 作为扣费主体
 */
class GeneralController extends BaseApiController
{
    public array $notNeedLogin = ['analyze', 'ocr'];

    /**
     * 通用AI分析（免登录，通过 device_code 扣费）
     * @return \think\response\Json
     */
    public function analyze()
    {
        $params = $this->request->post();
        $result = GeneralLogic::analyze($params);
        if ($result) {
            return $this->success(data: GeneralLogic::getReturnData());
        }
        return $this->fail(GeneralLogic::getError());
    }

    /**
     * 通用OCR识别（免登录，通过 device_code 扣费）
     * @return \think\response\Json
     */
    public function ocr()
    {
        $params = $this->request->post();
        $result = GeneralLogic::ocr($params);
        if ($result) {
            return $this->success(data: GeneralLogic::getReturnData());
        }
        return $this->fail(GeneralLogic::getError());
    }
}
