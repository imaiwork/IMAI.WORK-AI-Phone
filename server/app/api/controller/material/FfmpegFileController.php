<?php

namespace app\api\controller\material;

use app\api\controller\BaseApiController;
use app\api\lists\material\FfmpegFileLists;
use app\api\logic\material\FfmpegFileLogic;
use app\api\validate\material\FfmpegFileValidate;
use think\exception\HttpResponseException;

class FfmpegFileController extends BaseApiController
{
    public function add()
    {
        try {
            $params = (new FfmpegFileValidate())->post()->goCheck('add');
            $result = FfmpegFileLogic::addFfmpegFile($params);
            if ($result) {
                return $this->success(data: FfmpegFileLogic::getReturnData());
            }
            return $this->fail(FfmpegFileLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new FfmpegFileValidate())->post()->goCheck('update');
            $result = FfmpegFileLogic::updateFfmpegFile($params);
            if ($result) {
                return $this->success(data: FfmpegFileLogic::getReturnData());
            }
            return $this->fail(FfmpegFileLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new FfmpegFileValidate())->post()->goCheck('delete');
            $result = FfmpegFileLogic::deleteFfmpegFile($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(FfmpegFileLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new FfmpegFileValidate())->get()->goCheck('detail');
            $result = FfmpegFileLogic::getFfmpegFile($params);
            if ($result) {
                return $this->data(FfmpegFileLogic::getReturnData());
            }
            return $this->fail(FfmpegFileLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 列表
     */
    public function lists()
    {
        return $this->dataLists(new FfmpegFileLists());
    }
}