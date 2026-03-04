<?php


namespace app\api\controller\storyboard;

use app\api\controller\BaseApiController;
use app\api\logic\storyboard\StoryboardVideoTaskLogic;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * StoryboardVideoController
 * @desc 分镜一键生成视频
 * @author dagouzi
 */
class StoryboardVideoTaskController extends BaseApiController
{

    public array $notNeedLogin = ['list','notify'];

    public function notify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('storyboard')->write('接收分镜参数'.json_encode($data));
            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val){
                Log::channel('storyboard')->write('重复请求');
                return $this->fail('重复请求');
            }
            cache($key, 1, 20);
            $result = StoryboardVideoTaskLogic::notify($data);
            if (!$result) {
                return $this->fail(StoryboardVideoTaskLogic::getError());
            }
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('storyboard')->write('分镜回调失败'.$e->getMessage());
            return $this->success('fail');
        }
    }

    /**
     * @desc 生成视频
     * @return \think\response\Json
     * @date 2024/9/30 16:26
     * @author dagouzi
     */
    public function videoTask(): Json
    {
        $data = $this->request->post();
        $result = StoryboardVideoTaskLogic::videoTask($data);
        if ($result) {
            return $this->data(StoryboardVideoTaskLogic::getReturnData());
        }
        return $this->fail(StoryboardVideoTaskLogic::getError());
    }

    /**
     * @desc 查询视频生成状态
     * @return \think\response\Json
     * @date 2024/9/30 16:26
     * @author dagouzi
     */
    public function status(): Json
    {
        $data = $this->request->get();
        $result = StoryboardVideoTaskLogic::status($data);
        if ($result) {
            return $this->data(StoryboardVideoTaskLogic::getReturnData());
        }
        return $this->fail(StoryboardVideoTaskLogic::getError());
    }

    /**
     * 删除视频任务
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = StoryboardVideoTaskLogic::delete($params['id']);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(StoryboardVideoTaskLogic::getError());
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
            $params = $this->request->get();
            $result = StoryboardVideoTaskLogic::detail($params['id']);
            if ($result) {
                return $this->success(data: StoryboardVideoTaskLogic::getReturnData());
            }
            return $this->fail(StoryboardVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}
