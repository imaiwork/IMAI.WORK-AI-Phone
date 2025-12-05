<?php


namespace app\api\controller;

use app\api\logic\HumanLogic;
use app\common\service\human\HumanService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;
use think\response\Json;
/**
 * HumanController
 * @desc 数字人
 * @author dagouzi
 */
class HumanController extends BaseApiController
{

    public array $notNeedLogin = ['test', 'list', 'add', 'dyToText', 'notify','clipnotify'];

    public function test() {

    }

    /**
     * @desc 生成视频
     * @return \think\response\Json
     * @date 2024/9/30 16:26
     * @author dagouzi
     */
    public function videoTask()
    {
        $data = $this->request->post();
        $result = HumanLogic::videoTask($data);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 视频重试
     * @return \think\response\Json
     * @date 2024/9/28 17:47
     * @author dagouzi
     */
    public function videoRetry()
    {
        $id = $this->request->post('video_id/d');
        $result = HumanLogic::videoRetry($id);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 视频列表
     * @return \think\response\Json
     * @date 2024/9/30 19:14
     * @author dagouzi
     */
    public function videoLists()
    {
        $data = $this->request->get();
        $result = HumanLogic::videoLists($data);
        return $this->data($result);
    }

    /**
     * @desc 创建形象
     * @return \think\response\Json
     * @date 2024/9/28 17:47
     * @author dagouzi
     */
    public function createAnchor()
    {
        $data = $this->request->post();
        $result = HumanLogic::createAnchor($data);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 形象重试
     * @return \think\response\Json
     * @date 2024/9/28 17:47
     * @author dagouzi
     */
    public function anchorRetry()
    {
        $id = $this->request->post('anchor_id/d');
        $result = HumanLogic::anchorRetry($id);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 形象列表
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:10
     * @author dagouzi
     */
    public function anchorLists()
    {
        $data = $this->request->get();
        $result = HumanLogic::anchorLists($data);
        return $this->data($result);
    }

    /**
     * @desc 删除形象
     * @return \think\response\Json
     * @date 2024/9/28 18:32
     * @author dagouzi
     */
    public function anchorDelete()
    {
        $params = $this->request->post();
        return HumanLogic::anchorDelete($params) ? $this->success() : $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 语音克隆
     * @return \think\response\Json
     * @date 2024/9/28 18:33
     * @author dagouzi
     */
    public function createVoice()
    {
        $data = $this->request->post();
        $result = HumanLogic::createVoice($data);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 音色重试
     * @return \think\response\Json
     * @date 2024/9/28 17:47
     * @author dagouzi
     */
    public function voiceRetry()
    {
        $id = $this->request->post('voice_id/d');
        $result = HumanLogic::voiceRetry($id);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 音色列表
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:41
     * @author dagouzi
     */
    public function voiceLists()
    {
        $data = $this->request->get();
        $result = HumanLogic::voiceLists($data);
        return $this->data($result);
    }

    public function builtInVoiceLists()
    {
        $data = $this->request->get();
        $result = HumanLogic::voiceLists($data);
        return $this->data($result);
    }

    /**
     * @desc 删除音色
     * @return \think\response\Json
     * @date 2024/9/28 18:41
     * @author dagouzi
     */
    public function voiceDelete()
    {
        $params = $this->request->post();
        return HumanLogic::voiceDelete($params) ? $this->success() : $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 创建音频
     * @return \think\response\Json
     * @date 2024/9/28 18:33
     * @author dagouzi
     */
    public function createAudio()
    {
        $data = $this->request->post();
        $result = HumanLogic::createAudio($data);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 音频重试
     * @return \think\response\Json
     * @date 2024/9/28 17:47
     * @author dagouzi
     */
    public function audioRetry()
    {
        $id = $this->request->post('audio_id/d');
        $result = HumanLogic::audioRetry($id);
        if ($result) {
            return $this->data(HumanLogic::getReturnData());
        }
        return $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 音频列表
     * @return Json
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @date 2024/9/28 18:41
     * @author dagouzi
     */
    public function audioLists()
    {
        $data = $this->request->get();
        $result = HumanLogic::audioLists($data);
        return $this->data($result);
    }

    /**
     * @desc 删除音频
     * @return \think\response\Json
     * @date 2024/9/28 18:41
     * @author dagouzi
     */
    public function audioDelete()
    {
        $params = $this->request->post();
        return HumanLogic::audioDelete($params) ? $this->success() : $this->fail(HumanLogic::getError());
    }

    /**
     * @desc 删除视频
     * @return \think\response\Json
     * @date 2024/9/28 18:41
     * @author dagouzi
     */
    public function videoDelete()
    {
        $params = $this->request->post();
        return HumanLogic::videoDelete($params) ? $this->success() : $this->fail(HumanLogic::getError());
    }


    /**
     * 异步接收数字人回掉回调
     */
    public function notify(): Json
    {

        try {
            $type = $this->request->param('human_type');
            $modelVersion = $this->request->param('model_version');
            $data = $this->request->all();
            Log::channel('human')->write('接收数字人参数'.json_encode($data));

            if (isset($data['data'])) {

                $data = $this->request->param('data');
            }
            switch ($type) {

                case 'avatar':
                    HumanLogic::updateAnchor($data, $modelVersion);
                    break;
                case 'voice':
                    HumanLogic::updateVoice($data, $modelVersion);
                    break;
                case 'audio':
                    HumanLogic::updateAudio($data, $modelVersion);
                    break;
                case 'video':
                    HumanLogic::updateVideo($data, $modelVersion);
                    break;
                default:
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('human')->write('数字人参数'.json_encode($data).'数字人回调失败'.$e->getMessage());
            return $this->success('fail');
        }
    }

    /**
     * 文案
     * @return Json
     * @author L
     * @data 2024/6/12 14:04
     */
    public function copywriting()
    {
        $params = $this->request->post();
        return HumanLogic::copywriting($params) ? $this->data(HumanLogic::getReturnData()) : $this->fail(HumanLogic::getError());
    }

    /**
     * 异步接收数字人回掉回调
     */
    public function clipnotify(): Json
    {

        try {
            $data = $this->request->all();
            Log::channel('clip')->write('剪辑视频接收数字人参数'.json_encode($data));
            $result = HumanLogic::updateClipVideo($data);
            if (!$result){
                return   $this->fail(HumanLogic::getError());
            }

            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('clip')->write('剪辑视频接收数字人参数'.json_encode($data).'数字人回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }


    public function videoUpdate()
    {
        try {
            $params = $this->request->post();
            $result = HumanLogic::updateTaskName($params);
            if ($result) {
                return $this->success('操作成功');
            }
            return $this->fail(HumanLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
