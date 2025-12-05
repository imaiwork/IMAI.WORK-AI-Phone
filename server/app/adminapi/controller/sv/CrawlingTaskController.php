<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\CrawlingTaskLists;

class CrawlingTaskController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 16:40:09
     */
    public function lists()
    {  
       return $this->dataLists(new CrawlingTaskLists());
    }
}