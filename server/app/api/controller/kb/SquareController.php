<?php

namespace app\api\controller\kb;

use app\api\controller\BaseApiController;
use app\api\logic\kb\KbSquareLogic;
use think\db\exception\DbException;
use think\response\Json;

/**
 * 机器人广场管理
 */
class SquareController extends BaseApiController
{
    public array $notNeedLogin = ['category', 'lists'];

    /**
     * @notes 机器人分类
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function category(): Json
    {
        $lists = KbSquareLogic::category();
        return $this->data($lists);
    }

    /**
     * @notes 机器人广场列表
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function lists(): Json
    {
        $get = $this->request->get();
        $lists = KbSquareLogic::lists($get);
        return $this->data($lists);
    }

    /**
     * @notes 使用的记录
     * @return Json
     * @throws DbException
     * @author kb
     */
    public function record(): Json
    {
        $lists = KbSquareLogic::record($this->userId);
        return $this->data($lists);
    }

    /**
     * @notes 使用机器人
     * @return Json
     * @author kb
     */
    public function add(): Json
    {
        $squareId = intval($this->request->post('id'));
        $result = KbSquareLogic::add($squareId, $this->userId);
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('OK', $result);
    }

    /**
     * @notes 删除使用机器人
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $id = intval($this->request->post('id'));
        $result = KbSquareLogic::del($id, $this->userId);
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('OK');
    }
}