<?php

namespace app\api\controller\hd;

use app\api\controller\BaseApiController;
use app\api\lists\hd\HdPuzzleSettingLists;
use app\api\logic\hd\HdPuzzleSettingLogic;
use app\api\validate\hd\HdPuzzleSettingValidate;
use think\exception\HttpResponseException;

/**
 * PuzzleSettingController
 */
class PuzzleSettingController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new HdPuzzleSettingLists());
    }

    public function add()
    {
        try {
            $params = (new HdPuzzleSettingValidate())->post()->goCheck('add');
            $result = HdPuzzleSettingLogic::addPuzzleSetting($params);
            if ($result) {
                return $this->data(HdPuzzleSettingLogic::getReturnData());
            }
            return $this->fail(HdPuzzleSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new HdPuzzleSettingValidate())->get()->goCheck('detail');
            $result = HdPuzzleSettingLogic::detailPuzzleSetting($params);
            if ($result) {
                return $this->data(HdPuzzleSettingLogic::getReturnData());
            }
            return $this->fail(HdPuzzleSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new HdPuzzleSettingValidate())->post()->goCheck('delete');
            $result = HdPuzzleSettingLogic::deletePuzzleSetting($params['id']);
            if ($result) {
                return $this->success();
            }
            return $this->fail(HdPuzzleSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新
     */
    public function update()
    {
        try {
            $params = (new HdPuzzleSettingValidate())->post()->goCheck('update');
            $result = HdPuzzleSettingLogic::updatePuzzleSetting($params);
            if ($result) {
                return $this->data(HdPuzzleSettingLogic::getReturnData());
            }
            return $this->fail(HdPuzzleSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}

