<?php
namespace app\api\controller;


use app\api\lists\suno\SunoLists;
use app\api\logic\suno\SunoLogic;
use app\api\validate\suno\SunoValidate;
use app\api\controller\BaseApiController;
use think\response\Json;



/**
 *
 * Class WechatController
 * @package app\api\controller
 */
class SunoController extends BaseApiController
{
    public array $notNeedLogin = [
        'updateTaskStatus',
    ];
    /**
     * @notes 列表
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function lists()
    {
        return $this->dataLists(new SunoLists());
    }


    /**
     * @notes 添加
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function add():Json
    {
        $params = (new SunoValidate())->post()->goCheck('add');
        return SunoLogic::add($params, $this->userId)
            ? $this->success('操作成功', SunoLogic::getReturnData())
            : $this->fail(SunoLogic::getError());
    }

    /**
     * @notes 创建音乐
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function createMusic():Json
    {
        $params = (new SunoValidate())->post()->goCheck('createMusic');
        return SunoLogic::createMusic($params, $this->userId)
            ? $this->success('操作成功', SunoLogic::getReturnData())
            : $this->fail(SunoLogic::getError());
    }

    /**
     * 修改任务状态 (定时任务)
     * @return Json
     * @author L
     * @data 2024/7/3 16:52
     */
    public function updateTaskStatus()
    {
        set_time_limit(0);
        return SunoLogic::updateTaskStatus() ? $this->success() : $this->fail(SunoLogic::getError());
    }

    /**
     * @notes 删除
     * @return Json
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function delete():Json
    {
        $id = $this->request->get('id/d');
        if (empty($id)) {
            return $this->fail('参数丢失');
        }
        return SunoLogic::delete($id, $this->userId) ? $this->success() : $this->fail(SunoLogic::getError());
    }


    /**
     * @notes 详情
     * @return Json
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function detail():Json
    {
        $id = $this->request->get('id/d');
        if (empty($id)) {
            return $this->fail('参数丢失');
        }
        $result = SunoLogic::detail($id, $this->userId);
        return $this->success(data: SunoLogic::getReturnData());
    }


    /**
     * @notes 修改
     * @return Json
     * @author L
     * @date 2024-07-03 10:09:00
     */
    public function edit():Json
    {
        $postData = (new SunoValidate())->post()->goCheck('add');
        $edit      = SunoLogic::edit($postData, $this->userId);
        return $edit ? $this->success() : $this->fail(SunoLogic::getError());
    }
}
            