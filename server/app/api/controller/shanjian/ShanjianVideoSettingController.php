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

            $params['shanjian_type'] =  $params['shanjian_type'] ?? 1;

            switch ( $params['shanjian_type']){
                case 1://数字人口播
                    $result = ShanjianVideoSettingLogic::add($params);
                    break;
                case 2://真人口播
                    $result = ShanjianVideoSettingLogic::addType2($params);
                    break;
                case 3://素材
                    $result = ShanjianVideoSettingLogic::addType3($params);
                    break;
               case 4://新闻体
                    $result = ShanjianVideoSettingLogic::addType4($params);
                    break;
                case 5://数字人口播无包装
                    $result = ShanjianVideoSettingLogic::addType5($params);
                    break;
                default:
                    return $this->fail('不支持的壹传媒类型');
            }

            if ($result) {
                return $this->data(ShanjianVideoSettingLogic::getReturnData());
            }
            return $this->fail(ShanjianVideoSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 新增数字人口播视频(无包装, shanjian_type=5)
     * 独立创建入口: AI智剪关闭时 type=5 即最终视频; 开启时成功后自动派生 type=2 包装
     */
    public function addType5()
    {
        try {
            $params = (new ShanjianVideoSettingValidate())->post()->goCheck('add');
            $result = ShanjianVideoSettingLogic::addType5($params);
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
