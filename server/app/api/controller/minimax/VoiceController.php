<?php

namespace app\api\controller\minimax;

use app\api\controller\BaseApiController;
use app\api\lists\minimax\VoiceLists;
use app\api\logic\minimax\VoiceLogic;
use think\exception\HttpResponseException;
use think\facade\Cache;
use think\facade\Log;
use think\response\Json;
use app\common\service\CallbackTokenService;


class VoiceController extends BaseApiController
{
    public array $notNeedLogin = ['notify', 'asrnotify'];

    public function lists()
    {
        return $this->dataLists(new VoiceLists());
    }

    public function upload()
    {
        try {
            $params = $this->request->post();
            $result = VoiceLogic::upload($params);
            if ($result) {
                return $this->data(VoiceLogic::getReturnData());
            }
            return $this->fail(VoiceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function add()
    {
        try {
            $params = $this->request->post();
            $result = VoiceLogic::add($params);
            if ($result) {
                return $this->data(VoiceLogic::getReturnData());
            }
            return $this->fail(VoiceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function audio()
    {
        try {
            $params = $this->request->post();
            $result = VoiceLogic::audiosCreate($params);
            if ($result) {
                return $this->data(VoiceLogic::getReturnData());
            }
            return $this->fail(VoiceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * Minimax TTS 后闪剪 ASR 回调：用原始文字覆盖 ASR 错别字，保留逐字时间戳
     */
    public function asrnotify(): Json
    {
        try {
            $data = $this->request->all();
            // 回调原始数据完整落日志（voiceAsr 通道）
            Log::channel('voiceAsr')->write('接收闪剪ASR回调原始数据' . json_encode(CallbackTokenService::maskForLog($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (!CallbackTokenService::guard('voiceAsr', '闪剪ASR', (string)$this->request->get('token', ''))) {
                return $this->fail('unauthorized');
            }
            // 令牌只用于鉴权，不能跟着 $data 流进业务层落库或写日志
            unset($data['token']);

            $key = md5(json_encode($data));
            if (Cache::store('redis')->get($key)) {
                echo 1;
                die;
            }
            Cache::store('redis')->set($key, 1, 20);

            $result = VoiceLogic::handleAsrNotify($data);
            if (!$result) {
                Log::channel('voiceAsr')->write('闪剪ASR回调处理失败' . json_encode([
                    'error' => VoiceLogic::getError(),
                    'raw'   => CallbackTokenService::maskForLog($data),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                return $this->fail(VoiceLogic::getError());
            }
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('voiceAsr')->write('闪剪ASR回调异常' . json_encode([
                'message' => $e->getMessage(),
                'raw'     => CallbackTokenService::maskForLog($this->request->all()),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return $this->fail('fail');
        }
    }

}