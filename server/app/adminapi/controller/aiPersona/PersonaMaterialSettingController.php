<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\aiPersona\PersonaMaterialSettingLogic;
use app\adminapi\validate\aiPersona\PersonaMaterialSettingValidate;

/**
 * IP人设设置（人设素材库一键清理开关与次数阈值）
 * Class PersonaMaterialSettingController
 * @package app\adminapi\controller\aiPersona
 */
class PersonaMaterialSettingController extends BaseAdminController
{
    /**
     * @notes 获取人设设置
     * @return \think\response\Json
     */
    public function getConfig()
    {
        $result = PersonaMaterialSettingLogic::getConfig();
        return $this->data($result);
    }

    /**
     * @notes 设置人设设置
     * @return \think\response\Json
     */
    public function setConfig()
    {
        $params = (new PersonaMaterialSettingValidate())->post()->goCheck('setConfig');
        PersonaMaterialSettingLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
