<?php

namespace app\api\controller\copywriting;

use app\api\controller\BaseApiController;
use app\api\logic\copywriting\PublicCopyLogic;
use app\api\validate\copywriting\PublicCopyValidate;

class PublicCopyController extends BaseApiController
{
    public function lists()
    {
        $params = (new PublicCopyValidate())->get()->goCheck('browse');
        if (!PublicCopyLogic::browse($params)) {
            return $this->fail(PublicCopyLogic::getError());
        }
        return $this->data(PublicCopyLogic::getReturnData());
    }

    public function add()
    {
        $params = (new PublicCopyValidate())->post()->goCheck('add');
        if (!PublicCopyLogic::add($params)) {
            return $this->fail(PublicCopyLogic::getError());
        }
        $data = PublicCopyLogic::getReturnData();
        return $this->success($data['message'] ?? '已加入', $data);
    }

    public function refresh()
    {
        $params = (new PublicCopyValidate())->post()->goCheck('refresh');
        if (!PublicCopyLogic::refresh($params)) {
            $data = PublicCopyLogic::getReturnData();
            if (is_array($data) && !empty($data['need_confirm'])) {
                return $this->fail(PublicCopyLogic::getError(), $data, 0, 0);
            }
            return $this->fail(PublicCopyLogic::getError());
        }
        return $this->success('已更新', PublicCopyLogic::getReturnData());
    }
}
