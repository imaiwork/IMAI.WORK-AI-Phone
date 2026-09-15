<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\lists\sv\CrawlingManualLists;
use app\api\lists\sv\CrawlingManualRecordLists;

use think\exception\HttpResponseException;
use app\api\validate\sv\CrawlingManualValidate;
use app\api\logic\sv\CrawlingManualLogic;

/**
 * CrawlingManualController
 * 爬取任务控制器
 */
class CrawlingManualController extends BaseApiController
{
    public array $notNeedLogin = ['cron'];

    public function lists()
    {
        return $this->dataLists(new CrawlingManualLists());
    }

    public function add()
    {
        try {
            $params = (new CrawlingManualValidate())->post()->goCheck('add');
            $result = CrawlingManualLogic::add($params);
            if ($result) {
                return $this->data(CrawlingManualLogic::getReturnData());
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new CrawlingManualValidate())->get()->goCheck('detail');
            $result = CrawlingManualLogic::detail($params);
            if ($result) {
                return $this->data(CrawlingManualLogic::getReturnData());
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function delete()
    {
        try {
            $params = (new CrawlingManualValidate())->post()->goCheck('delete');
            $result = CrawlingManualLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function change()
    {
        try {
            $params = (new CrawlingManualValidate())->post()->goCheck('change');
            $result = CrawlingManualLogic::change($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function recordLists(){
        return $this->dataLists(new CrawlingManualRecordLists());
    }
    public function recordDelete(){
        try {
            $params = (new CrawlingManualValidate())->post()->goCheck('recordDelete');
            $result = CrawlingManualLogic::recordDelete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function cron(){
        try {
            CrawlingManualLogic::crawlingManualTaskCron();
            return $this->success();
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function subtasks(){
        try {
            $params = $this->request->post();
            $result = CrawlingManualLogic::subtasks($params);
            if ($result) {
                return $this->data(CrawlingManualLogic::getReturnData());
            }
            return $this->fail(CrawlingManualLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}