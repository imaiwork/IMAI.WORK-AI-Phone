<?php

namespace app\api\controller\sora;

use app\api\controller\BaseApiController;
use app\api\lists\sora\SoraAnchorLists;
use app\api\lists\sora\SoraAnchorPublicLists;
use app\api\logic\sora\SoraAnchorLogic;
use think\exception\HttpResponseException;
use think\facade\Log;
use think\response\Json;

class SoraAnchorController extends BaseApiController
{
    public array $notNeedLogin = ['notify'];

    public function lists()
    {
        return $this->dataLists(new SoraAnchorLists());
    }

    public function publicLists()
    {
        return $this->dataLists(new SoraAnchorPublicLists());
    }

    public function add()
    {
        try {
            $params = $this->request->post();
            $result = SoraAnchorLogic::add($params);
            if ($result) {
                return $this->data(SoraAnchorLogic::getReturnData());
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = $this->request->post();
            $result = SoraAnchorLogic::update($params);
            if ($result) {
                return $this->data(SoraAnchorLogic::getReturnData());
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function delete()
    {
        try {
            $params = $this->request->post();
            $result = SoraAnchorLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = SoraAnchorLogic::detail($params);
            if ($result) {
                return $this->data(SoraAnchorLogic::getReturnData());
            }
            return $this->fail(SoraAnchorLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function notify(): Json
    {
        try {
            $data = $this->request->all();
            Log::channel('sora')->write('接收Sora形象合成参数'.json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $key = md5(json_encode($data));
            $val = cache($key);
            if ($val) {
                echo 1;
                die;
            }
            cache($key, 1, 20);
            $result = SoraAnchorLogic::updateAnchor($data);
            if (!$result) {
                return $this->fail(SoraAnchorLogic::getError());
            }
            return $this->success('ok');
        } catch (\Exception $e) {
            Log::channel('sora')->write('Sora形象回调失败'.$e->getMessage());
            return $this->fail('fail');
        }
    }

}


