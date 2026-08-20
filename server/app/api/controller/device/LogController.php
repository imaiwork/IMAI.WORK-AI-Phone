<?php

namespace app\api\controller\device;

use app\api\controller\BaseApiController;
use app\api\lists\device\LogLists;
use app\api\logic\device\LogLogic;
use app\api\validate\device\LogValidate;
use think\exception\HttpResponseException;

/**
 * LogController
 * @desc 设备运行日志
 */
class LogController extends BaseApiController
{
    /**
     * @desc 获取列表
     */
    public function lists()
    {
        return $this->dataLists(new LogLists());
    }

    /**
     * @desc 获取详情
     */
    public function detail()
    {
        try {
            $params = (new LogValidate())->get()->goCheck('detail');
            $result = LogLogic::detail($params);
            if ($result) {
                return $this->data(LogLogic::getReturnData());
            }
            return $this->fail(LogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }

    /**
     * @desc 批量删除
     */
    public function delete()
    {
        try {
            $params = (new LogValidate())->post()->goCheck('delete');
            $result = LogLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(LogLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
