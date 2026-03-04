<?php


namespace app\api\controller\kb;

use app\api\controller\BaseApiController;
use app\api\lists\kb\KbRobotLists;
use app\api\logic\kb\KbRobotLogic;
use app\common\logic\BaseLogic;
use Exception;
use think\db\exception\DbException;
use think\response\Json;

/**
 * 机器人管理
 */
class RobotController extends BaseApiController
{
    public array $notNeedLogin = ['lists','commonLists'];

    /**
     * @notes 智能体列表
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function all(): Json
    {
        $params = $this->request->get();
        $result = KbRobotLogic::all($params, $this->userId);
        return $this->data($result);
    }

    /**
     * @notes 智能体置顶
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function top(): Json
    {
        $params = $this->request->post();
        $result = KbRobotLogic::top($params, $this->userId);
        if ($result === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('操作成功');
    }

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
     * @notes 机器人列表
     * @return Json
     * @author kb
     */
    public function commonLists(): Json
    {
        return $this->dataLists((new KbRobotLists()));
    }

    /**
     * @notes 机器人详情
     * @return Json
     * @author kb
     */
    public function detail(): Json
    {
        $params = $this->request->get();
        try {
            $detail = KbRobotLogic::detail(intval($params['id']), $this->userId);
            return $this->data($detail);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 机器人新增
     * @return Json
     * @author kb
     */
    public function add(): Json
    {
        $post = $this->request->post();
        $results = KbRobotLogic::add($post, $this->userId);
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
        $params = $this->request->post();
        $results = KbRobotLogic::edit($params, $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('编辑成功');
    }

    /**
     * @notes 机器人删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $params = $this->request->post();
        $results = KbRobotLogic::del(intval($params['id']), $this->userId);
        if ($results === false) {
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('删除成功');
    }


    /**
     * @notes 分享列表
     * @return Json
     * @author kb
     * @date 2024/7/25 11:26
     */
    public function categoryLists(){
        $results = KbRobotLogic::categoryLists();
        return $this->success('', $results);
    }


    /**
     * @notes 机器人分享
     * @return Json
     * @author kb
     * @date 2024/7/25 11:22
     */
    public function share()
    {
        $params = $this->request->post();
        $result = KbRobotLogic::share($params, $this->userInfo);
        if (false === $result) {
            return $this->fail(KbRobotLogic::getError());
        }
        $tips = BaseLogic::getReturnData() ?: '分享成功';
        return $this->success($tips);

    }

    /**
     * @notes 取消分享
     * @return Json
     * @author kb
     * @date 2024/7/26 16:36
     */
    public function cancelShare()
    {
        $params = $this->request->post();
        $result = KbRobotLogic::cancelShare($params,$this->userId);
        if(false === $result){
            return $this->fail(KbRobotLogic::getError());
        }
        return $this->success('取消成功');
//        if ($results === false) {
//            return $this->fail(KbRobotLogic::getError());
//        }
    }

    public function systemLists(): Json
    {
        $params = $this->request->get();
        try {
            $detail = KbRobotLogic::getSystemLists($params);
            return $this->data($detail);
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function getCopywriting()
    {
        $params = $this->request->post();
        $params['user_id'] =  $this->userId ;
        return KbRobotLogic::getCopywriting($params) ? $this->success(data: KbRobotLogic::getReturnData()) : $this->fail(KbRobotLogic::getError());
    }
}