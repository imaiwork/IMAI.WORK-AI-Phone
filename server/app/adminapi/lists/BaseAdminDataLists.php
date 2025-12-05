<?php



namespace app\adminapi\lists;


use app\common\lists\BaseDataLists;

/**
 * 管理员模块数据列表基类
 * Class BaseAdminDataLists
 * @package app\adminapi\lists
 */
abstract class BaseAdminDataLists extends BaseDataLists
{
    protected array $adminInfo;
    protected int $adminId;

    public function __construct()
    {
        parent::__construct();
        $this->adminInfo = $this->request->adminInfo;
        $this->adminId = $this->request->adminId;
    }
}
