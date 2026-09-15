<?php

namespace app\api\controller;

use app\api\logic\VideoLogic;
use think\exception\HttpResponseException;

class VideoController extends BaseApiController
{
    /**
     * @author Rick
     * @date 2025/12/10 15:30
     */
    public function creationRecord()
    {
        $params = $this->request->get();
        $result = VideoLogic::getVideoCreationRecordLists($params, $this->userId);
        return $this->data($result);
    }

    /**
     * 删除视频任务
     */
    public function creationRecordDelete()
    {
        try {
            $params = $this->request->post();
            $result = VideoLogic::creationRecordDelete($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(VideoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function creationRecordUpdate()
    {
        try {
            $params = $this->request->post();
            $result = VideoLogic::creationRecordUpdate($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(VideoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}
