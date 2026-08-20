<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\aiPersona\AiPersonaLists;
use app\api\logic\aiPersona\AiPersonaLogic;
use app\common\service\MemberService;
use Exception;
use think\facade\Db;
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
        $existing = (int)Db::name('ai_persona')
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->count();
        $reason = '';
        if (!MemberService::canCreate($this->userId, 'persona', $existing, $reason)) {
            return $this->fail($reason . ',请升级会员');
        }
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
     * Update hot words for AI persona.
     * @return Json
     */
    public function updateHotWords(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::updateHotWords($params, $this->userId);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('编辑成功', AiPersonaLogic::getReturnData());
    }

    public function updateOption(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::updateOption($params, $this->userId);
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
     * 爆款复刻：校验人设形象/音色是否可用
     * @return Json
     */
    public function checkViralAssets(): Json
    {
        $params = $this->request->get();
        try {
            $result = AiPersonaLogic::checkViralAssets(intval($params['id'] ?? 0), $this->userId);
            if ($result === false) {
                return $this->fail(AiPersonaLogic::getError() ?: '校验失败');
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

    public function publishConfigDetail(): Json
    {
        try {
            $params = $this->request->get();
            $personaId = intval($params['persona_id'] ?? $params['id'] ?? 0);
            $result = AiPersonaLogic::publishConfigDetail($personaId, $this->userId);
            if ($result) {
                return $this->data(AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function publishConfigUpdate(): Json
    {
        try {
            $params = $this->request->post();
            $result = AiPersonaLogic::publishConfigUpdate($params, $this->userId);
            if ($result) {
                return $this->success('配置成功', AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function contentPublishConfigDetail(): Json
    {
        return $this->publishConfigDetail();
    }

    public function contentPublishConfigUpdate(): Json
    {
        return $this->publishConfigUpdate();
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
    /**
     * @return Json
     */
    public function updateWechatPublishMode(): Json
    {
        $params = $this->request->post();
        $result = AiPersonaLogic::updateWechatPublishMode($params, $this->userId);
        if ($result === false) {
            return $this->fail(AiPersonaLogic::getError());
        }
        return $this->success('编辑成功', AiPersonaLogic::getReturnData());
    }

    
    public function hotWords(){
        try {
            $params = $this->request->get();
            $result = AiPersonaLogic::hotWords($params);
            if ($result) {
                return $this->success(data: AiPersonaLogic::getReturnData());
            }
            return $this->fail(AiPersonaLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
