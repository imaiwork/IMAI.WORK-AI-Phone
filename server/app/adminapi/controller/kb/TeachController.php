<?php


namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\kb\KbKnowTestRecordLists;
use app\adminapi\lists\kb\KbTeachLists;
use app\adminapi\logic\kb\KbTeachLogic;
use think\response\Json;

/**
 * 训练数据管理
 */
class TeachController extends BaseAdminController
{
    /**
     * @notes 训练数据列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists(new KbTeachLists());
    }

    /**
     * @notes 训练数据列表
     * @return Json
     * @author kb
     */
    public function datas(): Json
    {
        return $this->dataLists(new KbTeachLists());
    }

    /**
     * @notes 训练数据删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $uuid = $this->request->post('uuid');
        $result = KbTeachLogic::del($uuid);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 训练数据删除
     * @return Json
     * @author kb
     */
    public function delete(): Json
    {
        $params = $this->request->post();
        $result = KbTeachLogic::delete($params, 0);
        if ($result === false) {
            return $this->fail(KbTeachLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 训练数据详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        $detail = KbTeachLogic::detail($params['uuid']);
        return $this->data($detail);
    }


    /**
     * @notes 训练数据修正
     * @return Json
     * @author kb
     */
    public function update(): Json
    {
        $params = $this->request->post();
        $result = KbTeachLogic::update($params, 0);
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
        $params = $this->request->post();
        $result = KbTeachLogic::reset($params, 0);
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
        $params = $this->request->post();
        $result = KbTeachLogic::insert($params, 0);
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
        $params = $this->request->post();
        $result = KbTeachLogic::import($params, 0);
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
        $params = $this->request->post();
        $result = KbTeachLogic::tests($params,0);
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
        $params = $this->request->get();
        $detail = KbTeachLogic::testRecordDetail($params['tr_id']);
        return $this->data($detail);
    }

    /**
     * @notes QA检测
     * @return Json
     * @author kb
     */
    public function qaCheck(): Json
    {
        $fdIds = $this->request->post('fd_ids', []);
        $result = KbTeachLogic::qaCheck($fdIds, 0);
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
        $result = KbTeachLogic::qaRetry($kbId, $fdId, 0);
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