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
            $params['media_type'] = 1;      // 1=视频 2=图文
            $params['scene'] = 1;           // 1代表从创作开始等场景来源

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
