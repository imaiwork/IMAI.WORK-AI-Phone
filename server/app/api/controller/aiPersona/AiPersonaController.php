<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\AiPersonaLists;
use app\api\logic\aiPersona\AiPersonaLogic;
use Exception;
use think\response\Json;

class AiPersonaController extends BaseApiController
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
     * 新增AI人设
     * @return Json
     */
    public function add(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::add($params, $this->userId);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('创建成功', AiPersonaLogic::getReturnData());
    }

    /**
     * 编辑AI人设
     * @return Json
     */
    public function edit(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::edit($params, $this->userId);
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
        $result = AiPersonaLogic::update($params, $this->userId);
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
        $result = AiPersonaLogic::delete(intval($params['id']), $this->userId);
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
            $result = AiPersonaLogic::detail(intval($params['id']), $this->userId);
            if ($result === false) {
                return $this->fail(AiPersonaLogic::getError() ?: '人设不存在');
            }
            return $this->data(AiPersonaLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取AI人设详情
     * @return Json
     */
    public function configStatus(): Json
    {
        $params = $this->request->get();
        try {
            $result = AiPersonaLogic::configStatus(intval($params['id']), $this->userId);
            if ($result === false) {
                return $this->fail(AiPersonaLogic::getError() ?: '人设不存在');
            }
            return $this->data(AiPersonaLogic::getReturnData());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 更新报告生成状态
     * @return Json
     */
    public function updateReportStatus(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::updateReportStatus(
            intval($params['persona_id']),
            intval($params['report_status']),
            $params['report_content'] ?? ''
        );
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('更新成功');
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
                intval($params['persona_id']),
                $this->userId
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
            $result = AiPersonaLogic::knowledgeUpdate($params, $this->userId);
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
            $result = AiPersonaLogic::clue($params, $this->userId);
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
            $result = AiPersonaLogic::wechat($params, $this->userId);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}