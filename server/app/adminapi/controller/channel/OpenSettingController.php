<?php

namespace app\adminapi\controller\channel;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\channel\OpenSettingLogic;
use app\adminapi\validate\channel\OpenSettingValidate;

/**
 * 微信开放平台
 * Class AppSettingController
 * @package app\adminapi\controller\setting\app
 */
class OpenSettingController extends BaseAdminController
{

    /**
     * @notes 获取微信开放平台设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 11:03
     */
    public function getConfig()
    {
        $result = OpenSettingLogic::getConfig();
        return $this->data($result);
    }


    /**
     * @notes 微信开放平台设置
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/3/29 11:03
     */
    public function setConfig()
    {
        $params = (new OpenSettingValidate())->post()->goCheck();
        OpenSettingLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }
}
