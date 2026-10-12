<?php

namespace app\adminapi\controller\copywriting;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\copywriting\PublicCopyLists;
use app\adminapi\logic\copywriting\PublicCopyLogic;
use app\adminapi\validate\copywriting\PublicCopyValidate;

class PublicCopyController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new PublicCopyLists());
    }

    public function add()
    {
        $params = (new PublicCopyValidate())->post()->goCheck('add');
        if (!PublicCopyLogic::add($params)) {
            return $this->fail(PublicCopyLogic::getError());
        }
        return $this->success('已保存', PublicCopyLogic::getReturnData());
    }

    public function update()
    {
        $params = (new PublicCopyValidate())->post()->goCheck('update');
        if (!PublicCopyLogic::update($params)) {
            return $this->fail(PublicCopyLogic::getError());
        }
        return $this->success('已保存', PublicCopyLogic::getReturnData());
    }

    public function delete()
    {
        $params = (new PublicCopyValidate())->post()->goCheck('id');
        if (!PublicCopyLogic::delete((int)$params['id'])) {
            return $this->fail(PublicCopyLogic::getError());
        }
        return $this->success('已删除');
    }
}
