<?php

namespace app\adminapi\controller\channel;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\channel\WebPageSettingLogic;
use app\adminapi\validate\channel\WebPageSettingValidate;

/**
 * H5设置控制器
 * Class HFiveSettingController
 * @package app\adminapi\controller\setting\h5
 */
class WebPageSettingController extends BaseAdminController
{

    /**
     * @notes 获取H5设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:36
     */
    public function getConfig()
    {
        $result = WebPageSettingLogic::getConfig();
        return $this->data($result);
    }


    /**
     * @notes H5设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 10:36
     */
    public function setConfig()
    {
        $params = (new WebPageSettingValidate())->post()->goCheck();
        WebPageSettingLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
