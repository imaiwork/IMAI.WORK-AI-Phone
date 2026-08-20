<?php

namespace app\api\controller\videoImitation;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\validate\videoImitation\PublishValidate;
use app\api\logic\videoImitation\ImitationPublishLogic;

/**
 * 视频复刻发布设置
 */
class PublishController extends BaseApiController
{
    /**
     * @desc 添加发布设置 (由前端勾选任务下发)
     */
    public function add()
    {
        try {
            $params = (new PublishValidate())->post()->goCheck('add');
            
            // 补充适配矩阵调度的默认参数
            $params['task_type'] = 6;       // 6代表视频复刻
            $params['scene'] = 1;

            $videoIds = $params['video_ids'] ?? [];
            if (!is_array($videoIds)) {
                $videoIds = [];
            }
            $mediaTypes = \app\common\model\videoImitation\VideoImitationTask::where('id', 'in', $videoIds)
                ->where('user_id', $this->userId)
                ->column('media_type');
            $mediaTypes = array_values(array_unique(array_map('intval', $mediaTypes)));
            if (count($mediaTypes) > 1) {
                return $this->fail('同一发布计划不能混选视频与图文任务');
            }
            $params['media_type'] = (int)($mediaTypes[0] ?? 1);

            $result = ImitationPublishLogic::add($params);
            if ($result) {
                return $this->success(data: ImitationPublishLogic::getReturnData());
            }
            return $this->fail(ImitationPublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron() {
        ImitationPublishLogic::setImitationPublishDetail();
        return $this->success();
    }   
}
