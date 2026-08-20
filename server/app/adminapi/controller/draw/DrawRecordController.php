<?php

declare(strict_types=1);

namespace app\adminapi\controller\draw;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\draw\DrawRecordLists;
use app\common\model\draw\DrawTask;
use think\response\Json;

/**
 * draw 生图/生视频记录（读新表 la_draw_task）
 *
 * GET /adminapi/draw.drawRecord/lists
 * GET /adminapi/draw.drawRecord/detail?id= 或 ?task_no=
 * POST /adminapi/draw.drawRecord/del
 */
class DrawRecordController extends BaseAdminController
{
    public function lists(): Json
    {
        return $this->dataLists(new DrawRecordLists());
    }

    public function detail(): Json
    {
        $id = (int)$this->request->get('id', 0);
        $taskNo = (string)$this->request->get('task_no', '');

        $query = DrawTask::alias('t')
            ->leftJoin('user u', 'u.id = t.user_id and t.user_id <> 0')
            ->field('t.*,u.nickname,u.avatar');
        if ($id > 0) {
            $query->where('t.id', $id);
        } elseif ($taskNo !== '') {
            $query->where('t.task_no', $taskNo);
        } else {
            return $this->fail('缺少 id 或 task_no');
        }

        $task = $query->findOrEmpty();
        if ($task->isEmpty()) {
            return $this->fail('任务不存在');
        }

        $data = $task->toArray();
        $data['status_text'] = DrawRecordLists::statusText((int)$data['status']);
        $data['assets'] = DrawRecordLists::formatAssets((int)$data['id']);

        return $this->data($data);
    }

    public function del(): Json
    {
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return $this->fail('缺少 id');
        }
        DrawTask::destroy($id);
        return $this->success('删除成功');
    }
}
