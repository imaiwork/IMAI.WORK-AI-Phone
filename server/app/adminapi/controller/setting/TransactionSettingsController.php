<?php


namespace app\adminapi\controller\setting;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\TransactionSettingsLogic;
use app\adminapi\validate\setting\TransactionSettingsValidate;

/**
 * 交易设置
 * Class TransactionSettingsController
 * @package app\adminapi\controller\setting
 */
class TransactionSettingsController extends BaseAdminController
{
    /**
     * @notes 获取交易设置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 11:40 上午
     */
    public function getConfig()
    {
        $result = TransactionSettingsLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置交易设置
     * @return \think\response\Json
     * @author ljj
     * @date 2022/2/15 11:50 上午
     */
    public function setConfig()
    {
        $params = (new TransactionSettingsValidate())->post()->goCheck('setConfig');
        TransactionSettingsLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
