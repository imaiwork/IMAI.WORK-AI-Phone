<?php


namespace app\api\controller\sora;

use app\api\controller\BaseApiController;
use app\api\logic\sora\SoraVideoTaskLogic;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

/**
 * SoraVideoController
 * @desc sora一键生成视频
 * @author dagouzi
 */
class SoraVideoTaskController extends BaseApiController
{

    public array $notNeedLogin = ['list','notify'];

    public function notify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('sora')->write('接收sora参数'.json_encode($data));
            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val){
                Log::channel('sora')->write('重复请求');
                return $this->fail('重复请求');
            }
            cache($key, 1, 20);
            $result = SoraVideoTaskLogic::notify($data);
            if (!$result) {
                return $this->fail(SoraVideoTaskLogic::getError());
            }
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('sora')->write('sora回调失败'.$e->getMessage());
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
        $result = SoraVideoTaskLogic::videoTask($data);
        if ($result) {
            return $this->data(SoraVideoTaskLogic::getReturnData());
        }
        return $this->fail(SoraVideoTaskLogic::getError());
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
        $result = SoraVideoTaskLogic::status($data);
        if ($result) {
            return $this->data(SoraVideoTaskLogic::getReturnData());
        }
        return $this->fail(SoraVideoTaskLogic::getError());
    }

    /**
     * 删除视频任务
     */
    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = SoraVideoTaskLogic::delete($params['id']);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(SoraVideoTaskLogic::getError());
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
            $result = SoraVideoTaskLogic::detail($params['id']);
            if ($result) {
                return $this->success(data: SoraVideoTaskLogic::getReturnData());
            }
            return $this->fail(SoraVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}
