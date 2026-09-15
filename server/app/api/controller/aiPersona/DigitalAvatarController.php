<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\DigitalAvatarLists;
use app\api\logic\aiPersona\DigitalAvatarLogic;
use think\response\Json;

class DigitalAvatarController extends BaseApiController
{
    // 无需登录的接口
    public array $notNeedLogin = [];

    /**
     * AI人设关联形象列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new DigitalAvatarLists());
    }

    /**
     * 新增AI人设关联形象
     * @return Json
     */
    public function add(): Json
    {
        $params = $this->request->post();
        $result = DigitalAvatarLogic::add($params, $this->userId);
        if ($result === false) {
            return $this->fail(DigitalAvatarLogic::getError());
        }
        return $this->success('创建成功', DigitalAvatarLogic::getReturnData());
    }

    /**
     * 删除AI人设关联形象
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $result = DigitalAvatarLogic::delete($params['ids'], $this->userId);
        if ($result === false) {
            return $this->fail(DigitalAvatarLogic::getError());
        }
        return $this->success('删除成功', DigitalAvatarLogic::getReturnData());
    }

    /**
     * 绑定AI人设内的音色
     * @return Json
     */
    public function bindPersonaVoice(): Json
    {
        $params = $this->request->post();
        $result = DigitalAvatarLogic::bindPersonaVoice($params, $this->userId);
        if ($result === false) {
            return $this->fail(DigitalAvatarLogic::getError());
        }
        return $this->success('绑定音色成功', DigitalAvatarLogic::getReturnData());
    }
}