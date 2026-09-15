<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\SvMediaManualSettingLogic;
use app\api\validate\sv\SvMediaManualSettingValidate;
use app\api\lists\sv\SvMediaManualSettingLists;
use think\exception\HttpResponseException;

/**
 * MediaManualSettingController
 */
class MediaManualSettingController extends BaseApiController
{
    public array $notNeedLogin = [];

    public function lists()
    {
        return $this->dataLists(new SvMediaManualSettingLists());
    }

    public function add()
    {
        try {
            $params = $this->request->post();
            $result = SvMediaManualSettingLogic::addSvMediaManualSetting($params);
            if ($result) {
                return $this->data(SvMediaManualSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaManualSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = $this->request->get();
            $result = SvMediaManualSettingLogic::detailSvMediaManualSetting($params);
            if ($result) {
                return $this->data(SvMediaManualSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaManualSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new SvMediaManualSettingValidate())->post()->goCheck('delete');
            $result = SvMediaManualSettingLogic::deleteSvMediaManualSetting($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(SvMediaManualSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 媒体手动设置更新
     */
    public function update()
    {
        try {
            $params = (new SvMediaManualSettingValidate())->post()->goCheck('update');
            $result = SvMediaManualSettingLogic::updateSvMediaManualSetting($params);
            if ($result) {
                return $this->data(SvMediaManualSettingLogic::getReturnData());
            }
            return $this->fail(SvMediaManualSettingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}