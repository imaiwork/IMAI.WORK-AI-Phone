<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\VoiceLists;
use app\api\logic\shanjian\VoiceLogic;
use app\api\validate\shanjian\VoiceValidate;
use app\common\service\MemberService;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;


class VoiceController extends BaseApiController
{
    public array $notNeedLogin = ['notify'];

    public function lists()
    {
        return $this->dataLists(new VoiceLists());
    }

    public function add()
    {
        try {
            // 与蝉镜音色共用 max_voices 配额（human_voice 表）
            $existing = MemberService::countQuotaVoices($this->userId);
            $reason = '';
            if (!MemberService::canCreate($this->userId, 'voice', $existing, $reason)) {
                return $this->fail($reason . ',请升级会员');
            }
            $params = (new VoiceValidate())->post()->goCheck('add');
            $result = VoiceLogic::add($params);
            if ($result) {
                return $this->data(VoiceLogic::getReturnData());
            }
            return $this->fail(VoiceLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function notify()
    {
        try {
            $data = $this->request->all();
            Log::channel('shanjian')->write('接收闪剪音色合成参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $result = VoiceLogic::notify($data);
            if ($result) {
                return $this->data(VoiceLogic::getReturnData());
            }
            return $this->fail(VoiceLogic::getError());
        } catch (HttpResponseException $e) {
            Log::channel('shanjian')->write('闪剪音色回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }

    public function musicLists()
    {
        $result = VoiceLogic::musicLists();
        if ($result) {
            return $this->data(VoiceLogic::getReturnData());
        }
    }
}