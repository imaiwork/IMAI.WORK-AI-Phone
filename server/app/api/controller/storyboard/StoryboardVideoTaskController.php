<?php


namespace app\api\controller\storyboard;

use app\api\controller\BaseApiController;
use app\api\logic\storyboard\StoryboardVideoTaskLogic;
use think\exception\HttpResponseException;
use think\response\Json;

/**
 * StoryboardVideoController
 * @desc 分镜一键生成视频
 * @author dagouzi
 */
class StoryboardVideoTaskController extends BaseApiController
{

    public array $notNeedLogin = ['list'];

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
