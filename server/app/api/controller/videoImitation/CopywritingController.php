<?php

namespace app\api\controller\videoImitation;

use app\api\controller\BaseApiController;
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

            // 调用 VideoImitationLogic 核心逻辑
            $result = \app\api\logic\videoImitation\VideoImitationLogic::createOrUpdateTask($url, $this->userId, $personaId, $id);

            if ($result) {
                return $this->success(data: $result);
            }

            return $this->fail('处理失败');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '处理异常');
        }
    }
}
