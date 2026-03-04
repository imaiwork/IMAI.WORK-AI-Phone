<?php


namespace app\api\controller\kb;

use app\api\controller\BaseApiController;
use app\api\lists\kb\KbRobotGroupLists;
use app\api\logic\kb\KbRobotGroupLogic;
use Exception;
use think\response\Json;

/**
 * 智能体分组管理
 */
class RobotGroupController extends BaseApiController
{
    public array $notNeedLogin = ['lists'];

    /**
     * @notes 智能体分组列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        return $this->dataLists((new KbRobotGroupLists()));
    }

    /**
     * @notes 智能体分组详情
     * @return Json
     * @throws Exception
     * @author kb
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        $results = KbRobotGroupLogic::detail(intval($params['id']), $this->userId);
        if (!$results) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->data($results);
    }

    /**
     * @notes 智能体分组创建
     * @return Json
     */
    public function add(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::add($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('创建成功', $results);
    }

    /**
     * @notes 智能体分组编辑
     * @return Json
     * @author kb
     */
    public function update(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::update($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 智能体分组删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::del(intval($params['id']), $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('删除成功');
    }

    /**
     * @notes 智能体加入分组
     * @return Json
     * @author kb
     */
    public function join(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::join($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('加入分组成功');
    }

    /**
     * @notes 智能体移除分组
     * @return Json
     * @author kb
     */
    public function remove(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::remove($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('移除分组成功');
    }

    /**
     * @notes 智能体分组置顶
     * @return Json
     * @author kb
     */
    public function top(): Json
    {
        $params = $this->request->post();
        $results = KbRobotGroupLogic::top($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotGroupLogic::getError());
        }
        return $this->success('操作成功');
    }
}