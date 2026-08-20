<?php

namespace app\api\controller;

use app\api\logic\TeamLogic;

/**
 * 租户解析 - 按域名/小程序appid返回团队品牌配置(无需登录)
 * Class TenantController
 * @package app\api\controller
 */
class TenantController extends BaseApiController
{
    public array $notNeedLogin = ['resolve'];

    /**
     * @notes 解析当前租户品牌(前端启动时调用做换肤/品牌)
     */
    public function resolve()
    {
        $domain = (string)$this->request->param('domain', '');
        $appid = (string)$this->request->param('appid', '');
        return $this->data(TeamLogic::resolveTenant($domain, $appid));
    }
}
