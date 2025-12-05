<?php


namespace app\api\controller\kb;

use app\api\controller\BaseApiController;
use app\api\lists\kb\KbKnowTestRecordLists;
use app\api\lists\kb\KbTeachLists;
use app\api\logic\kb\KbTeachLogic;
use app\api\validate\kb\KbTeachValidate;
use think\response\Json;

/**
 * 训练数据管理
 */
class TeachController extends BaseApiController
{
    /**
     * @notes 训练数据列表
     * @return Json
     * @author kb
     */
    public function datas(): Json
    {
        return $this->dataLists((new KbTeachLists()));
    }

    /**
     * @notes 检测数据状态
     * @return Json
     * @author kb
     */
    public function detection(): Json
    {
        //$params = (new KbTeachValidate())->post()->goCheck('check');
        $params = $this->request->post();
        $detail = KbTeachLogic::detection($this->userId, intval($params['kb_id']), intval($params['fd_id']), $params['uuids']);
        return $this->data($detail);
    }

    /**
     * @notes 训练数据详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
//        $params = (new KbTeachValidate())->get()->goCheck('uuid');
        $params = $this->request->get();
        $detail = KbTeachLogic::detail($params['uuid']);
        return $this->data($detail);
    }

    /**
     * @notes 训练数据删除
     * @return Json
     * @author kb
     */
    public function delete(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('delete');
        $params = $this->request->post();
        $result = KbTeachLogic::delete($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 训练数据修正
     * @return Json
     * @author kb
     */
    public function update(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('update');
        $params = $this->request->post();
        $result = KbTeachLogic::update($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('操作成功');
    }

    /**
     * @notes 训练失败重试
     * @return Json
     * @author kb
     */
    public function reset(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('reset');
        $params = $this->request->post();
        $result = KbTeachLogic::reset($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('发起成功');
    }

    /**
     * @notes 训练数据录入 (手动录入)
     * @return Json
     * @author kb
     */
    public function insert(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('insert');
        $params = $this->request->post();
        $result = KbTeachLogic::insert($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('录入成功');
    }

    /**
     * @notes 导入训练数据 (文件导入)
     * @return Json
     * @author kb
     */
    public function import(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('import', ['user_id'=>$this->userId]);
        $params = $this->request->post();
        $result = KbTeachLogic::import($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('导入成功');
    }

    /**
     * @notes 搜索测试
     * @return Json
     * @author kb
     */
    public function tests(): Json
    {
//        $params = (new KbTeachValidate())->post()->goCheck('tests');
        $params = $this->request->post();
        //旧版搜索测试
//        $result = KbTeachLogic::tests_old($params['kb_id'], $params['question'],$this->userId);
        $result = KbTeachLogic::tests($params,$this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes 搜索测试数据列表
     * @return Json
     * @author kb
     */
    public function testRecords(): Json
    {
        return $this->dataLists((new KbKnowTestRecordLists()));
    }

    /**
     * @notes 搜索测试数据详情
     * @return Json
     * @author kb
     */
    public function testRecordDetail(): Json
    {
//        $params = (new KbTeachValidate())->get()->goCheck('tr_id');
        $params = $this->request->get();
        $detail = KbTeachLogic::testRecordDetail($params['tr_id']);
        return $this->data($detail);
    }

    /**
     * @notes 预估价格计算
     * @return Json
     * @author kb
     */
    public function charging(): Json
    {
        $kbId = $this->request->post('kb_id', 0);
        $text = $this->request->post('text', '');
        $result = KbTeachLogic::charging($kbId, $text);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->data($result);
    }

    /**
     * @notes QA检测
     * @return Json
     * @author kb
     */
    public function qaCheck(): Json
    {
        $fdIds = $this->request->post('fd_ids', []);
        $result = KbTeachLogic::qaCheck($fdIds, $this->userId);
        return $this->data($result);
    }

    /**
     * @notes QA拆分重试
     * @return Json
     * @author kb
     */
    public function qaRetry(): Json
    {
        $kbId = intval($this->request->post('kb_id', 0));
        $fdId = intval($this->request->post('fd_id', 0));
        $result = KbTeachLogic::qaRetry($kbId, $fdId, $this->userId);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('已发起重试');
    }

    public function capture(): Json
    {
        $url  = $this->request->post('url',[]);
        $result = KbTeachLogic::capture($url);
        if (is_array($result)) {
            return $this->success('抓取成功', $result);
        }
        return $this->fail(KbTeachLogic::getError());
    }
}