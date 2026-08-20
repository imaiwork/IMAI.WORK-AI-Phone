<?php

declare(strict_types=1);

namespace app\api\controller\draw;

use app\api\controller\BaseApiController;
use app\api\logic\draw\DrawLogic;
use app\api\logic\draw\PptLogic;
use think\response\Json;

/**
 * draw 生图/生视频 / PPT
 *
 * 路由示例：
 * POST /api/draw.draw/generateImage
 * POST /api/draw.draw/generateVideo
 * POST /api/draw.draw/optimizeImagePrompt
 * POST /api/draw.draw/optimizeVideoPrompt
 * POST /api/draw.draw/pptFollowup
 * POST /api/draw.draw/pptChapters
 * POST /api/draw.draw/pptSubmitSlides
 * POST /api/draw.draw/pptRegenerateSlide
 * GET  /api/draw.draw/detail
 * GET  /api/draw.draw/lists
 * POST /api/draw.draw/getTaskStatus
 * GET  /api/draw.draw/conversationLists
 * GET  /api/draw.draw/conversationDetail
 * POST /api/draw.draw/conversationDelete
 * POST /api/draw.draw/notify   （中台回调，免登录）
 */
class DrawController extends BaseApiController
{
    public array $notNeedLogin = ['notify'];

    public function generateImage(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::generateImage($params, $this->userId);
        return $ok
            ? $this->success('提交成功', DrawLogic::getReturnData() ?: [])
            : $this->fail(DrawLogic::getError());
    }

    public function generateVideo(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::generateVideo($params, $this->userId);
        return $ok
            ? $this->success('提交成功', DrawLogic::getReturnData() ?: [])
            : $this->fail(DrawLogic::getError());
    }

    /** 图片提示词优化（中台 Coze，不计费） */
    public function optimizeImagePrompt(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::optimizeImagePrompt($params);
        return $ok
            ? $this->success('ok', DrawLogic::getReturnData() ?: [])
            : $this->fail(DrawLogic::getError());
    }

    /** 视频提示词优化（中台 Coze，不计费） */
    public function optimizeVideoPrompt(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::optimizeVideoPrompt($params);
        return $ok
            ? $this->success('ok', DrawLogic::getReturnData() ?: [])
            : $this->fail(DrawLogic::getError());
    }

    /** PPT 追问问卷（Coze，不计费） */
    public function pptFollowup(): Json
    {
        $ok = PptLogic::followup($this->request->post());
        return $ok
            ? $this->success('ok', PptLogic::getReturnData() ?: [])
            : $this->fail(PptLogic::getError());
    }

    /** PPT 章节大纲（Coze，不计费） */
    public function pptChapters(): Json
    {
        $ok = PptLogic::chapters($this->request->post());
        return $ok
            ? $this->success('ok', PptLogic::getReturnData() ?: [])
            : $this->fail(PptLogic::getError());
    }

    /** PPT 批量提交生图（有结果才扣费） */
    public function pptSubmitSlides(): Json
    {
        $ok = PptLogic::submitSlides($this->request->post(), $this->userId);
        return $ok
            ? $this->success('提交成功', PptLogic::getReturnData() ?: [])
            : $this->fail(PptLogic::getError());
    }

    /** PPT 单页重生 */
    public function pptRegenerateSlide(): Json
    {
        $ok = PptLogic::regenerateSlide($this->request->post(), $this->userId);
        return $ok
            ? $this->success('提交成功', PptLogic::getReturnData() ?: [])
            : $this->fail(PptLogic::getError());
    }

    public function detail(): Json
    {
        $params = $this->request->get();
        $data = DrawLogic::detail($params, $this->userId);
        if ($data === []) {
            return $this->fail('任务不存在');
        }
        return $this->success('ok', $data);
    }

    public function lists(): Json
    {
        $params = $this->request->get();
        return $this->success('ok', DrawLogic::lists($params, $this->userId));
    }

    public function getTaskStatus(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::getTaskStatus($params, $this->userId);
        return $ok
            ? $this->success('ok', DrawLogic::getReturnData())
            : $this->fail(DrawLogic::getError());
    }

    public function conversationLists(): Json
    {
        $params = $this->request->get();
        return $this->success('ok', DrawLogic::conversationLists($params, $this->userId));
    }

    public function conversationDetail(): Json
    {
        $params = $this->request->get();
        $data = DrawLogic::conversationDetail($params, $this->userId);
        if ($data === []) {
            return $this->fail('会话不存在');
        }
        return $this->success('ok', $data);
    }

    public function conversationDelete(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::conversationDelete($params, $this->userId);
        return $ok ? $this->success('删除成功') : $this->fail(DrawLogic::getError());
    }

    public function notify(): Json
    {
        $params = $this->request->post();
        $ok = DrawLogic::notify($params);
        return $ok
            ? $this->success('ok', DrawLogic::getReturnData())
            : $this->fail(DrawLogic::getError());
    }
}
