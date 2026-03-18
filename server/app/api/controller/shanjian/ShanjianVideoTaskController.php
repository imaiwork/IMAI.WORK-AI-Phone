<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\logic\shanjian\ShanjianVideoTaskLogic;
use app\api\validate\shanjian\ShanjianVideoTaskValidate;
use app\common\service\FileService;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * ShanjianVideoTaskController
 * 闪剪视频任务控制器
 */
class ShanjianVideoTaskController extends BaseApiController
{
    public array $notNeedLogin = ['notify','composite'];


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
     * 异步接收闪剪回调
     */
    public function notify(): Json
    {
        $lockKey = '';
        try {
            $data = $this->request->all();
            Log::channel('shanjiannotice')->write('接收闪剪参数'.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            $taskId = $data['task_id'] ?? '';
            if (empty($taskId)) {
                return $this->fail('缺少任务ID');
            }

            $lockKey = 'shanjian_video_task_notify_' . $taskId;
            $lock = cache($lockKey);
            if ($lock) {
                return $this->fail('任务正在处理中，请勿重复请求');
            }
            cache($lockKey, 1, 300);

            $result = ShanjianVideoTaskLogic::notify($data);
            cache($lockKey, null);
            
            if (!$result) {
                return $this->fail(ShanjianVideoTaskLogic::getError());
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('shanjiannotice')->write('闪剪回调失败'.$e->getMessage());
            if (!empty($lockKey)) {
                cache($lockKey, null);
            }
            return $this->fail('fail');
        }
    }


    public function copywriting(){
        $params = $this->request->post();
        return ShanjianVideoTaskLogic::copywriting($params) ? $this->data(ShanjianVideoTaskLogic::getReturnData()) : $this->fail(ShanjianVideoTaskLogic::getError());
    }


}