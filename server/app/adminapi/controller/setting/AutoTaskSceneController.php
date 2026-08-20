<?php

namespace app\adminapi\controller\setting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\AutoTaskSceneLogic;
use app\adminapi\validate\setting\AutoTaskSceneValidate;
use think\response\Json;

/**
 * 自动任务场景配置控制器
 * Class AutoTaskSceneController
 * @package app\adminapi\controller\setting
 */
class AutoTaskSceneController extends BaseAdminController
{
    /**
     * @notes 获取自动任务场景配置
     * @return Json
     */
    public function getConfig(): Json
    {
        $config = AutoTaskSceneLogic::getConfig();
        return $this->data($config);
    }

    /**
     * @notes 保存自动任务场景配置
     * @return Json
     */
    public function setConfig(): Json
    {
        $params = (new AutoTaskSceneValidate())->post()->goCheck('set');
        $result = AutoTaskSceneLogic::setConfig($params);
        if (true === $result) {
            return $this->success('保存成功', [], 1, 1);
        }
        return $this->fail(AutoTaskSceneLogic::getError());
    }
}
