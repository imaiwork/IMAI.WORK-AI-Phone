<?php

namespace app\api\controller\videoImitation;

use app\api\controller\BaseApiController;
use app\api\logic\videoImitation\VideoImitationLogic;
use think\exception\HttpResponseException;

/**
 * 文案控制器
 */
class CopywritingController extends BaseApiController
{
    /**
     * 视频文案解析+仿写
     */
    public function video2text()
    {
        try {
            $params = (new \app\api\validate\videoImitation\CopywritingValidate())->post()->goCheck('video2text');

            $url = $params['url'] ?? '';
            $id = $params['id'] ?? 0;
            $personaId = $params['persona_id'] ?? 0;
            $visualMaterialSource = $params['visual_material_source'] ?? 3;

            // 调用 VideoImitationLogic 核心逻辑
            $result = VideoImitationLogic::createOrUpdateTask($url, $this->userId, $personaId, $id, $visualMaterialSource);

            if ($result) {
                $msg = '';
                if (is_array($result)
                    && ($result['resume_from'] ?? '') === VideoImitationLogic::RESUME_FROM_GENERATE
                ) {
                    $msg = '成片任务已重新提交';
                }
                return $this->success($msg, $result);
            }

            return $this->fail(VideoImitationLogic::getError() ?: '处理失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '处理异常');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage() !== '' ? $e->getMessage() : '处理异常');
        }
    }

    /**
     * 小红书图文解析+发布文案（异步改写）
     */
    public function image2text()
    {
        try {
            $params = (new \app\api\validate\videoImitation\CopywritingValidate())->post()->goCheck('image2text');

            $url = $params['url'] ?? '';
            $id = (int)($params['id'] ?? 0);
            $personaId = (int)($params['persona_id'] ?? 0);

            $result = VideoImitationLogic::createOrUpdateImageTextTask($url, $this->userId, $personaId, $id);

            if ($result) {
                return $this->success(data: $result);
            }

            return $this->fail(VideoImitationLogic::getError() ?: '处理失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '处理异常');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage() !== '' ? $e->getMessage() : '处理异常');
        }
    }

    /**
     * @desc 语音转文字
     * @date 2026/4/10 16:34
     * @author MonitorAllen
     */
    public function speech2Text()
    {
        try {
            $params = $this->request->param();
            $audioUrl = $params['audio_url'] ?? '';

            // 调用 VideoImitationLogic 核心逻辑
            $result = VideoImitationLogic::speech2Text($audioUrl);

            if ($result) {
                return $this->success(data: $result);
            }

            return $this->fail(VideoImitationLogic::getError() ?: '处理失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '处理异常');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage() !== '' ? $e->getMessage() : '处理异常');
        }
    }
}
