<?php

namespace app\adminapi\controller\videoImitation;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\videoImitation\TaskLists;
use think\facade\Db;
use think\response\Json;

class VideoImitationTaskController extends BaseAdminController
{
    /**
     * 视频复刻任务列表
     * @return Json
     */
    public function lists(): Json
    {
        return $this->dataLists(new TaskLists());
    }

    /**
     * 删除视频复刻任务（支持批量，仅标记 task_delete 为 1）
     * @return Json
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        // 兼容前端可能传 id 或 ids 数组
        $ids = $params['id'] ?? $params['ids'] ?? [];
        if (empty($ids)) {
            return $this->fail('请选择要删除的任务');
        }
        $ids = is_string($ids) ? json_decode($ids, true) : $ids;
        
        try {
            Db::name('video_imitation_task')->where('id', 'in', $ids)->update(['task_delete' => 1]);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->fail('删除失败：' . $e->getMessage());
        }
    }
}
