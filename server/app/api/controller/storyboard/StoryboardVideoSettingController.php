<?php


namespace app\api\controller\storyboard;

use app\api\controller\BaseApiController;
use app\api\logic\storyboard\StoryboardVideoSettingLogic;
use think\exception\HttpResponseException;
use think\response\Json;

/**
 * StoryboardVideoSettingController
 * @desc 分镜一键生成视频
 */
class StoryboardVideoSettingController extends BaseApiController
{

    public array $notNeedLogin = ['list'];

    /**
     * @desc 生成视频
     * @return \think\response\Json
     */
    public function add(): Json
    {
        $data = $this->request->post();
        $result = StoryboardVideoSettingLogic::add($data);
        if ($result) {
            return $this->data(StoryboardVideoSettingLogic::getReturnData());
        }
        return $this->fail(StoryboardVideoSettingLogic::getError());
    }

    /**
     * @desc 生成状态
     * @return \think\response\Json
     */
    public function status(): Json
    {
        $data = $this->request->post();
        $result = StoryboardVideoSettingLogic::status($data,$this->userId);
        if ($result) {
            return $this->data(StoryboardVideoSettingLogic::getReturnData());
        }
        return $this->fail(StoryboardVideoSettingLogic::getError());
    }
    /**
     * 获取视频设置详情
     */
    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = StoryboardVideoSettingLogic::detail($params['id']);
            if ($result) {
                return $this->data(StoryboardVideoSettingLogic::getReturnData());
            }
            return $this->fail(StoryboardVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function updateName()
    {
        try {
            $params = $this->request->post();
            $result = StoryboardVideoSettingLogic::updateName($params);
            if ($result) {
                return $this->data(StoryboardVideoSettingLogic::getReturnData());
            }
            return $this->fail(StoryboardVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 删除视频设置
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = StoryboardVideoSettingLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(StoryboardVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}
