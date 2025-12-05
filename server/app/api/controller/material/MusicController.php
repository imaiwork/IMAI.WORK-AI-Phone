<?php

namespace app\api\controller\material;

use app\api\controller\BaseApiController;
use app\api\lists\material\MusicLists;
use app\api\logic\material\MusicLogic;
use app\api\validate\material\MusicValidate;
use think\exception\HttpResponseException;

class MusicController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new MusicValidate())->post()->goCheck('add');
            $result = MusicLogic::addMusic($params);
            if ($result) {
                return $this->success(data: MusicLogic::getReturnData());
            }
            return $this->fail(MusicLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new MusicValidate())->post()->goCheck('update');
            $result = MusicLogic::updateMusic($params);
            if ($result) {
                return $this->success(data: MusicLogic::getReturnData());
            }
            return $this->fail(MusicLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new MusicValidate())->post()->goCheck('delete');
            $result = MusicLogic::deleteMusic($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(MusicLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new MusicValidate())->get()->goCheck('detail');
            $result = MusicLogic::getMusic($params);
            if ($result) {
                return $this->data(MusicLogic::getReturnData());
            }
            return $this->fail(MusicLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new MusicLists());
    }
}
