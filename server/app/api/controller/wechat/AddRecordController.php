<?php


namespace app\api\controller\wechat;

use app\api\controller\BaseApiController;
use app\api\lists\wechat\AddRecordLists;
use app\api\logic\wechat\AddRecordLogic;
use app\api\validate\wechat\AddRecordValidate;
use think\exception\HttpResponseException;

/**
 * AddRecordController
 * @desc AI销售加微记录
 * @author Qasim
 */
class AddRecordController extends BaseApiController
{

    public array $notNeedLogin = [];

    /**
     * @desc 获取加微记录列表
     */
    public function lists()
    {
        return $this->dataLists(new AddRecordLists());
    }

    /**
     * @desc 删除加微记录
     */
    public function delete()
    {
        try {
            $params = (new AddRecordValidate())->post()->goCheck('delete');
            $result = AddRecordLogic::delete($params);
            if ($result) {
                return $this->success();
            }
            return $this->fail(AddRecordLogic::getError());
        } catch (HttpResponseException $e) {
            return $this->fail($e->getResponse()->getData()['msg'] ?? '');
        }
    }
}
