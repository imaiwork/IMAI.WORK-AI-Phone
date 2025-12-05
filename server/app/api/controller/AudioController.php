<?php

namespace app\api\controller;

use app\api\lists\audio\AudioLists;
use app\api\logic\AudioLogic;
use app\api\logic\service\AudioService;
use app\common\model\audio\Audio;
use think\facade\Log;
use think\response\Json;

class AudioController extends BaseApiController
{
    public array $notNeedLogin = [
        'notify',
    ];


    /**
     * 列表
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function lists(): Json
    {
        return $this->dataLists(new AudioLists());
    }


    /**
     * 创建任务
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function task(): Json
    {
        $getData = $this->request->post();
        $upload  = AudioLogic::task($getData);
        return $upload ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }

    /**
     * 创建任务
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function batch(): Json
    {
        $getData = $this->request->post();
        $upload  = AudioLogic::batch($getData);
        return $upload ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }


    /**
     * 富文本
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function text(): Json
    {
        $getData = $this->request->post();
        $upload  = AudioLogic::text($getData);
        return $upload ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }

    /**
     * 创建任务
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function status(): Json
    {
        $taskId = $this->request->post('task_id', '');
        if (empty($taskId)) {
            return $this->fail('参数错误');
        }
        $status = AudioLogic::status($taskId);

        return $status ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }

    /**
     * 重试
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function retry(): Json
    {
        $audioId = $this->request->post('id', '');
        if (empty($audioId)) {
            return $this->fail('参数错误');
        }
        $status = AudioLogic::retry($audioId);

        return $status ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }


    /**
     * 暂停
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function pause(): Json
    {
        $audioId = $this->request->post('id', '');
        if (empty($audioId)) {
            return $this->fail('参数错误');
        }
        $status = AudioLogic::pause($audioId);

        return $status ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }

    /**
     * 继续
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function continue(): Json
    {
        $audioId = $this->request->post('id', '');
        if (empty($audioId)) {
            return $this->fail('参数错误');
        }
        $status = AudioLogic::continue($audioId);

        return $status ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }


    /**
     * 停止
     * @return Json
     * @author L
     * @data 2024/6/28 11:08
     */
    public function stop(): Json
    {
        $audioId = $this->request->post('id', '');
        $url     = $this->request->post('url', '');
        if (empty($audioId) || empty($url)) {
            return $this->fail('参数错误');
        }
        $status = AudioLogic::stop($audioId, $url);

        return $status ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }

    /**
     * 异步接收音频转文字回调
     * @return Json
     * @author L
     * @data 2024/6/24 18:16
     */
    public function notify(): Json
    {

        try {
            $data = request()->all();

            AudioLogic::updateAudioInfo($data);

            return $this->success('ok');
        } catch (\Exception $e) {

            return $this->success('fail');
        }
    }


    /**
     * 详情
     * @return Json
     * @throws \Exception
     * @author L
     * @data 2024/6/28 11:23
     */
    public function detail(): Json
    {
        $audioId = $this->request->get('id', '');
        $detail  = AudioLogic::detail($audioId);
        return $detail ? $this->success(data: AudioLogic::getReturnData()) : $this->fail(AudioLogic::getError());
    }


    /**
     * delete Audio
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        return AudioLogic::delete($params) ? $this->success() : $this->fail(AudioLogic::getError());
    }
}
