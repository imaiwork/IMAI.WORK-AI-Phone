<?php

namespace app\adminapi\controller\sv;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\sv\SvVideoTaskLogic;
use app\adminapi\validate\sv\SvVideoTaskValidate;
use app\adminapi\lists\sv\SvVideoTaskLists;

/**
 * VideoSettingController
 */
class VideoTaskController extends BaseAdminController
{

    public array $notNeedLogin = ['notify'];

    public function lists()
    {
        return $this->dataLists(new SvVideoTaskLists());
    }


    public function delete()
    {
        try {
            $params = (new SvVideoTaskValidate())->post()->goCheck('delete');
            $result = SvVideoTaskLogic::deleteSvVideoTask($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvVideoTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }




}