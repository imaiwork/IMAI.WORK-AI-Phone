<?php

namespace app\api\controller\hd;

use app\api\controller\BaseApiController;
use think\exception\HttpResponseException;
use app\api\logic\hd\DoubaoLogic;
use app\api\validate\hd\DoubaoValidate;
use think\response\Json;

class DoubaoController extends BaseApiController
{
    public array $notNeedLogin = [];
    /**
     * @desc 文生视频
     */
    public function txt2video()
    {
        try {
            $params = (new DoubaoValidate())->post()->goCheck('txt2video');
            $result = DoubaoLogic::text2video($params);
            if ($result) {
                return $this->data(DoubaoLogic::getReturnData());
            }
            return $this->fail(DoubaoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 图生视频
     */
    public function img2video()
    {
        try {
            $params = (new DoubaoValidate())->post()->goCheck('img2video');
            $result = DoubaoLogic::image2video($params);
            if ($result) {
                return $this->data(DoubaoLogic::getReturnData());
            }
            return $this->fail(DoubaoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 图生视频
     */
    public function getTaskStatus()
    {
        try {
            $params = (new DoubaoValidate())->post()->goCheck('detail');
            $result = DoubaoLogic::getTaskStatus($params);
            if ($result) {
                return $this->data(DoubaoLogic::getReturnData());
            }
            return $this->fail(DoubaoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 图生视频
     */
    public function videoDelete()
    {
        try {
            $params = (new DoubaoValidate())->post()->goCheck('detail');
            $result = DoubaoLogic::videoDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(DoubaoLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
