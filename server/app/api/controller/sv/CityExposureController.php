<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\CityExposureRecordLists;
use app\api\lists\sv\CityExposureTaskLists;
use app\api\logic\sv\CityExposureLogic;
use app\api\validate\sv\CityExposureTaskValidate;

/**
 * CityExposureController
 * @desc 同城曝光任务
 */
class CityExposureController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 子任务列表
     */
    public function lists()
    {
        return $this->dataLists(new CityExposureTaskLists());
    }

    /**
     * @desc 执行记录列表
     */
    public function recordLists()
    {
        return $this->dataLists(new CityExposureRecordLists());
    }

    /**
     * @desc 添加同城曝光任务
     */
    public function add()
    {
        $params = (new CityExposureTaskValidate())->post()->goCheck('add');
        $result = CityExposureLogic::add($params);
        if ($result) {
            return $this->success(data: CityExposureLogic::getReturnData());
        }
        return $this->fail(CityExposureLogic::getError());
    }

    /**
     * @desc 编辑同城曝光任务
     */
    public function edit()
    {
        $params = (new CityExposureTaskValidate())->post()->goCheck('edit');
        $result = CityExposureLogic::edit($params);
        if ($result) {
            return $this->success(data: CityExposureLogic::getReturnData());
        }
        return $this->fail(CityExposureLogic::getError());
    }

    /**
     * @desc 删除任务
     */
    public function delete()
    {
        $params = (new CityExposureTaskValidate())->post()->goCheck('delete');
        $result = CityExposureLogic::delete($params);
        if ($result) {
            return $this->success();
        }
        return $this->fail(CityExposureLogic::getError());
    }

    /**
     * @desc 更新子任务状态（暂停 / 恢复）
     */
    public function updateStatus()
    {
        $params = (new CityExposureTaskValidate())->post()->goCheck('updateStatus');
        $result = CityExposureLogic::updateStatus($params);
        if ($result) {
            return $this->success();
        }
        return $this->fail(CityExposureLogic::getError());
    }

    /**
     * @desc 任务详情
     */
    public function detail()
    {
        $params = (new CityExposureTaskValidate())->get()->goCheck('detail');
        $result = CityExposureLogic::detail($params);
        if ($result !== false) {
            return $this->success(data: $result);
        }
        return $this->fail(CityExposureLogic::getError());
    }
}