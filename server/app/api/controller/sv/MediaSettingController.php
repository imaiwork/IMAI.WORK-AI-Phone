<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvMediaSettingLogic;
use app\api\validate\sv\SvMediaSettingValidate;
use app\api\lists\sv\SvMediaSettingLists;
use think\exception\HttpResponseException;

/**
 * MediaSettingController
 */
class MediaSettingController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new SvMediaSettingLists());
    }

    public function add()
    {
        try {
            $params = (new SvMediaSettingValidate())->post()->goCheck('add');
            $result = SvMediaSettingLogic::addSvMediaSetting($params);
            if ($result) {
                return $this->data(SvMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new SvMediaSettingValidate())->get()->goCheck('detail');
            $result = SvMediaSettingLogic::detailSvMediaSetting($params);
            if ($result) {
                return $this->data(SvMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvMediaSettingValidate())->post()->goCheck('delete');
            $result = SvMediaSettingLogic::deleteSvMediaSetting($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 平台
     */
    public function update()
    {
        try {
            $params = (new SvMediaSettingValidate())->post()->goCheck('update');
            $result = SvMediaSettingLogic::updateSvMediaSetting($params);
            if ($result) {
                return $this->data(SvMediaSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


}