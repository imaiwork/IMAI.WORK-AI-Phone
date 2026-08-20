<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\DeviceViralRecordLists;
use app\api\logic\sv\DeviceViralRecordLogic;
use app\api\validate\sv\DeviceViralRecordValidate;
use think\exception\HttpResponseException;

/**
 * 爆款库记录
 */
class DeviceViralRecordController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * @desc 爆款库列表
     */
    public function lists()
    {
        try {
            (new DeviceViralRecordValidate())->get()->goCheck('lists');
            return $this->dataLists(new DeviceViralRecordLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 手动导入分享链接（排队，每日00:00-03:00解析）
     */
    public function manualImport()
    {
        try {
            $params = (new DeviceViralRecordValidate())->post()->goCheck('manualImport');
            $result = DeviceViralRecordLogic::manualImport($params);
            if ($result) {
                return $this->success('已加入排队', DeviceViralRecordLogic::getReturnData());
            }
            return $this->fail(DeviceViralRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 标记/撤回不感兴趣
     */
    public function interest()
    {
        try {
            $params = (new DeviceViralRecordValidate())->post()->goCheck('interest');
            $result = DeviceViralRecordLogic::interest($params);
            if ($result) {
                return $this->success(data: DeviceViralRecordLogic::getReturnData());
            }
            return $this->fail(DeviceViralRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 清空不感兴趣列表
     */
    public function clearUninterested()
    {
        try {
            $params = (new DeviceViralRecordValidate())->post()->goCheck('clearUninterested');
            $result = DeviceViralRecordLogic::clearUninterested($params);
            if ($result) {
                return $this->success(data: DeviceViralRecordLogic::getReturnData());
            }
            return $this->fail(DeviceViralRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 保存AI仿写文案
     */
    public function saveCopywriting()
    {
        try {
            $params = (new DeviceViralRecordValidate())->post()->goCheck('saveCopywriting');
            $result = DeviceViralRecordLogic::saveCopywriting($params);
            if ($result) {
                return $this->success(data: DeviceViralRecordLogic::getReturnData());
            }
            return $this->fail(DeviceViralRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
