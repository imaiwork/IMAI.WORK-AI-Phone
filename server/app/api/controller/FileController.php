<?php

namespace app\api\controller;

use app\api\controller\BaseApiController;
use app\api\lists\material\FfmpegFileLists;
use app\api\logic\material\FfmpegFileLogic;
use app\api\validate\material\FfmpegFileValidate;
use think\exception\HttpResponseException;

class FileController extends BaseApiController
{
    public function videoTranscoding()
    {
        try {
            $params = $this->request->post();
            $params['type'] = $params['type'] ?? 20;
            $result = FfmpegFileLogic::addFfmpegFile($params);
            if ($result) {
                return $this->success(data: FfmpegFileLogic::getReturnData());
            }
            return $this->fail(FfmpegFileLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

  


    public function detail()
    {
        try {
            $params = $this->request->all();
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