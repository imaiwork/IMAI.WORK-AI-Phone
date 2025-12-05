<?php


namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\kb\ChatLists;
use app\adminapi\lists\kb\KbRobotLists;
use app\adminapi\logic\kb\KbRobotLogic;
use app\adminapi\validate\IDMustValidate;
use think\response\Json;

/**
 * 机器人管理
 */
class RobotController extends BaseAdminController
{
    /**
     * @notes 机器人列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists((new KbRobotLists()));
    }

    /**
     * @desc 会话列表
     * @return Json
     */
    public function chatLists()
    {
        return $this->dataLists(new ChatLists());
    }

    /**
     * @notes 机器人新增
     * @return Json
     * @author kb
     */
    public function add(): Json
    {
        $post    = $this->request->post();
        $results = KbRobotLogic::add($post);
        if ($results === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('创建成功', $results);
    }

    /**
     * @notes 机器人编辑
     * @return Json
     * @author kb
     */
    public function edit(): Json
    {
        $params  = $this->request->post();
        $results = KbRobotLogic::edit($params);
        if ($results === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 机器人详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        (new IDMustValidate())->goCheck();
        $id = intval($this->request->get('id'));

        $result = KbRobotLogic::detail($id);
        return $this->data($result);
    }

    /**
     * @notes 机器人删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = $this->request->post('id');
        if (is_string($id)) {
            $id = explode(',', $id);
        }
        $result = KbRobotLogic::del($id);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }

        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 修改机器人状态
     * @return Json
     * @author kb
     */
    public function changeStatus(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = intval($this->request->post('id'));

        $result = KbRobotLogic::changeStatus($id);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }

        return $this->success(KbRobotLogic::getError(), [], 1, 1);
    }

    /**
     * @notes 修改广场的状态
     * @return Json
     * @author kb
     */
    public function changePublic(): Json
    {
        (new IDMustValidate())->post()->goCheck();
        $id = intval($this->request->post('id'));

        $result = KbRobotLogic::changePublic($id);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }

        return $this->success(KbRobotLogic::getError(), [], 1, 1);
    }

    /**
     * @notes 机器人问答记录
     * @return Json
     * @author kb
     */
    public function chatRecord(): Json
    {
        $get = $this->request->get();
        $result = KbRobotLogic::chatRecord($get);
        return $this->data($result);
    }

    /**
     * @notes 机器人问答删除
     * @return Json
     * @author kb
     */
    public function chatClean(): Json
    {
        $ids = $this->request->post('ids', []);

        $result = KbRobotLogic::chatClean($ids);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 机器人删除
     * @return Json
     * @author kb
     */
    public function deleteChatLog(): Json
    {
        $id     = $this->request->post('id');
        if (is_string($id)) {
            $id = explode(',', $id);
        }
        $result = KbRobotLogic::deleteChatLog($id);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

}