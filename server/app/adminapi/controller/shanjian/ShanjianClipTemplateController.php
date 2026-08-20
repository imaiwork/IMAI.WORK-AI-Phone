<?php

namespace app\adminapi\controller\shanjian;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\shanjian\ShanjianClipTemplateLists;
use think\response\Json;

/**
 * 闪剪视频模板（人设视频剪辑等后台选择器用）
 */
class ShanjianClipTemplateController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function lists(): Json
    {
        return $this->dataLists(new ShanjianClipTemplateLists());
    }
}
