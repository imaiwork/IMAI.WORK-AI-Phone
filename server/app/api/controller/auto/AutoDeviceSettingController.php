<?php

namespace app\api\controller\auto;

use app\api\controller\BaseApiController;
use app\api\logic\auto\AutoDeviceSettingLogic;
use app\api\validate\auto\AutoDeviceSettingValidate;
use app\api\lists\auto\AutoDeviceSettingLists;
use think\exception\HttpResponseException;

/**
 * AutoDeviceSettingController
 */
class AutoDeviceSettingController extends BaseApiController
{
    /**
     * 获取自动设备设置列表
     */
    public function lists()
    {
        return $this->dataLists(new AutoDeviceSettingLists());
    }

    /**
     * 新增自动设备设置
     */
    public function add()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceSettingValidate();
            if (!$validate->scene('add')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceSettingLogic::addAutoDeviceSetting($params);
                if ($result) {
                return $this->success('添加成功', AutoDeviceSettingLogic::getReturnData());
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 更新自动设备设置
     */
    public function update()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceSettingValidate();
            if (!$validate->scene('update')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceSettingLogic::updateAutoDeviceSetting($params);
            if ($result) {
                return $this->success('更新成功', AutoDeviceSettingLogic::getReturnData());
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 删除自动设备设置
     */
    public function delete()
    {
        try {
            $id = $this->request->param('id/d', 0);
            if (!$id) {
                return $this->fail('参数错误');
            }
            $result = AutoDeviceSettingLogic::deleteAutoDeviceSetting($id);
            if ($result) {
                return $this->success('删除成功');
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取自动设备设置详情
     */
    public function detail()
    {
        try {
            $params = $this->request->param();
            $validate = new AutoDeviceSettingValidate();
            if (!$validate->scene('detail')->check($params)) {
                return $this->fail($validate->getError());
            }
            $result = AutoDeviceSettingLogic::getAutoDeviceSettingDetail($params);
            if ($result) {
                return $this->success('获取成功', AutoDeviceSettingLogic::getReturnData());
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 获取自动设备设置列表
     */
    public function autoDeviceSetting()
    {
        try {
            $result = AutoDeviceSettingLogic::autoDeviceSetting();
            if ($result) {
                return $this->success('获取成功', AutoDeviceSettingLogic::getReturnData());
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 处理human_image数据
     */
    public function processHumanImageData()
    {
        try {
            $result = AutoDeviceSettingLogic::processHumanImageData();
            if ($result) {
                return $this->success('处理成功', AutoDeviceSettingLogic::getReturnData());
            }
            return $this->fail(AutoDeviceSettingLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}