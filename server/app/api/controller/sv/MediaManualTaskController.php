<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\validate\sv\SvMediaManualTaskValidate;
use app\api\logic\sv\SvMediaManualTaskLogic;
use app\api\lists\sv\SvMediaManualTaskLists;
use think\exception\HttpResponseException;

/**
 * MediaManualTaskController
 * @desc 媒体手动任务控制器
 */
class MediaManualTaskController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 获取媒体手动任务列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new SvMediaManualTaskLists());
    }

    /**
     * @desc 添加媒体手动任务
     * @return \think\response\Json
     */
    public function add()
    {
        try {
            $params = (new SvMediaManualTaskValidate())->post()->goCheck('add');
            $result = SvMediaManualTaskLogic::addSvMediaManualTask($params);
            if ($result) {
                return $this->data(SvMediaManualTaskLogic::getReturnData());
            }
            return $this->fail(SvMediaManualTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 获取媒体手动任务详情
     * @return \think\response\Json
     */
    public function detail()
    {
        try {
            $params = (new SvMediaManualTaskValidate())->get()->goCheck('detail');
            $result = SvMediaManualTaskLogic::detailSvMediaManualTask($params);
            if ($result) {
                return $this->data(SvMediaManualTaskLogic::getReturnData());
            }
            return $this->fail(SvMediaManualTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新媒体手动任务
     * @return \think\response\Json
     */
    public function update()
    {
        try {
            $params = (new SvMediaManualTaskValidate())->post()->goCheck('update');
            $result = SvMediaManualTaskLogic::updateSvMediaManualTask($params);
            if ($result) {
                return $this->data(SvMediaManualTaskLogic::getReturnData());
            }
            return $this->fail(SvMediaManualTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除媒体手动任务
     * @return \think\response\Json
     */
    public function delete()
    {
        try {
            $params = (new SvMediaManualTaskValidate())->post()->goCheck('delete');
            $result = SvMediaManualTaskLogic::deleteSvMediaManualTask($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMediaManualTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * @desc 批量更新任务状态
     * @return \think\response\Json
     */
    public function publish()
    {
        try {
            $params = $this->request->post();
            $result = SvMediaManualTaskLogic::publish($params);
            if ($result) {
                return $this->data(SvMediaManualTaskLogic::getReturnData());
            }
            return $this->fail(SvMediaManualTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}