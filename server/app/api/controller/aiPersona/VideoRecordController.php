<?php

namespace app\api\controller\aiPersona;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\ShanjianVideoTaskLists;
use app\api\logic\aiPersona\VideoRecordLogic;
use app\api\validate\aiPersona\VideoRecordValidate;
use think\exception\HttpResponseException;

class VideoRecordController extends BaseApiController
{
    public function lists()
    {
        return $this->dataLists(new ShanjianVideoTaskLists());
    }

    /**
     * 视频生成失败重试
     */
    public function retry()
    {
        try {
            $params = (new VideoRecordValidate())->post()->goCheck('retry');
            $result = VideoRecordLogic::retry($params);
            if ($result) {
                return $this->success('操作成功', VideoRecordLogic::getReturnData());
            }
            return $this->fail(VideoRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
