<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\aiPersona\AiPersonaLists;
use app\adminapi\logic\aiPersona\AiPersonaLogic;
use Exception;
use think\response\Json;

class AiPersonaController extends BaseAdminController
{
    // 无需登录的接口
    public array $notNeedLogin = [];

    /**
     * AI人设列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new AiPersonaLists());
    }

    /**
     * 编辑AI人设
     * @return Json
     */
    public function edit(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::edit($params);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('编辑成功', AiPersonaLogic::getReturnData());
    }

    /**
     * 编辑AI人设发布模式
     * @return Json
     */
    public function update(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::update($params);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('编辑成功', AiPersonaLogic::getReturnData());
    }

    /**
     * 删除AI人设
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $ids = is_array($params['id']) ? $params['id'] : [$params['id']];
        $result = AiPersonaLogic::delete($ids);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('删除成功', AiPersonaLogic::getReturnData());
    }

    /**
     * 获取AI人设详情
     * @return Json
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        try {
            $result = AiPersonaLogic::detail(intval($params['id']));
            if ($result === false) {
                return $this->fail(AiPersonaLogic::getError() ?: '人设不存在');
            }
            return $this->data(AiPersonaLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }


    /**
     * 获取人设关联的设备列表
     * @return Json
     */
    public function getDevices(): Json
    {
        try {
            $params = $this->request->get();
            $result = AiPersonaLogic::getDevicesByPersonaId(
                intval($params['persona_id'])
            );
            if ($result) {
                return $this->data(AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function analysis()
    {
        try {
            $params = $this->request->post();
            $result = AiPersonaLogic::analysis($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function report()
    {
        try {
            $params = $this->request->post();
            $result = AiPersonaLogic::report($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function knowledgeUpdate(){
        try {
            $params = $this->request->post();
            $result = AiPersonaLogic::knowledgeUpdate($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function clue(){
        try {
            $params = $this->request->get();
            $result = AiPersonaLogic::clue($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function wechat(){
        try {
            $params = $this->request->get();
            $result = AiPersonaLogic::wechat($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}