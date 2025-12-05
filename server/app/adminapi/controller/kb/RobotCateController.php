<?php

namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\kb\KbRobotCateLists;
use app\adminapi\logic\kb\KbRobotCateLogic;
use app\adminapi\validate\kb\KbRobotCateValidate;
use think\response\Json;

/**
 * 机器人分类管理
 */
class RobotCateController extends BaseAdminController
{
    /**
     * @notes 所有分类
     * @return Json
     * @author kb
     */
    public function all(): Json
    {
        $lists = KbRobotCateLogic::all();
        return $this->data($lists);
    }

    /**
     * @notes 机器人分类列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists((new KbRobotCateLists()));
    }

    /**
     * @notes 机器人分类详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        (new KbRobotCateValidate())->goCheck('id');
        $id = intval($this->request->get('id'));

        $detail = KbRobotCateLogic::detail($id);
        if (!$detail) {
            return $this->fail('数据不存在');
        }
        return $this->data($detail);
    }

    /**
     * @notes 机器人分类新增
     * @return Json
     * @author kb
     */
    public function add(): Json
    {
        $params = (new KbRobotCateValidate())->post()->goCheck('add');
        $result = KbRobotCateLogic::add($params);
        if (!$result) {
            return $this->fail(KbRobotCateLogic::getError());
        }
        return $this->success('添加成功', [], 1, 1);
    }

    /**
     * @notes 机器人分类编辑
     * @return Json
     * @author kb
     */
    public function edit(): Json
    {
        $params = (new KbRobotCateValidate())->post()->goCheck();
        $result = KbRobotCateLogic::edit($params);
        if (!$result) {
            return $this->fail(KbRobotCateLogic::getError());
        }
        return $this->success('编辑成功', [], 1, 1);
    }

    /**
     * @notes 机器人分类删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $params = (new KbRobotCateValidate())->post()->goCheck('id');
        $result = KbRobotCateLogic::del(intval($params['id']));
        if (!$result) {
            return $this->fail(KbRobotCateLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 机器人分类状态
     * @return Json
     * @author kb
     */
    public function changeStatus(): Json
    {
        (new KbRobotCateValidate())->post()->goCheck('id');
        $id = intval($this->request->post('id'));

        $result = KbRobotCateLogic::changeStatus($id);
        if ($result === false) {
            return $this->fail(KbRobotCateLogic::getError());
        }

        return $this->success(KbRobotCateLogic::getError(), [], 1, 1);
    }
}