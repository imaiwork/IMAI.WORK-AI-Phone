<?php


namespace app\adminapi\controller\setting\system;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\setting\system\CacheLogic;

/**
 * 系统缓存
 * Class CacheController
 * @package app\adminapi\controller\setting\system
 */
class CacheController extends BaseAdminController
{

    /**
     * @notes 清除系统缓存
     * @return \think\response\Json
     * @author 段誉
     * @date 2022/4/8 16:34
     */
    public function clear()
    {
        CacheLogic::clear();
        return $this->success('清除成功', [], 1, 1);
    }
}
