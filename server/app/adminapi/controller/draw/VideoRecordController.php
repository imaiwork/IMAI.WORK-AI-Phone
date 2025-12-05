<?php

namespace app\adminapi\controller\draw;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\video\VideoLogic;
use app\adminapi\lists\draw\VideoRecordLists;
use think\response\Json;

/**
 * Ai视频 火山引擎 即梦AI
 * Class HdImageController
 * @package app\adminapi\controller\volc
 */
class VideoRecordController extends BaseAdminController
{
    /**
     * @desc 获取列表
     * @return Json
     * @author Rick
     * @date 2025/7/9 18:06
     */
    public function lists(): Json
    {
        return $this->dataLists(new VideoRecordLists());
    }

    /**
     * @desc 获取详情
     * @return Json
     * @author Rick
     * @date 2025/7/9 14:30
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        $result = VideoLogic::detail($params);
        return $this->data($result);
    }

    /**
     * @desc 获取详情
     * @return Json
     * @author Rick
     * @date 2025/7/9 14:30
     */
    public function del(): Json
    {
        $params = $this->request->post();
        return VideoLogic::del($params) ? $this->success() : $this->fail(VideoLogic::getError());
    }
}
