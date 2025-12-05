<?php

declare(strict_types=1);

namespace app\adminapi\controller;

use think\App;
use app\common\controller\BaseCommonAdminController;

/**
 * 管理元控制器基类
 * Class BaseAdminController
 * @package app\adminapi\controller
 */
class BaseAdminController extends BaseCommonAdminController
{
    protected int $adminId = 0;
    protected array $adminInfo = [];

    public function initialize()
    {
        if (isset($this->request->adminInfo) && $this->request->adminInfo) {
            $this->adminInfo = $this->request->adminInfo;
            $this->adminId = $this->request->adminInfo['admin_id'];
        }
    }
}
