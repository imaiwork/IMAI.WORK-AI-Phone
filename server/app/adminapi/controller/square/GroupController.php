<?php

namespace app\adminapi\controller\square;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\square\SquareLogic;
use app\adminapi\validate\square\SquareValidate;
use app\common\service\square\SquareAssetService;

class GroupController extends BaseAdminController
{
    public function lists()
    {
        return $this->data([
            'groups' => SquareLogic::groupLists(),
            'asset_total' => SquareAssetService::countAllAssets(),
        ]);
    }

    public function add()
    {
        $params = (new SquareValidate())->post()->goCheck('groupAdd');
        if (!SquareLogic::addGroup($params)) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已保存', SquareLogic::getReturnData());
    }

    public function update()
    {
        $params = (new SquareValidate())->post()->goCheck('groupUpdate');
        if (!SquareLogic::updateGroup($params)) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已保存', SquareLogic::getReturnData());
    }

    public function delete()
    {
        $params = (new SquareValidate())->post()->goCheck('id');
        if (!SquareLogic::deleteGroup((int)$params['id'])) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已删除');
    }
}