<?php

namespace app\adminapi\controller\square;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\square\AssetLists;
use app\adminapi\logic\square\SquareLogic;
use app\adminapi\validate\square\SquareValidate;

class AssetController extends BaseAdminController
{
    public function lists()
    {
        return $this->dataLists(new AssetLists());
    }

    public function add()
    {
        $params = (new SquareValidate())->post()->goCheck('assetAdd');
        if (!SquareLogic::addAsset($params)) {
            return $this->fail(SquareLogic::getError());
        }
        $data = SquareLogic::getReturnData();
        $created = is_array($data) && isset($data['created']) ? (int)$data['created'] : 1;
        return $this->success($created > 1 ? ('已入库 ' . $created . ' 个素材') : '已保存', $data);
    }

    public function update()
    {
        $params = (new SquareValidate())->post()->goCheck('assetUpdate');
        if (!SquareLogic::updateAsset($params)) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已保存', SquareLogic::getReturnData());
    }

    public function delete()
    {
        $params = (new SquareValidate())->post()->goCheck('id');
        if (!SquareLogic::deleteAsset((int)$params['id'])) {
            return $this->fail(SquareLogic::getError());
        }
        return $this->success('已删除');
    }
}