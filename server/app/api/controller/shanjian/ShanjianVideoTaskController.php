<?php

namespace app\api\controller\shanjian;

use app\adminapi\lists\shanjian\ShanjianVideoTaskLists;
use app\api\controller\BaseApiController;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\validate\shanjian\ShanjianVideoTaskValidate;
use app\common\model\shanjian\ShanjianVideoTask;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * ShanjianVideoTaskController
 * 闪剪视频任务控制器
 */
class ShanjianVideoTaskController extends BaseApiController
{
    public array $notNeedLogin = ['notify','composite','covernotify'];


    /**
     * 删除视频任务
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = ShanjianVideoTaskLogic::delete($params['id']);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 获取视频任务详情
     */
    public function detail()
    {
        try {
            $params = (new ShanjianVideoTaskValidate())->get()->goCheck('detail');
            $result = ShanjianVideoTaskLogic::detail($params['id']);
            if ($result) {
                return $this->success(data: ShanjianVideoTaskLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 手动下载/转存成片（原链接 -> 本地/站点存储）
     */
    public function download()
    {
        try {
            $params = (new ShanjianVideoTaskValidate())->post()->goCheck('download');
            $result = ShanjianVideoTaskLogic::downloadResult((int)$params['id']);
            if ($result) {
                return $this->data(ShanjianVideoTaskLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * 异步接收闪剪回调
     */
    public function notify(): Json
    {
        $lockKey = '';
        $gotLock = false;
        try {
            $data = $this->request->all();
            Log::channel('shanjiannotice')->write('接收闪剪参数'.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $taskId = $data['task_id'] ?? '';
            if (empty($taskId)) {
                return $this->fail('缺少任务ID');
            }

            // 已终态：幂等返回成功，避免闪剪重试再次撞锁
            $existStatus = ShanjianVideoTask::where('task_id', $taskId)->value('status');
            if ($existStatus !== null && in_array((int)$existStatus, [
                ShanjianVideoTask::STATUS_FAILED,
                ShanjianVideoTask::STATUS_SUCCESS,
            ], true)) {
                return $this->success('ok1');
            }

            $lockKey = 'shanjian_video_task_notify_' . $taskId;
            $gotLock = ShanjianVideoTaskLogic::acquireRedisLock($lockKey, 180);
            if (!$gotLock) {
                // 正在处理中：先 ack，避免闪剪密集重推
                return $this->success('ok2');
            }

            $result = ShanjianVideoTaskLogic::notify($data);
            if (!$result) {
                ShanjianVideoTaskLogic::releaseRedisLock($lockKey);
                return $this->fail(ShanjianVideoTaskLogic::getError());
            }

            ShanjianVideoTaskLogic::keepRedisLock($lockKey, 20);
            return $this->success('ok3');
        } catch (\Exception $e) {
            if ($gotLock && $lockKey !== '') {
                ShanjianVideoTaskLogic::releaseRedisLock($lockKey);
            }
            Log::channel('shanjiannotice')->write('闪剪回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }


    public function copywriting(){
        $params = $this->request->post();
        return ShanjianVideoTaskLogic::copywriting($params) ? $this->data(ShanjianVideoTaskLogic::getReturnData()) : $this->fail(ShanjianVideoTaskLogic::getError());
    }

    /**
     * 获取视频任务列表
     */
     public function lists()
    {
        return $this->dataLists(new ShanjianVideoTaskLists());
    }


    /**
     * 异步接收闪剪封面回调
     */
    public function covernotify(): Json
    {
        $lockKey = '';
        $gotLock = false;
        try {
            $data = $this->request->all();
            Log::channel('shanjiannotice')->write('接收闪剪封面参数'.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $taskId = $data['task_id'] ?? '';
            if (empty($taskId)) {
                return $this->fail('缺少任务ID');
            }

            $thumbStatus = ShanjianVideoTask::where('task_id', $taskId)->value('thumb_status');
            if ($thumbStatus !== null && in_array((int)$thumbStatus, [2, 3], true)) {
                return $this->success('ok');
            }

            $lockKey = 'shanjian_video_task_cover_notify_' . $taskId;
            $gotLock = ShanjianVideoTaskLogic::acquireRedisLock($lockKey, 180);
            if (!$gotLock) {
                return $this->success('ok');
            }

            $result = ShanjianVideoTaskLogic::covernotify($data);
            if (!$result) {
                ShanjianVideoTaskLogic::releaseRedisLock($lockKey);
                return $this->fail(ShanjianVideoTaskLogic::getError());
            }

            ShanjianVideoTaskLogic::keepRedisLock($lockKey, 20);
            return $this->success('ok');
        } catch (\Exception $e) {
            if ($gotLock && $lockKey !== '') {
                ShanjianVideoTaskLogic::releaseRedisLock($lockKey);
            }
            Log::channel('shanjiannotice')->write('闪剪封面回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }
}