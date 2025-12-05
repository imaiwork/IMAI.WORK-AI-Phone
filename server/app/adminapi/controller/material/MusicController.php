<?php

namespace app\adminapi\controller\material;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\material\MusicLists;
use app\adminapi\logic\material\MusicLogic;
use app\adminapi\validate\material\MusicValidate;
use think\exception\HttpResponseException;

class MusicController extends BaseAdminController
{
    public function add()
    {
        try {
            $params = (new MusicValidate())->post()->goCheck('add');
            $params['source_id'] = $this->adminId;
            $result = MusicLogic::addMusic($params);
            if ($result) {
                return $this->success('添加成功');
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
