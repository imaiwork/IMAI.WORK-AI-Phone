<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\aiPersona\DigitalVoiceLists;
use app\adminapi\logic\aiPersona\DigitalVoiceLogic;
use think\response\Json;

class DigitalVoiceController extends BaseAdminController
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
        $result = DigitalVoiceLogic::add($params);
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
        $result = DigitalVoiceLogic::delete($params['ids']);
        if ($result === false) {
            return $this->fail(DigitalVoiceLogic::getError());
        }
        return $this->success('删除成功', DigitalVoiceLogic::getReturnData());
    }
}