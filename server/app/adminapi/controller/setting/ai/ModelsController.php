<?php
namespace app\adminapi\controller\setting\ai;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\ai\AiModelsLogic;
use app\adminapi\validate\setting\ModelsValidate;
use app\common\service\chat\ChatModelsSyncService;
use app\common\service\draw\MediaModelsSyncService;
use think\response\Json;

/**
 * AI模型配置管理
 */
class ModelsController extends BaseAdminController
{
    /**
     * @notes 模型通道
     * @return Json
     * @author fzr
     */
    public function channels(): Json
    {
        $detail = AiModelsLogic::channel();
        return $this->data($detail);
    }

    /**
     * @notes 模型列表
     * @return Json
     * @author fzr
     */
    public function lists(): Json
    {
        $lists = AiModelsLogic::lists();
        return $this->data($lists);
    }

    /**
     * @notes 同步中台对话模型
     * @return Json
     */
    public function sync(): Json
    {
        try {
            $result = ChatModelsSyncService::sync();
            // GEO 监测计价模型缺失/未下发时,把提示随成功 toast 透出给管理员
            $notice = (string)($result['geo_monitor']['notice'] ?? '');
            $msg = $notice !== '' ? "同步成功;{$notice}" : '同步成功';
            return $this->success($msg, $result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 同步中台生图/生视频模型
     * @return Json
     */
    public function syncMedia(): Json
    {
        try {
            $result = MediaModelsSyncService::sync();
            return $this->success('同步成功', $result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 模型详情
     * @return Json
     * @author fzr
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        $result = AiModelsLogic::detail(intval($params['id']));
        if (!$result) {
            return $this->fail('模型不存在!');
        }
        return $this->data($result);
    }

    /**
     * @notes 模型创建
     * @return Json
     * @author fzr
     */
    public function add(): Json
    {
        $params = (new ModelsValidate())->post()->goCheck('add');
        $result = AiModelsLogic::add($params);
        if ($result === false) {
            return $this->fail(AiModelsLogic::getError());
        }
        return $this->success('创建成功', [], 1, 1);
    }

    /**
     * @notes 模型编辑
     * @return Json
     * @author fzr
     */
    public function edit(): Json
    {
        $params = $this->request->post();
        $result = AiModelsLogic::edit($params);
        if ($result === false) {
            return $this->fail(AiModelsLogic::getError());
        }
        return $this->success('编辑成功', [], 1, 1);
    }

    /**
     * @notes 模型删除
     * @return Json
     * @author fzr
     */
    public function del(): Json
    {
        $params = (new ModelsValidate())->post()->goCheck('id');
        $result = AiModelsLogic::del(intval($params['id']));
        if ($result === false) {
            return $this->fail(AiModelsLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 模型计费排序
     * @return Json
     * @author fzr
     */
    public function sort(): Json
    {
        $params = (new ModelsValidate())->post()->goCheck('sort');
        $result = AiModelsLogic::sort($params);
        if (!$result) {
            return $this->fail(AiModelsLogic::getError());
        }
        return $this->success('操作成功');
    }

    /**
     * @notes 一键开关聊天大模型
     * @return Json
     */
    public function switchChatModels(): Json
    {
        $params = (new ModelsValidate())->post()->goCheck('switchChatModels');
        $result = AiModelsLogic::switchChatModels($params);
        if ($result === false) {
            return $this->fail(AiModelsLogic::getError());
        }
        return $this->success('操作成功', [], 1, 1);
    }
}