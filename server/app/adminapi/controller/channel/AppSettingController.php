<?php

namespace app\adminapi\controller\channel;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\channel\AppSettingLogic;

/**
 * APP设置控制器
 * Class AppSettingController
 * @package app\adminapi\controller\setting\app
 */
class AppSettingController extends BaseAdminController
{

    /**
     * @notes 获取App设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:24
     */
    public function getConfig()
    {
        $result = AppSettingLogic::getConfig();
        return $this->data($result);
    }


    /**
     * @notes App设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:25
     */
    public function setConfig()
    {
        $params = $this->request->post();
        AppSettingLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
