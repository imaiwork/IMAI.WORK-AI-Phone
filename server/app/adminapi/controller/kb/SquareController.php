<?php

namespace app\adminapi\controller\kb;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\kb\KbSquareLogic;
use app\adminapi\validate\kb\KbSquareValidate;
use think\response\Json;

/**
 * 机器人广场管理
 */
class SquareController extends BaseAdminController
{
    /**
     * @notes 机器人广场列表
     * @return Json
     * @author kb
     */
    public function lists(): Json
    {
        $lists = KbSquareLogic::lists($this->request->get());
        return $this->data($lists);
    }

    /**
     * @notes 机器人广场编辑
     * @return Json
     * @author kb
     */
    public function edit(): Json
    {
        $params = (new KbSquareValidate())->post()->goCheck();
        $result = KbSquareLogic::edit($params);
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('编辑成功', [], 1, 1);
    }

    /**
     * @notes 机器人广场删除
     * @return Json
     * @author kb
     */
    public function del(): Json
    {
        $params = (new KbSquareValidate())->post()->goCheck('id');
        $result = KbSquareLogic::del(intval($params['id']));
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('删除成功', [], 1, 1);
    }

    /**
     * @notes 机器人广场状态
     * @return Json
     * @author kb
     */
    public function setStatus(): Json
    {
        $params = (new KbSquareValidate())->post()->goCheck('status');
        $result = KbSquareLogic::setStatus(intval($params['id']), intval($params['is_show']));
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('修改成功', [], 1, 1);
    }

    /**
     * @notes 机器人广场排序
     * @return Json
     * @author kb
     */
    public function setSort(): Json
    {
        $params = (new KbSquareValidate())->post()->goCheck('sort');
        $result = KbSquareLogic::setStatus(intval($params['id']), intval($params['sort']));
        if ($result === false) {
            return $this->fail(KbSquareLogic::getError());
        }
        return $this->success('修改成功', [], 1, 1);
    }

    /**
     * @notes 审核状态
     * @return mixed
     * @author ljj
     * @date 2023/8/31 12:19 下午
     */
    public function verifyStatus()
    {
        $params = $this->request->post();
        KbSquareLogic::verifyStatus($params);
        return $this->success('操作成功', [], 1, 1);
    }

    /**
     * @notes 获取智能体广场配置
     * @return mixed
     * @author ljj
     * @date 2023/8/31 2:49 下午
     */
    public function getConfig()
    {
        $result = KbSquareLogic::getConfig();
        return $this->success('',$result);
    }

    /**
     * @notes 设置音乐广场配置
     * @return mixed
     * @author ljj
     * @date 2023/8/31 2:50 下午
     */
    public function setConfig()
    {
        $params = $this->request->post();
        KbSquareLogic::setConfig($params);
        return $this->success('操作成功', [], 1, 1);
    }


}