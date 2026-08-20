<?php

namespace app\adminapi\controller\aiPersona;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\aiPersona\SynthesisConfigLogic;
use Exception;
use think\response\Json;

class SynthesisConfigController extends BaseAdminController
{
    public array $notNeedLogin = [];

    public function update(): Json
    {
        try {
            $params = $this->request->post();
            $result = SynthesisConfigLogic::update($params);
            if ($result) {
                return $this->success(data: SynthesisConfigLogic::getReturnData());
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getByPersonaId(): Json
    {
        try {
            $params = $this->request->get();
            $result = SynthesisConfigLogic::getByPersonaId($params);
            if ($result) {
                return $this->data(SynthesisConfigLogic::getReturnData());
            }
            return $this->fail(SynthesisConfigLogic::getError());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
