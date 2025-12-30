<?php


namespace app\api\controller\sora;

use app\api\controller\BaseApiController;
use app\api\logic\sora\SoraVideoSettingLogic;
use think\exception\HttpResponseException;
use think\response\Json;

/**
 * SoraVideoController
 * @desc sora一键生成视频
 * @author dagouzi
 */
class SoraVideoSettingController extends BaseApiController
{

    public array $notNeedLogin = ['list'];

    /**
     * @desc 生成视频
     * @return \think\response\Json
     * @date 2024/9/30 16:26
     * @author dagouzi
     */
    public function add(): Json
    {
        $data = $this->request->post();
        $result = SoraVideoSettingLogic::add($data);
        if ($result) {
            return $this->data(SoraVideoSettingLogic::getReturnData());
        }
        return $this->fail(SoraVideoSettingLogic::getError());
    }

    /**
     * 获取视频设置详情
     */
    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = SoraVideoSettingLogic::detail($params['id']);
            if ($result) {
                return $this->data(SoraVideoSettingLogic::getReturnData());
            }
            return $this->fail(SoraVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function updateName()
    {
        try {
            $params = $this->request->post();
            $result = SoraVideoSettingLogic::updateName($params);
            if ($result) {
                return $this->data(SoraVideoSettingLogic::getReturnData());
            }
            return $this->fail(SoraVideoSettingLogic::getError());
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
            $result = SoraVideoSettingLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SoraVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function copywriting(){
        try {
            $params = $this->request->post();
            $result = SoraVideoSettingLogic::copywriting($params);
            if ($result) {
                return $this->data(SoraVideoSettingLogic::getReturnData());
            }
            return $this->fail(SoraVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
