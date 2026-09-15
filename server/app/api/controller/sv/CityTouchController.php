<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\CityTouchRecordLists;
use app\api\lists\sv\CityTouchTaskLists;
use app\api\logic\sv\CityTouchLogic;
use app\api\validate\sv\CityTouchRecordValidate;
use app\api\validate\sv\CityTouchTaskValidate;
use think\exception\HttpResponseException;

/**
 * CityTouchController
 * @desc 同城视频截流设置
 */
class CityTouchController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 获取任务列表
     */
    public function lists()
    {
        return $this->dataLists(new CityTouchTaskLists());
    }

    /**
     * @desc 添加同城视频截流任务
     */
    public function add()
    {
        try {
            $params = (new CityTouchTaskValidate())->post()->goCheck('add');
            $result = CityTouchLogic::add($params);
            if ($result) {
                return $this->success(data: CityTouchLogic::getReturnData());
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除任务
     */
    public function delete()
    {
        try {
            $params = (new CityTouchTaskValidate())->post()->goCheck('delete');
            $result = CityTouchLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 任务详情
     */
    public function detail()
    {
        try {
            $params = (new CityTouchTaskValidate())->get()->goCheck('detail');
            $result = CityTouchLogic::detail($params);
            if ($result) {
                return $this->data(CityTouchLogic::getReturnData());
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 获取执行记录列表
     */
    public function recordLists()
    {
        return $this->dataLists(new CityTouchRecordLists());
    }

    /**
     * @desc 获取执行记录详情
     */
    public function recordDetail()
    {
        try {
            $params = (new CityTouchRecordValidate())->get()->goCheck('detail');
            $result = CityTouchLogic::recordDetail($params);
            if ($result) {
                return $this->data(CityTouchLogic::getReturnData());
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 删除执行记录
     */
    public function recordDelete()
    {
        try {
            $params = (new CityTouchRecordValidate())->post()->goCheck('delete');
            $result = CityTouchLogic::recordDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 获取过滤词历史
     */
    public function getFilterHistory()
    {
        try {
            $result = CityTouchLogic::getFilterHistory([]);
            if ($result) {
                return $this->data(CityTouchLogic::getReturnData());
            }
            return $this->fail(CityTouchLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}