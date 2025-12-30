<?php


namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\LeadScrapingLists;
use app\api\lists\sv\LeadScrapingRecordLists;
use app\api\logic\sv\LeadScrapingLogic;
use app\api\validate\sv\LeadScrapingRecordValidate;
use app\api\validate\sv\LeadScrapingValidate;
use think\exception\HttpResponseException;

/**
 * LeadScrapingController
 * @desc 截流设置
 */
class LeadScrapingController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new LeadScrapingLists());
    }

    /**
     * @desc 添加截流设置
     */
    public function add()
    {
        try {
            $params = (new LeadScrapingValidate())->post()->goCheck('add');
            $result = LeadScrapingLogic::add($params);
            if ($result) {
                return $this->success(data: LeadScrapingLogic::getReturnData());
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 更新截流设置
     */
//    public function update()
//    {
//        try {
//            $params = (new LeadScrapingValidate())->post()->goCheck('update');
//            $result = LeadScrapingLogic::update($params);
//            if ($result) {
//                return $this->success(data: LeadScrapingLogic::getReturnData());
//            }
//            return $this->fail(LeadScrapingLogic::getError());
//        } catch (HttpResponseException $e) {
//            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
//        }
//    }

    /**
     * @desc 删除
     */
    public function delete()
    {
        try {
            $params = (new LeadScrapingValidate())->post()->goCheck('delete');
            $result = LeadScrapingLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 详情
     */
    public function detail()
    {
        try {
            $params = (new LeadScrapingValidate())->get()->goCheck('detail');
            $result = LeadScrapingLogic::detail($params);
            if ($result) {
                return $this->data(LeadScrapingLogic::getReturnData());
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordLists()
    {
        return $this->dataLists(new LeadScrapingRecordLists());
    }

    public function recordDetail()
    {

        try {
            $params = (new LeadScrapingRecordValidate())->get()->goCheck('detail');
            $result = LeadScrapingLogic::recordDetail($params);
            if ($result) {
                return $this->data(LeadScrapingLogic::getReturnData());
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordDelete()
    {
        try {
            $params = (new LeadScrapingRecordValidate())->post()->goCheck('delete');
            $result = LeadScrapingLogic::recordDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 添加行业历史记录
     */
    public function getIndustryLog()
    {
        try {
            $params = $this->request->get();
            $result = LeadScrapingLogic::getIndustryLog($params);
            if ($result) {
                return $this->success(data: LeadScrapingLogic::getReturnData());
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 添加行业历史记录
     */
    public function deleteIndustryLog()
    {
        try {
            $params = $this->request->post();
            $result = LeadScrapingLogic::deleteIndustryLog($params['id']);
            if ($result) {
                return $this->success(data: LeadScrapingLogic::getReturnData());
            }
            return $this->fail(LeadScrapingLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

}
