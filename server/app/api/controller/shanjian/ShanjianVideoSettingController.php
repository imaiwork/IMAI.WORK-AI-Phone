<?php

namespace app\api\controller\shanjian;

use app\api\controller\BaseApiController;
use app\api\logic\shanjian\ShanjianVideoSettingLogic;
use app\api\validate\shanjian\ShanjianVideoSettingValidate;
use app\api\lists\shanjian\ShanjianVideoSettingLists;
use think\exception\HttpResponseException;

/**
 * 闪剪视频设置控制器
 * Class ShanjianVideoSettingController
 * @package app\api\controller\shanjian
 */
class ShanjianVideoSettingController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * 获取视频设置列表
     */
    public function lists()
    {
        return $this->dataLists(new ShanjianVideoSettingLists());
    }

    /**
     * 添加视频设置
     */
    public function add()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->post()->goCheck('add');

            $result = ShanjianVideoSettingLogic::add($params);

            if ($result) {
                return $this->data(ShanjianVideoSettingLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 获取视频设置详情
     */
    public function detail()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->get()->goCheck('detail');
            $result = ShanjianVideoSettingLogic::detail($params['id']);
            if ($result) {
                return $this->data(ShanjianVideoSettingLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 更新视频设置
     */
    public function update()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->post()->goCheck('update');
            $result = ShanjianVideoSettingLogic::update($params);
            if ($result) {
                return $this->data(ShanjianVideoSettingLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function updateName()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->post()->goCheck('updateName');
            $result = ShanjianVideoSettingLogic::updateName($params);
            if ($result) {
                return $this->data(ShanjianVideoSettingLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 删除视频设置
     */
    public function delete()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->post()->goCheck('delete');
            $result = ShanjianVideoSettingLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
