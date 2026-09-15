<?php

namespace app\api\controller\workData;

use app\api\controller\BaseApiController;
use app\api\logic\workData\WorkDataLogic;
use app\api\validate\workData\WorkDataValidate;

class WorkDataController extends BaseApiController
{
    public function personas()
    {
        (new WorkDataValidate())->get();
        $result = WorkDataLogic::personas((int)$this->userId);
        if (false === $result) {
            return $this->fail(WorkDataLogic::getError());
        }
        return $this->data($result);
    }

    public function analytics()
    {
        $params = (new WorkDataValidate())->get()->goCheck('analytics');
        $result = WorkDataLogic::analytics($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(WorkDataLogic::getError());
        }
        if (($result['bound'] ?? true) === false) {
            return $this->success((string)($result['msg'] ?? '未绑定账号'), [
                'bound' => false,
                'platform' => (string)($result['platform'] ?? ''),
            ]);
        }
        return $this->data($result);
    }

    public function syncSettings()
    {
        $params = (new WorkDataValidate())->post()->goCheck('syncSettings');
        $result = WorkDataLogic::syncSettings($params, (int)$this->userId);
        if (false === $result) {
            return $this->fail(WorkDataLogic::getError());
        }
        return $this->data($result);
    }
}
