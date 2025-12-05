<?php

namespace app\api\controller;

use app\api\lists\draw\VideoRecordLists;
use app\api\logic\VolcLogic;

class VolcController extends BaseApiController
{
    /**
     * @notes 文件
     * @author Rick
     * @date 2025/7/7 15:30
     */
    public function lists()
    {
        return $this->dataLists(new VideoRecordLists());
    }

    /**
     * @desc 获取详情
     * @date 2025/7/9 14:30
     * @author Rick
     */
    public function detail()
    {
        $params = $this->request->get();
        $result = VolcLogic::detail($params);
        return $this->data($result);
    }

    public function deleteVideo()
    {
        $params = $this->request->post();
        return VolcLogic::deleteVideo($params) ? $this->success() : $this->fail(VolcLogic::getError());
    }

    /**
     * @desc 获取任务状态
     * @date 2025/7/7 15:47
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public function getTaskStatus()
    {
        $params = $this->request->post();
        $result = VolcLogic::getTaskStatus($params);
        if ($result) {
            return $this->data(VolcLogic::getReturnData());
        }
        return $this->data(VolcLogic::getError());
    }

    /**
     * @desc 提交文生视频任务
     * @date 2025/7/7 15:47
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public function text2Video()
    {
        $params = $this->request->post();
        $result = VolcLogic::text2video($params);
        if ($result) {
            return $this->data(VolcLogic::getReturnData());
        }
        return $this->data(VolcLogic::getError());
    }

    /**
     * @desc 提交图生视频任务
     * @date 2025/7/7 15:47
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @author Rick
     */
    public function image2Video()
    {
        $params = $this->request->post();
        $result = VolcLogic::image2video($params);
        if ($result) {
            return $this->data(VolcLogic::getReturnData());
        }
        return $this->data(VolcLogic::getError());
    }

}
