<?php

namespace app\adminapi\controller\cardcode;
use app\adminapi\controller\BaseAdminController;

/**
 * 卡密记录控制器类
 * Class CardCodeController
 * @package app\adminapi\controller\cardcode
 */
class CardCodeRecordController extends BaseAdminController
{

    /**
     * @notes 列表类
     * @return mixed
     * @author kb
     * @date 2023/7/10 12:09
     */
    public function lists()
    {
        return $this->dataLists();
    }



}