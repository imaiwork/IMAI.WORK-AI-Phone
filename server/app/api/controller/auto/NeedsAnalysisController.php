<?php


namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use app\api\logic\auto\NeedsAnalysisLogic;
use app\api\validate\auto\NeedsAnalysisValidate;
use think\exception\HttpResponseException;

/**
 * NeedsAnalysisController
 * @desc 设备自动任务需求分析
 */
class NeedsAnalysisController extends BaseApiController
{

    public array $notNeedLogin = [];

    public function chat()
    {
        try {
            $params = (new NeedsAnalysisValidate())->post()->goCheck('chat');
            $result = NeedsAnalysisLogic::chat($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function retrieve()
    {
        try {
            $params = (new NeedsAnalysisValidate())->post()->goCheck('retrieve');
            $result = NeedsAnalysisLogic::chatRetrieve($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function analysis()
    {
        try {
            $params = (new NeedsAnalysisValidate())->post()->goCheck('analysis');
            $result = NeedsAnalysisLogic::analysis($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function report()
    {
        try {
            $params = $this->request->post();
            $result = NeedsAnalysisLogic::report($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 手动添加需求分析
     */
    public function add()
    {
        try {
            $params = $this->request->post();
            $result = NeedsAnalysisLogic::add($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = NeedsAnalysisLogic::detail($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
//            $params = (new NeedsAnalysisValidate())->post()->goCheck('update');
            $params = $this->request->post();
            $result = NeedsAnalysisLogic::update($params);
            if ($result) {
                return $this->success(data: NeedsAnalysisLogic::getReturnData());
            }
            return $this->fail(NeedsAnalysisLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
