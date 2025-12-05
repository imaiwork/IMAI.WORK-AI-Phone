<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\lists\shanjian\ShanjianAnchorLists;
use app\api\logic\shanjian\ShanjianAnchorLogic;
use app\api\validate\shanjian\ShanjianAnchorValidate;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

class ShanjianAnchorController extends BaseApiController
{
    public array $notNeedLogin = ['anchornotify', 'voicenotify','info'];

    public function add()
    {
        try {
            $params = (new ShanjianAnchorValidate())->post()->goCheck('add');
            $result = ShanjianAnchorLogic::add($params);
            if ($result) {
                return $this->data(ShanjianAnchorLogic::getReturnData());
            }
            return $this->fail(ShanjianAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = ShanjianAnchorLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(ShanjianAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new ShanjianAnchorValidate())->get()->goCheck('detail');
            $result = ShanjianAnchorLogic::detail($params);
            if ($result) {
                return $this->data(ShanjianAnchorLogic::getReturnData());
            }
            return $this->fail(ShanjianAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function lists()
    {
        return $this->dataLists(new ShanjianAnchorLists());
    }


    public function anchornotify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('shanjian')->write('接收闪剪形象合成参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val) {
                echo 1;
                die;
            }
            cache($key, 1, 20);
            $result = ShanjianAnchorLogic::updateAnchor($data);
            if (!$result) {
                return $this->fail(ShanjianAnchorLogic::getError());
            }
//
//            Log::channel('shanjian')->write('闪剪回调处理成功');
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('shanjian')->write('闪剪形象回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }

    public function voicenotify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('shanjian')->write('接收闪剪音色合成参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val) {
                echo 1;
                die;
            }
            cache($key, 1, 20);

            $result = ShanjianAnchorLogic::updateVoice($data);
            if (!$result) {
                return $this->fail(ShanjianAnchorLogic::getError());
            }
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('shanjian')->write('闪剪音色回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }

    public function authorizedList()
    {
        try {
            $data = $this->request->get();
            $result = ShanjianAnchorLogic::authorizedList($data);
            if ($result) {
                return $this->data($result);
            }
            return $this->fail(ShanjianAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}


