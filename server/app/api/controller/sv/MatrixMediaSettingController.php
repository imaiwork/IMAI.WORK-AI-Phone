<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvMatrixMediaSettingLogic;
use app\api\validate\sv\SvMatrixMediaSettingValidate;
use app\api\lists\sv\SvMatrixMediaSettingLists;
use think\exception\HttpResponseException;

/**
 * 矩阵媒体设置控制器
 * Class SvMatrixMediaSettingController
 * @package app\api\controller\sv
 */
class MatrixMediaSettingController extends BaseApiController
{
    public array $notNeedLogin = [];

    /**
     * 获取矩阵媒体设置列表
     */
    public function lists()
    {
        return $this->dataLists(new SvMatrixMediaSettingLists());
    }

    /**
     * 添加矩阵媒体设置
     */
    public function add()
    {
        try {
            $params = (new SvMatrixMediaSettingValidate())->post()->goCheck('add');
            
            $result = SvMatrixMediaSettingLogic::add($params);
            
            if ($result) {
                return $this->data(SvMatrixMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMatrixMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 获取矩阵媒体设置详情
     */
    public function detail()
    {
        try {
            $params = (new SvMatrixMediaSettingValidate())->get()->goCheck('detail');
            $result = SvMatrixMediaSettingLogic::detail($params['id']);
            if ($result) {
                return $this->data(SvMatrixMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMatrixMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 更新矩阵媒体设置
     */
    public function update()
    {
        try {
            $params = (new SvMatrixMediaSettingValidate())->post()->goCheck('update');
            $result = SvMatrixMediaSettingLogic::update($params);
            if ($result) {
                return $this->data(SvMatrixMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMatrixMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 删除矩阵媒体设置
     */
    public function delete()
    {
        try {
            $params = (new SvMatrixMediaSettingValidate())->post()->goCheck('delete');
            $result = SvMatrixMediaSettingLogic::delete($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMatrixMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
