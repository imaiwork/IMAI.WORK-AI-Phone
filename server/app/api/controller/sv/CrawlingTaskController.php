<?php

namespace app\api\controller\sv;

use app\api\controller\BaseApiController;
use app\api\logic\sv\CrawlingTaskLogic;
use app\api\validate\sv\CrawlingTaskValidate;
use app\api\lists\sv\CrawlingTaskLists;
use app\api\lists\sv\CrawlingRecordLists;

use app\common\traits\SphTaskTrait;
use think\exception\HttpResponseException;

/**
 * CrawlingTaskController
 * 爬取任务控制器
 */
class CrawlingTaskController extends BaseApiController
{
    use SphTaskTrait;
    public array $notNeedLogin = ['ocr', 'localOcr'];

    public function lists()
    {
        return $this->dataLists(new CrawlingTaskLists());
    }

    public function add()
    {
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('add');
            $result = CrawlingTaskLogic::add($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function detail()
    {
        try {
            $params = (new CrawlingTaskValidate())->get()->goCheck('detail');
            $result = CrawlingTaskLogic::detail($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 任务删除
     */
    public function delete()
    {
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('delete');
            self::sphDelete($params['id']);
            return $this->success();
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function deleteDevice()
    {
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('deleteDevice');
            $result = CrawlingTaskLogic::deleteDevice($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function update()
    {
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('update');
            $result = CrawlingTaskLogic::update($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function listsRecords()
    {
        return $this->dataLists(new CrawlingRecordLists());
    }

     /**
     * 任务暂停pause
     */
    public function changeStatus(){
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('common');
            $result = CrawlingTaskLogic::changeStatus($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        }catch (HttpResponseException $e){
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 任务开启send
     */
    public function retry(){
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('common');
            $task_id = $params['id'];
            self::sphSend($task_id);
            return $this->success();
        }catch (HttpResponseException $e){
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    public function keywords(){
        try {
            $params = (new CrawlingTaskValidate())->get()->goCheck('common');
            $result = CrawlingTaskLogic::keywordsCount($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }


    /**
     * 任务开启send
     */
    public function send(){
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('common');
            $task_id = $params['id'];
            self::sphSend($task_id);
            return $this->success();
        }catch (HttpResponseException $e){
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 任务暂停pause
     */
    public function pause(){
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('common');
            $task_id = $params['id'];
            self::sphPause($task_id);
            return $this->success();
        }catch (HttpResponseException $e){
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * 任务继续recovery
     */
    public function recovery(){
        try {
            $params = (new CrawlingTaskValidate())->post()->goCheck('common');
            $task_id = $params['id'];
            self::sphRecovery($task_id);
            return $this->success();
        }catch (HttpResponseException $e){
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }



    public function ocr()
    {
        $params = $this->request->post();
        $result = CrawlingTaskLogic::ocr($params);
        if ($result) {
            return $this->success(data: CrawlingTaskLogic::getReturnData());
        }
        return $this->fail(CrawlingTaskLogic::getError());
    }

    public function localOcr()
    {
        $params = $this->request->post();
        $result = CrawlingTaskLogic::localOcr($params);
        if ($result) {
            return $this->success(data: CrawlingTaskLogic::getReturnData());
        }
        return $this->fail(CrawlingTaskLogic::getError());
    }

    public function subtasks(){
        try {
            $params = $this->request->post();
            $result = CrawlingTaskLogic::subtasks($params);
            if ($result) {
                return $this->data(CrawlingTaskLogic::getReturnData());
            }
            return $this->fail(CrawlingTaskLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}