<?php


namespace app\adminapi\controller\setting\system;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\setting\system\LogLists;

/**
 * 系统日志
 * Class LogController
 * @package app\adminapi\controller\setting\system
 */
class LogController extends BaseAdminController
{
    /**
     * @notes 查看系统日志列表
     * @return \think\response\Json
     * @author ljj
     * @date 2021/8/3 4:25 下午
     */
    public function lists()
    {
        return $this->dataLists(new LogLists());
    }
}
