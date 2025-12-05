<?php
namespace app\adminapi\controller\sv;
use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\sv\PublishLists;
use app\adminapi\validate\sv\PublishDetailValidate;
use app\adminapi\validate\sv\PublishValidate;
use app\adminapi\logic\sv\PublishLogic;
use app\adminapi\lists\sv\PublishDetailLists;
use think\exception\HttpResponseException;

class PublishController extends BaseAdminController
{
    /**
     * @notes 列表
     * @author Lee
     * @date 2025-05-14 09:40:09
     */
    public function lists()
    { 
        return $this->dataLists(new PublishLists());
    }

    /**
     * @desc 详情
     */
    public function detail()
    {
        try {
            $params = (new PublishValidate())->get()->goCheck('detail');
            $result = PublishLogic::detail($params);
            if ($result) {
                return $this->data(PublishLogic::getReturnData());
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    public function delete()
    {
        try {
            $params = (new PublishValidate())->post()->goCheck('delete');
            $result = PublishLogic::deleteSvPublishSettingAccount($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(PublishLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordLists(){

        try {
            $params = (new PublishDetailValidate())->get()->goCheck('lists');
            return $this->dataLists(new PublishDetailLists());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}