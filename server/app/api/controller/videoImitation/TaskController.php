<?php

namespace app\api\controller\videoImitation;

use app\api\controller\BaseApiController;
use app\api\logic\videoImitation\TaskLogic;
use app\api\validate\videoImitation\TaskValidate;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * 视频复刻生成任务控制器
 */
class TaskController extends BaseApiController
{
    public array $notNeedLogin = ['notify', 'asyncParse'];

    /**
     * 任务列表
     */
    public function lists()
    {
        return $this->dataLists(new \app\api\lists\videoImitation\TaskLists());
    }

    /**
     * 删除任务 (软删除)
     */
    public function delete()
    {
        try {
            $params = (new TaskValidate())->post()->goCheck('delete');
            $result = TaskLogic::delete($params['id'], $this->userId);
            
            if ($result) {
                return $this->success('删除成功');
            }
            return $this->fail(TaskLogic::getError() ?: '删除失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '请求异常');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 任务详情
     */
    public function detail()
    {
        try {
            $params = (new TaskValidate())->get()->goCheck('detail');
            $result = TaskLogic::detail($params['id'], $this->userId);
            
            if ($result !== false) {
                return $this->success('获取成功', $result);
            }
            return $this->fail(TaskLogic::getError() ?: '获取失败');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '请求异常');
        }
    }

    /**
     * 确认发布文案
     */
    public function confirmPublishText()
    {
        try {
            $params = (new TaskValidate())->post()->goCheck('confirmPublishText');
            $id = $params['id'];
            $publishText = $params['publish_text'];
            $publishTopic = $params['publish_topic'];

            $result = TaskLogic::confirmPublishText($id, $this->userId, $publishText, $publishTopic);
            
            if ($result !== false) {
                return $this->success('确认成功');
            }
            return $this->fail(TaskLogic::getError() ?: '确认失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '请求异常');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 用户确认文本并开始生成视频
     */
    public function generate()
    {
        try {
            $params = (new TaskValidate())->post()->goCheck('generate');
            $id = $params['id'];
            $rewrittenText = $params['rewritten_text'];

            $result = TaskLogic::generate($id, $this->userId, $rewrittenText);
            
            if ($result !== false) {
                return $this->success('任务提交成功', $result);
            }
            
            return $this->fail(TaskLogic::getError() ?: '处理失败');
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '处理异常');
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 接收远端服务(如闪剪)异步回调
     */
    public function notify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('shanjiannotice')->write('视频复刻接收回调: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            
            $taskId = $data['task_id'] ?? '';
            if (empty($taskId)) {
                return $this->fail('缺少任务ID');
            }

            $result = TaskLogic::notify($data);
            
            if (!$result) {
                return $this->fail(TaskLogic::getError());
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('shanjiannotice')->error('视频复刻回调处理异常: ' . $e->getMessage());
            return $this->fail('fail');
        }
    }

    /**
     * 后台异步解析任务端点（由 cURL 触发，替代 Queue）
     */
    public function asyncParse()
    {
        // 允许后台断开连接后继续执行
        ignore_user_abort(true);
        set_time_limit(0);

        try {
            $data = $this->request->post();
            $taskId = $data['task_id'] ?? 0;

            if (!$taskId) {
                return $this->fail('缺少 task_id');
            }

            // 直接调用处理逻辑
            \app\api\logic\videoImitation\VideoImitationLogic::processParseTask($data);
            
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::write("视频复刻接口解析异常: " . $e->getMessage(), 'error');
            return $this->fail('fail');
        }
    }
}
