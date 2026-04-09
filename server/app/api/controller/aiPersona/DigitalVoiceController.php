<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\DigitalVoiceLists;
use app\api\logic\aiPersona\DigitalVoiceLogic;
use think\response\Json;

class DigitalVoiceController extends BaseApiController
{
    // 无需登录的接口
    public array $notNeedLogin = [];

    /**
     * AI人设关联音色列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new DigitalVoiceLists());
    }

    /**
     * 新增AI人设关联音色
     * @return Json
     */
    public function add(): Json
    {
        $params = $this->request->post();
        $result = DigitalVoiceLogic::add($params, $this->userId);
        if ($result === false) {
            return $this->fail(DigitalVoiceLogic::getError());
        }
        return $this->success('创建成功', DigitalVoiceLogic::getReturnData());
    }

    /**
     * 删除AI人设关联音色
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $result = DigitalVoiceLogic::delete($params['ids'], $this->userId);
        if ($result === false) {
            return $this->fail(DigitalVoiceLogic::getError());
        }
        return $this->success('删除成功', DigitalVoiceLogic::getReturnData());
    }
}